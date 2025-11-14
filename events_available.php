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
 * The endpoint for the event source request of the admin slot/blocker calendar.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann RUB
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_bookit\local\manager\event_manager;

require_admin();
$roomid = optional_param('roomid', 0, PARAM_INT);
$start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);

$room = null;
if ($roomid) {
    $room = \mod_bookit\local\persistent\room::get_record(['id' => $roomid], MUST_EXIST);
}

$start = new DateTime($start);

$end = new DateTime($end);

$events = event_manager::get_slots_in_timerange($start->getTimestamp(), $end->getTimestamp(), $roomid);
header('Content-Type: application/json');
echo json_encode($events);
