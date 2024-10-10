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
 * The endpoint for the event source request of the calendar component.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

use mod_bookit\local\manager\event_manager;

require_login();
// ...@TODO: capability check, check for sesskey!
// ...@TODO: The id of the instance should become required in future!
$id = optional_param('id', 0, PARAM_INT);
$start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);
try {
    $start = new DateTime($start);
    $start = $start->format('Y-m-d H:i');

    $end = new DateTime($end);
    $end = $end->format('Y-m-d H:i');
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
$events = event_manager::get_events_in_timerange($start, $end, $id);
header('Content-Type: application/json');
echo json_encode($events);
