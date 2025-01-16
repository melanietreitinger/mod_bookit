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
 * Web service to get available rooms
 *
 * @package     mod_bookit
 * @copyright   2025 Alexander Mikasch, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');


class get_rooms extends \external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * Get list of available rooms
     *
     * @return array of rooms
     */
    public static function execute() {
        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), []);

        // Get categories from manager
        $categories = \mod_bookit\local\manager\categories_manager::get_categories();

        $result = [];
        foreach ($categories as $category) {
            if ($category['name'] === 'Rooms') {
                foreach ($category['resources'] as $room) {
                    // Parse room capacity from description (e.g. "Room capacity: 168 seats")
                    $capacity = 0;
                    if (preg_match('/capacity: (\d+)/', $room['description'], $matches)) {
                        $capacity = (int)$matches[1];
                    }
                    
                    $result[] = [
                        'id' => $room['id'],
                        'name' => $room['name'],
                        'capacity' => $capacity
                    ];
                }
                break; // Wir haben die Räume gefunden, keine weiteren Kategorien nötig
            }
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
                'id' => new \external_value(PARAM_INT, 'Room ID'),
                'name' => new \external_value(PARAM_TEXT, 'Room name'),
                'capacity' => new \external_value(PARAM_INT, 'Room capacity')
            ])
        );
    }
} 