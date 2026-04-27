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
 * WORK IN PROGRESS by vadym (user story event filters)
 * Event feed for the BookIt calendar -- now with optional filters
 *
 * When no filter parameter is passed the behaviour is identical to the
 * original file: all* events in the requested time-range are returned.
 *
 * Optional GET parameters (all of them can be omitted):
 * room     (string) → comma-separated resource ids of rooms
 * faculty  (string) → comma-separated institutionIDs (exact match each)
 * status   (string) → comma-separated bookingstatus values 0-4
 * search   (string) → free-text search in event name OR faculty
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_bookit\local\entity\bookit_event;
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

// If this is an export request and the user is NOT service team,
// remove reserved events completely from the response.
if ($export && !has_capability('mod/bookit:viewalldetailsofevent', $context)) {
    $events = event_manager::strip_reserved_events($events);
}

// Enrich events with bookingstatus, institutionid, roomid and roomname.
$events = bookit_event::enrich_with_metadata($events);

// Apply in-memory filters (only if parameter present). For Filter user story.
$events = event_manager::filter_events_by_criteria($events, $roomids, $faculties, $statuses, $search);

// Normalize times to ISO 8601 so week/day views render them.
$events = event_manager::normalize_event_times_to_iso($events);

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