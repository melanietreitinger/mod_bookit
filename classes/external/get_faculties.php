<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Web service to get available faculties/departments
 *
 * @package     mod_bookit
 * @copyright   2025 Alexander Mikasch, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');


class get_faculties extends \external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * Get list of available faculties/departments
     *
     * @return array of faculties
     */
    public static function execute() {
        global $DB;

        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), []);

        // Get all unique departments from bookit_event table
        $sql = "SELECT DISTINCT department 
                FROM {bookit_event} 
                ORDER BY department ASC";
        $departments = $DB->get_records_sql($sql);

        $result = [];
        foreach ($departments as $department) {
            $result[] = [
                'id' => md5($department->department), // Generate a unique ID based on the name
                'name' => $department->department,
                'code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $department->department), 0, 10)) // Generate a code from the name
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function execute_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_TEXT, 'Department ID'),
                'name' => new \external_value(PARAM_TEXT, 'Department name'),
                'code' => new \external_value(PARAM_TEXT, 'Department code')
            ])
        );
    }
} 