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
 * External function to get BookIt configuration
 *
 * @package     mod_bookit
 * @copyright   2025 Alexander Mikasch, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_config extends \external_api {
    /**
     * Returns description of method parameters
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * Returns description of method result value
     * @return \external_single_structure
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'min_time_hour' => new \external_value(PARAM_INT, 'Früheste Startzeit (Stunde)'),
            'min_time_minute' => new \external_value(PARAM_INT, 'Früheste Startzeit (Minute)'),
            'max_time_hour' => new \external_value(PARAM_INT, 'Späteste Endzeit (Stunde)'),
            'max_time_minute' => new \external_value(PARAM_INT, 'Späteste Endzeit (Minute)'),
            'default_duration' => new \external_value(PARAM_INT, 'Standard-Zeitslotdauer in Minuten'),
            'min_duration' => new \external_value(PARAM_INT, 'Minimale Zeitslotdauer in Minuten'),
            'max_duration' => new \external_value(PARAM_INT, 'Maximale Zeitslotdauer in Minuten')
        ]);
    }

    /**
     * Get BookIt configuration
     * @return array
     */
    public static function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/bookit/lib.php');
        
        $config = get_config('mod_bookit');
        
        return [
            'min_time_hour' => (int)$config->min_time_hour,
            'min_time_minute' => (int)$config->min_time_minute,
            'max_time_hour' => (int)$config->max_time_hour,
            'max_time_minute' => (int)$config->max_time_minute,
            'default_duration' => (int)$config->default_duration,
            'min_duration' => (int)$config->min_duration,
            'max_duration' => (int)$config->max_duration
        ];
    }
} 