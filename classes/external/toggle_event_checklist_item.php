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
 * External API for toggling event checklist item state.
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
use mod_bookit\local\entity\masterchecklist\bookit_checklist_item;
use mod_bookit\local\manager\checklist_manager;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_checklist_state_manager;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * External API for toggling event checklist item done state.
 */
class toggle_event_checklist_item extends external_api {
    /**
     * Description of parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'            => new external_value(PARAM_INT, 'Course module ID'),
            'eventid'         => new external_value(PARAM_INT, 'Event ID'),
            'checklistitemid' => new external_value(PARAM_INT, 'Checklist item ID'),
            'done'            => new external_value(PARAM_BOOL, 'New done state'),
        ]);
    }

    /**
     * Toggle the done state of an event checklist item.
     *
     * @param int $cmid Course module ID
     * @param int $eventid Event ID
     * @param int $checklistitemid Checklist item ID
     * @param bool $done New done state
     * @return array
     */
    public static function execute(int $cmid, int $eventid, int $checklistitemid, bool $done): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'            => $cmid,
            'eventid'         => $eventid,
            'checklistitemid' => $checklistitemid,
            'done'            => $done,
        ]);

        $cm = get_coursemodule_from_id('bookit', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Verify event exists before acting on it.
        $event = $DB->get_record('bookit_event', ['id' => $params['eventid']], '*', MUST_EXIST);
        $item = bookit_checklist_item::from_database($params['checklistitemid']);
        $userroleids = checklist_manager::get_user_bookit_role_ids((int)$USER->id);

        if (
            !event_access_manager::can_toggle_event_checklist_item(
                $event,
                $item->roleids,
                $context,
                (int)$USER->id,
                $userroleids
            )
        ) {
            throw new \required_capability_exception($context, 'mod/bookit:viewalldetailsofownevent', 'nopermissions', '');
        }

        event_checklist_state_manager::set_item_state(
            $params['eventid'],
            $params['checklistitemid'],
            $USER->id,
            (bool)$params['done']
        );

        return ['done' => $params['done']];
    }

    /**
     * Description of return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'done' => new external_value(PARAM_BOOL, 'Updated done state'),
        ]);
    }
}
