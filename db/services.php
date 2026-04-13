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
 * Declaration of external services.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann, RUB
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_bookit_delete_blocker' => [
        'classname'   => 'mod_bookit\external\delete_blocker',
        'description' => 'Deletes a blocker.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'mod_bookit_get_possible_starttimes' => [
        'classname'   => 'mod_bookit\external\get_possible_starttimes',
        'description' => 'Gets all possible start times.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'mod_bookit_update_event_booking_status' => [
        'classname'   => 'mod_bookit\external\update_event_booking_status',
        'description' => 'Updates the booking status of an event.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'mod_bookit_update_event_resource_status' => [
        'classname'   => 'mod_bookit\external\update_event_resource_status',
        'description' => 'Updates the status of a booked resource for an event.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'mod_bookit_toggle_event_checklist_item' => [
        'classname'   => 'mod_bookit\external\toggle_event_checklist_item',
        'description' => 'Toggles the done state of a master checklist item for an event.',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
