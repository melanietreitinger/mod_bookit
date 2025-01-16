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
 * Web service to get available statuses
 *
 * @package     mod_bookit
 * @copyright   2025 Alexander Mikasch, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_statuses extends \external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * Get list of available statuses
     *
     * @return array of statuses
     */
    public static function execute(): array {
        return [
            ['id' => 0, 'name' => get_string('status_new', 'mod_bookit'), 'color' => '#FFA500'],         // Orange für neue Termine
            ['id' => 1, 'name' => get_string('status_inprogress', 'mod_bookit'), 'color' => '#0000FF'],  // Blau für Termine in Bearbeitung
            ['id' => 2, 'name' => get_string('status_confirmed', 'mod_bookit'), 'color' => '#008000'],    // Grün für bestätigte Termine
            ['id' => 3, 'name' => get_string('status_cancelled', 'mod_bookit'), 'color' => '#808080'],    // Grau für stornierte Termine
            ['id' => 4, 'name' => get_string('status_rejected', 'mod_bookit'), 'color' => '#FF0000']      // Rot für abgelehnte Termine
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function execute_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'Status ID'),
                'name' => new \external_value(PARAM_TEXT, 'Status name'),
                'color' => new \external_value(PARAM_TEXT, 'Status color (hex)')
            ])
        );
    }
} 