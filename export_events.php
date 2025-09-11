<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2025 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require('../../config.php');
require_once('lib.php');

use mod_bookit\local\manager\event_manager;

/* ------------------------------------------------------------------
   0.  Parameters & capability check
   ------------------------------------------------------------------ */
$cmid    = required_param('id',  PARAM_INT);          // course-module id
$ids     = optional_param_array('ids', [], PARAM_INT); // ids[]=1&ids[]=2 …
$room    = optional_param('room',    0,        PARAM_INT);
$faculty = optional_param('faculty', '',       PARAM_TEXT);
$status  = optional_param('status',  -1,       PARAM_INT);

$cm      = get_coursemodule_from_id('bookit', $cmid, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/bookit:viewownoverview', $context);

/* ------------------------------------------------------------------
   1.  Fetch events (either explicit ids[] or time-range)
   ------------------------------------------------------------------ */
if ($ids) {
    list($sqlids, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
    $events = $DB->get_records_select('bookit_event', "id $sqlids", $params);
} else {
    $start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
    $end   = optional_param('end',   '2100-01-01T00:00', PARAM_TEXT);

    $events = event_manager::get_events_in_timerange(
        (new DateTime($start))->format('Y-m-d H:i'),
        (new DateTime($end))->format('Y-m-d H:i'),
        $cmid
    );
}

/* additional UI filters ------------------------------------------------ */
$events = array_filter($events, static function($e) use ($room, $faculty, $status): bool {
    if ($room     && (int)$room    !== (int)($e->roomid          ?? 0)) return false;
    if ($faculty  && $faculty      !==        ($e->department     ?? '')) return false;
    if ($status >= 0 && $status    !== (int) ($e->bookingstatus  ?? -1)) return false;
    return true;
});

if (!$events) {
    print_error('noevents', 'mod_bookit');
}


//Add the event room
if ($events) {
    $eventids = array_keys($events);
    list($in, $p) = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED);
    $sql = "SELECT er.eventid, MIN(r.name) AS room
              FROM {bookit_event_resources} er
         JOIN {bookit_resource}        r  ON r.id = er.resourceid
             WHERE er.eventid $in
          GROUP BY er.eventid";

    foreach ($DB->get_records_sql($sql, $p) as $rec) {
        $events[$rec->eventid]->room = $rec->room ?? '';
    }
}

/* ------------------------------------------------------------------
   2.  Build VCALENDAR
   ------------------------------------------------------------------ */
/* ------------------------------------------------------------------
   2.  Build VCALENDAR
   ------------------------------------------------------------------ */
   $lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//BookIT//Export//EN'
];

foreach ($events as $ev) {
    $uid   = $ev->id . '@' . parse_url($CFG->wwwroot, PHP_URL_HOST);
    $start = gmdate('Ymd\THis\Z', (int)$ev->starttime);
    $end   = gmdate('Ymd\THis\Z', (int)($ev->endtime ?? ($ev->starttime + 3600)));

    $summary = ics_escape($ev->name);
    $loc     = ics_escape($ev->room ?? '');

    /* ------- human‑readable description ---------------------------- */
    $descrRows = [];
    if (!empty($ev->department))         { $descrRows[] = 'Faculty: '      . $ev->department; }
    if (!empty($ev->technicalneeds))     { $descrRows[] = '| Requirements: ' . $ev->technicalneeds; }
    if (!empty($ev->participantsamount)) { $descrRows[] = '| Participants: ' . $ev->participantsamount; }

    /* ------- assemble one VEVENT ----------------------------------- */
    $evlines = [
        'BEGIN:VEVENT',
        'UID:'      . $uid,
        'DTSTAMP:'  . gmdate('Ymd\THis\Z'),
        'DTSTART:'  . $start,
        'DTEND:'    . $end,
        'SUMMARY:'  . $summary,
        'LOCATION:' . $loc,
    ];

    if ($descrRows) {
        // first row starts the DESCRIPTION property
        $evlines[] = 'DESCRIPTION:' . ics_escape(array_shift($descrRows));
        // continuation lines: leading space = folded line
        foreach ($descrRows as $row) {
            $evlines[] = ' ' . ics_escape($row);
        }
    } else {
        $evlines[] = 'DESCRIPTION:';
    }

    $evlines[] = 'END:VEVENT';

    /* ------- append to calendar ------------------------------------ */
    $lines = array_merge($lines, $evlines);
}

$lines[] = 'END:VCALENDAR';


$ics = implode("\r\n", $lines);

/* ------------------------------------------------------------------
   3.  Output
   ------------------------------------------------------------------ */
$filename = clean_filename('bookit-events-' . date('Ymd-His') . '.ics');
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
echo $ics;
exit;


/* ==================================================================
   Helper: escape newline / comma / semicolon according to RFC 5545
   ================================================================== */
   function ics_escape(string $s): string {
    return str_replace(
        ['\\',   ',',  ';',  "\r", "\n"],
        ['\\\\', '\,', '\;', '',   '\N'],  
        $s
    );
}
