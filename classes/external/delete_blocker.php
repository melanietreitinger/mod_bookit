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
 * External API for deleting a blocker.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_bookit\local\persistent\blocker;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * External API for deleting a blocker.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_blocker extends external_api {
    /**
     * Description for delete_blocker parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'blockerid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Execution for delete_blocker external api.
     * @param int $blockerid
     * @return array
     */
    public static function execute(int $blockerid): array {
        [
            'blockerid' => $blockerid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'blockerid' => $blockerid,
        ]);
        $context = \context_system::instance();
        self::validate_context($context);

        require_capability('mod/bookit:managemasterchecklist', $context); // TODO: use other capability.

        $blocker = blocker::get_record(['id' => $blockerid], MUST_EXIST);
        $blocker->delete();

        return [];
    }

    /**
     * Description of delete_blocker return value.
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
