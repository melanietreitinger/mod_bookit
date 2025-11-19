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
$cmid    = required_param('id', PARAM_INT);          /* course-module id */
$ids     = optional_param_array('ids', [], PARAM_INT); /* ids[]=1 & ids[]=2…  No codechecker this is not code!!!!*/
$room    = optional_param('room', 0, PARAM_INT);
$faculty = optional_param('faculty', '', PARAM_TEXT);
$status  = optional_param('status', -1, PARAM_INT);

$cm      = get_coursemodule_from_id('bookit', $cmid, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/bookit:viewownoverview', $context);

/* ------------------------------------------------------------------
   1.  Fetch events (either explicit ids[] or time-range)
   ------------------------------------------------------------------ */
global $DB, $USER;

$viewalldetailsofevent    = has_capability('mod/bookit:viewalldetailsofevent', $context);
$viewalldetailsofownevent = has_capability('mod/bookit:viewalldetailsofownevent', $context);
$events = [];

if (!empty($ids)) {
    // Export specific IDs, but only those the user is allowed to see in detail.
    $inorequal = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'e');
    $in = $inorequal[0];
    $inparams = $inorequal[1];

    if ($viewalldetailsofevent) {
        $sql = "SELECT *
                    FROM {bookit_event}
                WHERE id $in";
        $events = $DB->get_records_sql($sql, $inparams);
    } else if ($viewalldetailsofownevent) {
        $like = $DB->sql_like('otherexaminers', ':otherex');
        $sql  = "SELECT *
                    FROM {bookit_event}
                    WHERE id $in
                    AND (
                        usermodified = :uid
                        OR personinchargeid = :uid2
                        OR $like
                    )";
        $params = $inparams + ['uid' => $USER->id, 'uid2' => $USER->id, 'otherex' => $USER->id];
        $events = $DB->get_records_sql($sql, $params);
    } else {
        // No details capability: nothing exportable.
        $events = [];
    }
} else {
    // Time-range export, capability-safe.
    $start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
    $end   = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);

    $startts = (new DateTime(str_replace('T', ' ', $start)))->getTimestamp();
    $endts   = (new DateTime(str_replace('T', ' ', $end)))->getTimestamp();

    if ($viewalldetailsofevent) {
        $sql = "SELECT *
                    FROM {bookit_event}
                WHERE endtime >= :starttime
                    AND starttime <= :endtime";
        $params = ['starttime' => $startts, 'endtime' => $endts];
        $events = $DB->get_records_sql($sql, $params);
    } else if ($viewalldetailsofownevent) {
        $like = $DB->sql_like('otherexaminers', ':otherex');
        $sql  = "SELECT *
                    FROM {bookit_event}
                    WHERE endtime >= :starttime
                    AND starttime <= :endtime
                    AND (
                        usermodified = :uid
                        OR personinchargeid = :uid2
                        OR $like
                    )";
        $params = [
            'starttime' => $startts,
            'endtime'   => $endts,
            'uid'       => $USER->id,
            'uid2'      => $USER->id,
            'otherex'   => $USER->id,
        ];
        $events = $DB->get_records_sql($sql, $params);
    } else {
        // No details capability: nothing exportable.
        $events = [];
    }
}

/* additional UI filters ------------------------------------------------ */
$events = array_filter($events, static function ($e) use ($room, $faculty, $status): bool {

    if ($room) {
        // Not here.
    }

    if ($faculty && $faculty !== ($e->department ?? '')) {
        return false;
    }

    if ($status >= 0 && $status !== (int) ($e->bookingstatus ?? -1)) {
        return false;
    }

    return true;
});

if (!$events) {
    throw new moodle_exception('noevents', 'mod_bookit');
}

/* add the event room */
if ($events) {
    $eventids = array_keys($events);
    $inorequal = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED);
    $in = $inorequal[0];
    $p  = $inorequal[1];
    $sql = "SELECT er.eventid, MIN(r.name) AS room
            FROM {bookit_event_resources} er
        JOIN {bookit_resource}        r  ON r.id = er.resourceid
            WHERE er.eventid $in
        GROUP BY er.eventid";

    foreach ($DB->get_records_sql($sql, $p) as $rec) {
        $events[$rec->eventid]->room = $rec->room ?? '';
    }
}

// Apply room filter after enrichment.
if ($room) {
    $events = array_filter($events, function($ev) use ($room) {
        return (int)$ev->resourceid === (int)$room;
    });
}


/* ------------------------------------------------------------------
   2.  Build VCALENDAR
   ------------------------------------------------------------------ */
    $lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//BookIT//Export//EN',
     ];

    foreach ($events as $ev) {
        $uid   = $ev->id . '@' . parse_url($CFG->wwwroot, PHP_URL_HOST);
        $start = gmdate('Ymd\THis\Z', (int)$ev->starttime);
        $end   = gmdate('Ymd\THis\Z', (int)($ev->endtime ?? ($ev->starttime + 3600)));

        $summary = ics_escape($ev->name);
        $loc     = ics_escape($ev->room ?? '');

        /* ------- human‑readable description ---------------------------- */
        $descrrows = [];
        if (!empty($ev->department)) {
            $descrrows[] = 'Faculty: ' . $ev->department;
        }
        if (!empty($ev->technicalneeds)) {
            $descrrows[] = '| Requirements: ' . $ev->technicalneeds;
        }
        if (!empty($ev->participantsamount)) {
            $descrrows[] = '| Participants: ' . $ev->participantsamount;
        }

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

        if ($descrrows) {
            /* first row starts the DESCRIPTION property */
            $evlines[] = 'DESCRIPTION:' . ics_escape(array_shift($descrrows));
            /* continuation lines: leading space = folded line */
            foreach ($descrrows as $row) {
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
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $ics;
    exit;


    /**
     * Helper: escape newline / comma / semicolon according to RFC 5545
     * @param string $s input string
     */
    function ics_escape(string $s): string {
        return str_replace(
            ['\\', ',', ';', "\r", "\n"],
            ['\\\\', '\,', '\;', '', '\N'],
            $s
        );
    }
