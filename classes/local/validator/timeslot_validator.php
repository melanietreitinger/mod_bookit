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
 * Validates timeslot settings and bookings
 *
 * @package     mod_bookit
 * @category    validator
 * @copyright   2025 Alexander Mikasch, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\validator;

defined('MOODLE_INTERNAL') || die();


class timeslot_validator {
    /**
     * Validates the start and end time of a timeslot
     *
     * @param int $starttime Unix timestamp for start time
     * @param int $endtime Unix timestamp for end time
     * @return array Array with validation result and error message
     */
    public static function validate_time_range($starttime, $endtime) {
        $min_hour = \get_config('mod_bookit', 'min_time_hour');
        $min_minute = \get_config('mod_bookit', 'min_time_minute');
        $max_hour = \get_config('mod_bookit', 'max_time_hour');
        $max_minute = \get_config('mod_bookit', 'max_time_minute');

        // Convert start and end time to hours and minutes
        $start_hour = (int)date('G', $starttime);
        $start_minute = (int)date('i', $starttime);
        $end_hour = (int)date('G', $endtime);
        $end_minute = (int)date('i', $endtime);

        // Check if start time is within allowed time range
        if ($start_hour < $min_hour || ($start_hour == $min_hour && $start_minute < $min_minute)) {
            return [
                'valid' => false,
                'error' => \get_string('error_start_time_too_early', 'mod_bookit', 
                    sprintf('%02d:%02d', $min_hour, $min_minute))
            ];
        }

        // Check if end time is within allowed time range
        if ($end_hour > $max_hour || ($end_hour == $max_hour && $end_minute > $max_minute)) {
            return [
                'valid' => false,
                'error' => \get_string('error_end_time_too_late', 'mod_bookit',
                    sprintf('%02d:%02d', $max_hour, $max_minute))
            ];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Validates the duration of a timeslot
     *
     * @param int $duration Duration in minutes
     * @return array Array with validation result and error message
     */
    public static function validate_duration($duration) {
        $min_duration = \get_config('mod_bookit', 'min_duration');
        $max_duration = \get_config('mod_bookit', 'max_duration');

        if ($duration < $min_duration) {
            return [
                'valid' => false,
                'error' => \get_string('error_duration_too_short', 'mod_bookit', $min_duration)
            ];
        }

        if ($duration > $max_duration) {
            return [
                'valid' => false,
                'error' => \get_string('error_duration_too_long', 'mod_bookit', $max_duration)
            ];
        }

        return ['valid' => true, 'error' => ''];
    }
} 