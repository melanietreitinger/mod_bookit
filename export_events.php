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
use mod_bookit\local\manager\event_access_manager;

/**
 * Extract integer user IDs from a CSV field.
 *
 * @param string|null $csv
 * @return int[]
 */
function export_events_csv_user_ids(?string $csv): array {
    if ($csv === null || $csv === '') {
        return [];
    }

    return array_values(array_filter(array_map('intval', explode(',', $csv))));
}

/**
 * Resolve user IDs to display names.
 *
 * @param int[] $userids
 * @return string[]
 */
function export_events_user_names(array $userids): array {
    global $DB;

    $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
    if (empty($userids)) {
        return [];
    }

    $fields = 'id, firstname, lastname, middlename, alternatename, firstnamephonetic, lastnamephonetic';
    $records = $DB->get_records_list('user', 'id', $userids, '', $fields);
    $names = [];
    foreach ($records as $record) {
        $names[(int)$record->id] = fullname($record);
    }

    return $names;
}

/**
 * Resolve institution IDs to display names.
 *
 * @param int[] $institutionids
 * @return string[]
 */
function export_events_institution_names(array $institutionids): array {
    global $DB;

    $institutionids = array_values(array_unique(array_filter(array_map('intval', $institutionids))));
    if (empty($institutionids)) {
        return [];
    }

    $records = $DB->get_records_list('bookit_institution', 'id', $institutionids, '', 'id, name');
    $names = [];
    foreach ($records as $record) {
        $names[(int)$record->id] = $record->name;
    }

    return $names;
}

/**
 * Format semester integer values into the user-facing term label.
 *
 * @param int|null $semester
 * @return string
 */
function export_events_semester_label(?int $semester): string {
    if (empty($semester)) {
        return '';
    }

    $year = (int)floor($semester / 10);
    $term = ((int)$semester % 10) === 1 ? 'summer_semester' : 'winter_semester';

    return get_string($term, 'mod_bookit') . ' ' . $year;
}

/**
 * Format a duration from event start/end timestamps.
 *
 * @param int $starttime
 * @param int $endtime
 * @return string
 */
function export_events_duration_label(int $starttime, int $endtime): string {
    $seconds = max(0, $endtime - $starttime);
    $hours = intdiv($seconds, HOURSECS);
    $minutes = intdiv($seconds % HOURSECS, MINSECS);
    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0 || empty($parts)) {
        $parts[] = $minutes . 'm';
    }

    return implode(' ', $parts);
}

/* ------------------------------------------------------------------
   0.  Parameters & capability check
   ------------------------------------------------------------------ */
$cmid    = required_param('id', PARAM_INT);          /* course-module id */
$ids     = optional_param_array('ids', [], PARAM_INT); /* ids[]=1 & ids[]=2…  No codechecker this is not code!!!!*/
$room    = optional_param('room', 0, PARAM_INT);
$faculty = optional_param('faculty', '', PARAM_TEXT);
$status  = optional_param('status', -1, PARAM_INT);
$readfilters = event_access_manager::normalise_governed_read_filters([
    'start' => optional_param('start', '1970-01-01T00:00', PARAM_TEXT),
    'end' => optional_param('end', '2100-01-01T00:00', PARAM_TEXT),
    'roomids' => $room ? [$room] : [],
    'facultyids' => $faculty === '' ? [] : $faculty,
    'bookingstatuses' => $status >= 0 ? [$status] : [],
]);

$cm      = get_coursemodule_from_id('bookit', $cmid, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
if (event_access_manager::is_observer_restricted_mode($context)) {
    redirect(
        new moodle_url('/mod/bookit/view.php', ['id' => $cmid]),
        get_string('observer_export_denied', 'mod_bookit'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}
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
    // Time-range export stays aligned with the governed calendar read contract.
    $candidateevents = event_manager::get_governed_calendar_events(
        $context,
        (int)$USER->id,
        $readfilters['start'] ?? '1970-01-01 00:00',
        $readfilters['end'] ?? '2100-01-01 00:00',
        $readfilters
    );
    $candidateids = array_values(array_unique(array_map(
        static fn(array $event): int => (int)$event['id'],
        $candidateevents
    )));

    if (empty($candidateids)) {
        $events = [];
    } else {
        [$idinsql, $idparams] = $DB->get_in_or_equal($candidateids, SQL_PARAMS_NAMED, 'g');

        if ($viewalldetailsofevent) {
            $sql = "SELECT *
                      FROM {bookit_event}
                     WHERE id $idinsql";
            $events = $DB->get_records_sql($sql, $idparams);
        } else if ($viewalldetailsofownevent) {
            $like = $DB->sql_like('otherexaminers', ':otherex');
            $sql  = "SELECT *
                        FROM {bookit_event}
                       WHERE id $idinsql
                         AND (
                             usermodified = :uid
                             OR personinchargeid = :uid2
                             OR $like
                         )";
            $params = $idparams + [
                'uid' => $USER->id,
                'uid2' => $USER->id,
                'otherex' => $USER->id,
            ];
            $events = $DB->get_records_sql($sql, $params);
        } else {
            $events = [];
        }
    }
}

if (!$events) {
    throw new moodle_exception('noevents', 'mod_bookit');
}

/* add the event room */
if ($events) {
    $roomids = array_values(array_unique(array_filter(array_map(
        static fn($event) => (int)($event->roomid ?? 0),
        $events
    ))));
    if (!empty($roomids)) {
        $roomrecords = $DB->get_records_list('bookit_room', 'id', $roomids, '', 'id, name');
        foreach ($events as $eventid => $event) {
            $roomid = (int)($event->roomid ?? 0);
            if ($roomid && isset($roomrecords[$roomid])) {
                $events[$eventid]->room = $roomrecords[$roomid]->name;
            }
        }
    }

    $missingroomeventids = array_keys(array_filter($events, static function ($event): bool {
        return empty($event->room);
    }));
    if (!empty($missingroomeventids)) {
        [$in, $p] = $DB->get_in_or_equal($missingroomeventids, SQL_PARAMS_NAMED);
        $sql = "SELECT er.eventid, MIN(r.name) AS room
                FROM {bookit_event_resource} er
                JOIN {bookit_resource} r ON r.id = er.resourceid
                WHERE er.eventid $in
                GROUP BY er.eventid";

        foreach ($DB->get_records_sql($sql, $p) as $rec) {
            $events[$rec->eventid]->room = $rec->room ?? '';
        }
    }
}

$alluserids = [];
$allinstitutionids = [];
foreach ($events as $event) {
    $alluserids[] = (int)($event->usermodified ?? 0);
    $alluserids[] = (int)($event->personinchargeid ?? 0);
    $alluserids = array_merge($alluserids, export_events_csv_user_ids($event->otherexaminers ?? ''));
    $allinstitutionids[] = (int)($event->institutionid ?? 0);
}
$usernames = export_events_user_names($alluserids);
$institutionnames = export_events_institution_names($allinstitutionids);

/* ------------------------------------------------------------------
   2.  Build VCALENDAR
   ------------------------------------------------------------------ */
    $lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//BookIT//Exportg//EN',
     ];

    foreach ($events as $ev) {
        $uid   = $ev->id . '@' . parse_url($CFG->wwwroot, PHP_URL_HOST);
        $start = gmdate('Ymd\THis\Z', (int)$ev->starttime);
        $end   = gmdate('Ymd\THis\Z', (int)($ev->endtime ?? ($ev->starttime + 3600)));

        $summary = ics_escape($ev->name);
        $loc     = ics_escape($ev->room ?? '');

        /* ------- human‑readable description ---------------------------- */
        $descrrows = [];
        $bookingpersonid = (int)($ev->usermodified ?? 0);
        $personinchargeid = (int)($ev->personinchargeid ?? 0);
        $institutionid = (int)($ev->institutionid ?? 0);
        $otherexaminerids = export_events_csv_user_ids($ev->otherexaminers ?? '');
        $otherexaminernames = array_values(array_filter(array_map(
            static fn($userid) => $usernames[$userid] ?? null,
            $otherexaminerids
        )));

        if ($bookingpersonid && isset($usernames[$bookingpersonid])) {
            $descrrows[] = get_string('exportevents_ics_bookingperson', 'mod_bookit') . ': ' . $usernames[$bookingpersonid];
        }
        if ($personinchargeid && isset($usernames[$personinchargeid])) {
            $descrrows[] = get_string('exportevents_ics_personincharge', 'mod_bookit') . ': ' . $usernames[$personinchargeid];
        }
        if (!empty($otherexaminernames)) {
            $descrrows[] = get_string('exportevents_ics_otherexaminers', 'mod_bookit') . ': '
                . implode(', ', $otherexaminernames);
        }
        if (!empty($ev->semester)) {
            $descrrows[] = get_string('exportevents_ics_semester', 'mod_bookit') . ': '
                . export_events_semester_label((int)$ev->semester);
        }
        $descrrows[] = get_string('exportevents_ics_duration', 'mod_bookit') . ': '
            . export_events_duration_label((int)$ev->starttime, (int)$ev->endtime);
        if ($institutionid && isset($institutionnames[$institutionid])) {
            $descrrows[] = get_string('exportevents_ics_faculty', 'mod_bookit') . ': '
                . $institutionnames[$institutionid];
        }
        if (!empty($ev->technicalneeds)) {
            $descrrows[] = get_string('exportevents_ics_requirements', 'mod_bookit') . ': ' . $ev->technicalneeds;
        }
        if (!empty($ev->participantsamount)) {
            $descrrows[] = get_string('exportevents_ics_participants', 'mod_bookit') . ': ' . $ev->participantsamount;
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

        $evlines[] = 'DESCRIPTION:' . ics_escape($descrrows ? implode("\n\n", $descrrows) : '');

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
