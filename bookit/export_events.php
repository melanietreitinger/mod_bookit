<?php
// This file is part of Moodle – https://moodle.org/
//
// Export selected BookIT events as an .ics file.
//
// @package     mod_bookit
// @copyright   2024 Melanie Treitinger
// @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

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
    $loc     = ics_escape($ev->room        ?? '');
    $descArr = [];
    if (!empty($ev->department))         { $descArr[] = 'Faculty: '       . $ev->department; }
    if (!empty($ev->technicalneeds))     { $descArr[] = 'Requirements: '  . $ev->technicalneeds; }
    if (!empty($ev->participantsamount)) { $descArr[] = 'Participants: '  . $ev->participantsamount; }
    $description = ics_escape(implode('\n', $descArr));

    $lines = array_merge($lines, [
        'BEGIN:VEVENT',
        'UID:'       . $uid,
        'DTSTAMP:'   . gmdate('Ymd\THis\Z'),
        'DTSTART:'   . $start,
        'DTEND:'     . $end,
        'SUMMARY:'   . $summary,
        'LOCATION:'  . $loc,
        'DESCRIPTION:' . $description,
        'END:VEVENT'
    ]);
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
        ['\\\\', '\,', '\;', '',   '\n'],
        $s
    );
}
