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

use context_module;
use mod_bookit\local\manager\event_manager;

// Import Moodle core functions
require_once($CFG->libdir . '/weblib.php');

// Import constants
use const PARAM_INT;
use const PARAM_TEXT;
use const MUST_EXIST;
use const DEBUG_DEVELOPER;

require_login();

// Get the module instance id
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('bookit', array('id' => $cm->instance), '*', MUST_EXIST);

// Check capabilities
require_capability('mod/bookit:view', context_module::instance($cm->id));

// Get the calendar parameters
$start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);

try {
    $start = new DateTime($start);
    $start = $start->format('Y-m-d H:i');

    $end = new DateTime($end);
    $end = $end->format('Y-m-d H:i');
} catch (Exception $e) {
    debugging('Error parsing dates: ' . $e->getMessage(), DEBUG_DEVELOPER);
    header('HTTP/1.0 400 Bad Request');
    die();
}

// Get events from the manager
$events = event_manager::get_events_in_timerange($start, $end, $id);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($events);
