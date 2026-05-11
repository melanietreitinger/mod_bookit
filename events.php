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
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Event feed for the BookIt calendar with optional room, faculty, status and search filters.
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_bookit\local\manager\event_manager;

require_login();           // User must be logged-in.
// ...@TODO: capability check, check for sesskey!
// ...@TODO: The id of the instance should become required in future!


$id     = required_param('id', PARAM_INT);      // Course-module id (required).
$start  = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end    = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);
$export = optional_param('export', 0, PARAM_INT);

$cm      = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$canfilterstatus = has_capability('mod/bookit:filterstatus', $context);

// Validate and convert start and end times.
try {
    $start = new DateTime($start);
    $start = $start->format('Y-m-d H:i');
    $end = new DateTime($end);
    $end = $end->format('Y-m-d H:i');
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    exit(1);
}

// New optional filter parameters (for filter user story).
// Room and faculty now accept comma-separated values for multi-select.
$roomraw  = optional_param('room', '', PARAM_TEXT);
$roomids  = array_filter(array_map('intval', explode(',', $roomraw)));

$facultyraw = optional_param('faculty', '', PARAM_TEXT);
$faculties  = array_filter(array_map('trim', explode(',', $facultyraw)));

$search  = optional_param('search', '', PARAM_TEXT);

// Status: accept ?status= or ?bookingstatus=. Now supports comma-separated values.
$statusraw = optional_param('status', '', PARAM_RAW_TRIMMED);
if ($statusraw === '') {
    $statusraw = optional_param('bookingstatus', '', PARAM_RAW_TRIMMED);
}

$statuses = [];
if ($statusraw !== '' && $canfilterstatus) {
    $statuses = array_map('intval', explode(',', $statusraw));
}


// Fetch events using the helper.
$events = event_manager::get_events_in_timerange($start, $end, $id);

if ($export) {
    $events = array_values($events);
}

// Apply in-memory filters (only if parameter present). For Filter user story.
$events = array_filter($events, function ($ev) use ($roomids, $faculties, $statuses, $search) {
    // Helper to read from array or object.
    $get = function ($src, array $keys) {
        foreach ($keys as $k) {
            if (is_array($src) && array_key_exists($k, $src) && $src[$k] !== '' && $src[$k] !== null) {
                return $src[$k];
            }
            if (is_object($src) && isset($src->$k) && $src->$k !== '' && $src->$k !== null) {
                return $src->$k;
            }
        }
        return null;
    };

    // ROOM filter — match any of the selected room ids.
    if (!empty($roomids)) {
        $eventroomid = (int) $get($ev, ['roomid', 'resourceid', 'rid']);
        if (!in_array($eventroomid, $roomids, true)) {
            return false;
        }
    }

    // FACULTY filter — match any of the selected faculties (case-insensitive).
    if (!empty($faculties)) {
        $evdept = mb_strtolower(trim((string) ($get($ev, ['institutionid', 'faculty', 'dept']) ?? '')));
        $wanted = array_map('mb_strtolower', $faculties);
        if ($evdept === '' || !in_array($evdept, $wanted, true)) {
            return false;
        }
    }

    // STATUS filter — match any of the selected statuses.
    if (!empty($statuses)) {
        $evstatus = $get($ev, ['bookingstatus']);
        if ($evstatus === null || !in_array((int) $evstatus, $statuses, true)) {
            return false;
        }
    }

    // SEARCH filter (substring in title + institutionid, case-insensitive).
    if ($search !== '') {
        $needle = mb_strtolower($search);
        $title = (string) ($get($ev, ['title', 'name', 'summary']) ?? '');
        $dept = (string) ($get($ev, ['institutionid', 'faculty', 'dept']) ?? '');
        $haystack = mb_strtolower($title . ' ' . $dept);
        if (mb_strpos($haystack, $needle) === false) {
            return false;
        }
    }
    return true;
});

// Normalize times to ISO 8601 so week/day views render them.
$events = array_values(array_map(function ($e) {
    if (isset($e->start)) {
        $e->start = str_replace(' ', 'T', $e->start) . ':00';
    }
    if (isset($e->end)) {
        $e->end = str_replace(' ', 'T', $e->end) . ':00';
    }
    return $e;
}, $events));

// Output JSON. Debug block – visible only if ?debug=1 is passed.
header('Content-Type: application/json; charset=utf-8');

$debug = optional_param('debug', 0, PARAM_INT);

if ($debug) {
    // Debug info is added, but still output plain events array for compatibility.
    $debuginfo = [
        'input' => [
            'roomids'    => $roomids,
            'faculties'  => $faculties,
            'statuses'   => $statuses,
            'search'     => $search,
            'start'      => $start,
            'end'        => $end,
        ],
        'event_count' => count($events),
    ];
    // Send headers for developer (browser console).
    header('X-BookIt-Debug: ' . json_encode($debuginfo));
}

// Always return only the event array itself.
echo json_encode(array_values($events), JSON_UNESCAPED_UNICODE);
exit;
