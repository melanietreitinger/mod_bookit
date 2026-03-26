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
 * External API for updating the booking status of an event.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_bookit\local\entity\bookit_event;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * External API for updating the booking status of an event.
 */
class update_event_booking_status extends external_api {
    /**
     * Description of parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'    => new external_value(PARAM_INT, 'Course module ID'),
            'eventid' => new external_value(PARAM_INT, 'Event ID'),
            'status'  => new external_value(PARAM_INT, 'New booking status value (0-4)'),
        ]);
    }

    /**
     * Update event booking status.
     *
     * @param int $cmid Course module ID
     * @param int $eventid Event ID
     * @param int $status New booking status
     * @return array
     */
    public static function execute(int $cmid, int $eventid, int $status): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'    => $cmid,
            'eventid' => $eventid,
            'status'  => $status,
        ]);

        $cm = get_coursemodule_from_id('bookit', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/bookit:managebasics', $context);

        $validstatuses = [0, 1, 2, 3, 4];
        if (!in_array($params['status'], $validstatuses, true)) {
            throw new \invalid_parameter_exception('Invalid booking status: ' . $params['status']);
        }

        $event = bookit_event::from_database($params['eventid']);
        $event->bookingstatus = $params['status'];
        $event->save();

        return ['status' => $params['status']];
    }

    /**
     * Description of return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, 'Updated booking status value'),
        ]);
    }
}
