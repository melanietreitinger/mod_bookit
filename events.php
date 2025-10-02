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
 * Event feed for the BookIT calendar -- now with optional filters
 *
 * When no filter parameter is passed the behaviour is identical to the
 * original file: all* events in the requested time-range are returned.
 *
 * Optional GET parameters (all of them can be omitted):
 * room     (int)    → resource id of the room
 * faculty  (string) → department / faculty  (exact match)
 * status   (int)    → bookingstatus 0-4
 * search   (string) → free-text search in event name OR faculty
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_bookit\local\manager\event_manager;

require_login();           // User must be logged-in.
// ...@TODO: capability check, check for sesskey!
// ...@TODO: The id of the instance should become required in future!


$id     = optional_param('id', 0, PARAM_INT);   // Course-module id
$start  = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end    = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);

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

// WORK IN PROGRESS by vadym - new optional filter parameters (for Filter user story).
$roomid  = optional_param('room',    0,          PARAM_INT);
$faculty = optional_param('faculty', '',         PARAM_TEXT);
$status  = optional_param('status',  '',         PARAM_INT);
$search  = optional_param('search',  '',         PARAM_TEXT);


// Fetch events using the helper.
$events = event_manager::get_events_in_timerange($start, $end, $id);

// WORK IN PROGRESS by vadym: Apply in-memory filters (only if parameter present). For Filter user story.
$events = array_filter($events, function($ev) use ($roomid, $faculty, $status, $search) {

    // Room filter (resource id – note: $ev->roomid comes from event_manager).
    if ($roomid && (!isset($ev->roomid) || (int)$ev->roomid !== $roomid)) {
        return false;
    }

    // Faculty / department filter (exact match).
    if ($faculty !== '' && (!isset($ev->department) || $ev->department !== $faculty)) {
        return false;
    }

    // Status filter (0 … 4).
    if ($status !== '' && (int)$ev->bookingstatus !== (int)$status) {
        return false;
    }

    // Free-text search (case-insensitive) in name OR department.
    if ($search !== '') {
        $haystack  = strtolower(($ev->name ?? '') . ' ' .
                                ($ev->department ?? ''));
        if (!str_contains($haystack, strtolower($search))) {
            return false;
        }
    }

    return true;   // Passes all active filters.
});

// Output JSON  => wird noch erledigt.
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array_values($events));
