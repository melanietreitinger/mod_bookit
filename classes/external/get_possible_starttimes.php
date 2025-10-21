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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use DateTime;
use mod_bookit\local\bool_timeline;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\weekplan_manager;
use mod_bookit\local\persistent\blocker;
use mod_bookit\local\persistent\room;
use mod_bookit\local\persistent\weekplan_room;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * External API for getting possible slots.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_possible_starttimes extends external_api {
    /**
     * Description for get_possible_slots parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'year' => new external_value(PARAM_INT),
            'month' => new external_value(PARAM_INT),
            'day' => new external_value(PARAM_INT),
            'duration' => new external_value(PARAM_INT),
            'roomid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Lists all possible starttimes for that day, duration and room.
     * @param DateTime $date
     * @param int $duration
     * @param int $roomid
     * @return array Associative array of [Timestamp => Time string].
     */
    public static function list_possible_starttimes(DateTime $date, int $duration, int $roomid): array {
        $room = room::get_record(['id' => $roomid], MUST_EXIST);

        $timestamp = $date->getTimestamp();

        $weekplanid = weekplan_room::get_applicable_weekplanid($timestamp, $roomid);
        if (!$weekplanid || !$room->get('active')) {
            return [];
        }

        // String 'N' gets 1 for Monday through 7 for Sunday.
        $weekday = (int) $date->format('N') - 1;
        $weekstarttime = $timestamp - weekplan_manager::SECONDS_PER_DAY * $weekday;
        $slots = weekplan_manager::get_weekplanslots_for_weekday($weekplanid, $weekday);
        $blockers = blocker::get_blockers_for_room($roomid, $timestamp, $timestamp + weekplan_manager::SECONDS_PER_DAY);

        $timeline = new bool_timeline(false);

        $extratimebefore = get_config('mod_bookit', 'extratimebefore');
        $extratimeafter = get_config('mod_bookit', 'extratimeafter');

        foreach ($slots as $slot) {
            $slot->starttime = event_manager::place_weekly_time_into_week($slot->starttime - $extratimebefore, $weekstarttime);
            $slot->endtime = event_manager::place_weekly_time_into_week($slot->endtime + $extratimeafter, $weekstarttime);
            $timeline->set_range($slot->starttime, $slot->endtime, true);
        }

        foreach ($blockers as $blocker) {
            $timeline->set_range($blocker->get('starttime'), $blocker->get('endtime'), false);
        }

        $starttimes = [];
        $freemodegrid = get_config('mod_bookit', 'eventstartstepwidth') * 60;

        foreach ($slots as $slot) {
            if ($room->get('roommode') == room::MODE_FREE) {
                $offset = $slot->starttime % $freemodegrid;
                if ($offset != 0) {
                    $slot->starttime += $freemodegrid - $offset;
                }
                for ($time = $slot->starttime; $time <= $slot->endtime; $time += $freemodegrid) {
                    if ($timeline->does_complete_range_equal($time, $time + $duration * 60, true)) {
                        $starttimes[$time] = (new DateTime())->setTimestamp($time)->format("H:i");
                    }
                }
            } else {
                if ($timeline->does_complete_range_equal($slot->starttime, $slot->starttime + $duration * 60, true)) {
                    $starttimes[$slot->starttime] = (new DateTime())->setTimestamp($slot->starttime)->format("H:i");
                }
            }
        }

        return $starttimes;
    }

    /**
     * Execution for get_possible_slots external api.
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $duration
     * @param int $roomid
     * @return array
     */
    public static function execute(int $year, int $month, int $day, int $duration, int $roomid): array {
        [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'duration' => $duration,
            'roomid' => $roomid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'duration' => $duration,
            'roomid' => $roomid,
        ]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('mod/bookit:addevent', $context);

        $date = new \DateTime("now");
        $date->setTime(0, 0);
        $date->setDate($year, $month, $day);

        $starttimes = self::list_possible_starttimes($date, $duration, $roomid);
        $transformed = [];

        foreach ($starttimes as $starttime => $starttimestring) {
            $transformed[] = [
                "timestamp" => $starttime,
                "string" => $starttimestring,
            ];
        }

        return $transformed;
    }

    /**
     * Description of get_possible_slots return value.
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'timestamp' => new external_value(PARAM_INT),
                'string' => new external_value(PARAM_TEXT),
            ]),
        );
    }
}
