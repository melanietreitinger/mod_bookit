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
 * ICS export endpoint for BookIt events.
 *
 * @package     mod_bookit
 * @copyright   2025 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require('../../config.php');
require_once('lib.php');

use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\output\ics_exporter;

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
                        usercreated = :uid
                        OR personinchargeid = :uid2
                        OR $like
                    )";
        $params = $inparams + ['uid' => $USER->id, 'uid2' => $USER->id, 'otherex' => $USER->id];
        $events = $DB->get_records_sql($sql, $params);
    } else {
        // No details capability: nothing exportable.
        $events = [];
    }

    // Post-filters for explicit-id exports (time-range path already filters via governed read).
    if ($events && ($faculty !== '' || $status >= 0 || $room)) {
        $events = array_filter($events, static function ($event) use ($faculty, $status, $room): bool {
            if ($faculty !== '' && (string)($event->institutionid ?? '') !== $faculty) {
                return false;
            }
            if ($status >= 0 && (int)($event->bookingstatus ?? -1) !== $status) {
                return false;
            }
            if ($room && (int)($event->roomid ?? 0) !== $room) {
                return false;
            }
            return true;
        });
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
                             usercreated = :uid
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

/* ------------------------------------------------------------------
   1b. Room names via canonical event.roomid → bookit_room
   ------------------------------------------------------------------ */
$roomids = array_values(array_unique(array_filter(array_map(
    static fn($event) => (int)($event->roomid ?? 0),
    $events
))));
if (!empty($roomids)) {
    $roomrecords = $DB->get_records_list('bookit_room', 'id', $roomids, '', 'id, name, shortname, location');
    foreach ($events as $eventid => $event) {
        $roomid = (int)($event->roomid ?? 0);
        if ($roomid && isset($roomrecords[$roomid])) {
            $rec = $roomrecords[$roomid];
            $events[$eventid]->room = implode(', ', array_filter([$rec->name, $rec->shortname, $rec->location]));
        }
    }
}

/* ------------------------------------------------------------------
   2.  Build VCALENDAR via shared exporter (rich DESCRIPTION + floating local time)
   ------------------------------------------------------------------ */
foreach ($events as $event) {
    $event->bookingurl = (new moodle_url('/mod/bookit/view.php', [
        'id' => $cmid,
        'eventid' => $event->id,
    ]))->out(false);
}

$ics = ics_exporter::build($events, (string)parse_url($CFG->wwwroot, PHP_URL_HOST));
/* ------------------------------------------------------------------
   3.  Output
   ------------------------------------------------------------------ */
$filename = clean_filename('bookit-events-' . date('Ymd-His') . '.ics');
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $ics;
exit;
