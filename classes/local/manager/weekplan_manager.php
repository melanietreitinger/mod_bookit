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
 * Weekplan manager class.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann, RUB
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\manager;

/**
 * Weekplan manager class.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann, RUB
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class weekplan_manager {

    /** @var string[] Array of Weekdays. */
    const WEEKDAYS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

    /** @var int[] Like WEEKDAYS, but flipped. */
    const INDEXED_WEEKDAYS = [
        "mo" => 0,
        "di" => 1,
        "mi" => 2,
        "do" => 3,
        "fr" => 4,
        "sa" => 5,
        "so" => 6,
    ];

    /** @var int How many seconds there are in one day. */
    const SECONDS_PER_DAY = 24 * 60 * 60;

    /**
     * Parses a string detailing a week plan into an array of slots (start and end time relative to start of week).
     * @param string $weekplan
     * @return array
     */
    private static function parse_weekplan(string $weekplan) {
        $lines = explode("\n", strtolower($weekplan));
        $parsedperiods = [];
        foreach ($lines as $line) {
            try {
                $line = trim($line);
                $dayofweek = substr($line, 0, 2);
                if (!array_key_exists($dayofweek, self::INDEXED_WEEKDAYS)) {
                    continue;
                }
                $dayofweekindex = self::INDEXED_WEEKDAYS[$dayofweek];

                $timeperiods = trim(substr($line, 2));
                $timeperiods = str_replace(' ', '', $timeperiods);

                foreach (explode(",", $timeperiods) as $timeperiod) {
                    try {
                        list($starttime, $endtime) = explode('-', $timeperiod);
                        $parsedperiods[] = [
                            self::parse_time($starttime) + self::SECONDS_PER_DAY * $dayofweekindex,
                            self::parse_time($endtime) + self::SECONDS_PER_DAY * $dayofweekindex,
                        ];
                    } catch (\Exception $e) {
                        // That's ok, we'll just skip the period.
                    }
                }
            } catch (\Exception $e) {
                // That's ok, we'll just skip the line.
            }
        }
        return $parsedperiods;
    }

    /**
     * Parses a time string like 8:12, 13 or 09:25 into a daytimestamp (seconds since start of day).
     * @param string $time
     * @return int
     */
    private static function parse_time(string $time) {
        if (str_contains($time, ':')) {
            list($hour, $minute) = explode(':', $time);
            $hour = intval($hour);
            $minute = intval($minute);
        } else {
            $hour = intval($time);
            $minute = 0;
        }
        return $hour * 3600 + $minute * 60;
    }

    /**
     * Returns a string representation (H:MM) for a given daytimestamp (seconds since start of day).
     * @param int $time
     * @return string
     */
    public static function daytime_to_str(int $time) {
        $seconds = $time % 60;
        $time = intdiv($time, 60);
        $minutes = $time % 60;
        $hours = intdiv($time, 60);
        return sprintf("%d:%02d", $hours, $minutes);
    }

    /**
     * Given an array of records of bookit_weekplanslot, this returns an array of strings (H:MM-H:MM) for each of the weekdays.
     * @param array $weekplan
     * @return array Array in the form of [weekdayindex => string[]].
     */
    public static function group_events_by_day(array $weekplan) {
        $eventsbyday = [];
        foreach ($weekplan as $event) {
            $weekdayindex = intdiv($event->starttime, self::SECONDS_PER_DAY);
            $daystarttime = $event->starttime % self::SECONDS_PER_DAY;
            $dayendtime = $event->endtime % self::SECONDS_PER_DAY;
            if (!isset($eventsbyday[$weekdayindex])) {
                $eventsbyday[$weekdayindex] = [];
            }
            $eventsbyday[$weekdayindex][] = self::daytime_to_str($daystarttime) . "-" . self::daytime_to_str($dayendtime);
        }
        ksort($eventsbyday);
        return $eventsbyday;
    }

    /**
     * Transforms an array of records of bookit_weekplanslot into a string for human editing.
     * @param array $weekplan
     * @return string
     */
    private static function weekplan_to_string(array $weekplan) {
        $eventsbyday = self::group_events_by_day($weekplan);
        $result = "";
        foreach ($eventsbyday as $weekdayindex => $events) {
            $result .= self::WEEKDAYS[$weekdayindex] . ' ' . join(", ", $events) . "\n";
        }
        return $result;
    }

    /**
     * Returns a textual representation for human editing of the weekplan with the specified id.
     * @param int $weekplanid
     * @return string
     */
    public static function create_string_weekplan_from_db(int $weekplanid) {
        global $DB;
        $records = $DB->get_records('bookit_weekplanslot', ['weekplanid' => $weekplanid]);
        return self::weekplan_to_string($records);
    }

    /**
     * Saves the weekplan to the DB based on the given textual representation.
     * @param int $weekplanid
     * @return string
     */
    public static function save_string_weekplan_to_db(string $weekplan, int $weekplanid) {
        global $DB;
        $DB->delete_records('bookit_weekplanslot', ['weekplanid' => $weekplanid]); // Awful.

        $weekplanevents = self::parse_weekplan($weekplan);
        foreach ($weekplanevents as $weekplanevent) {
            $DB->insert_record('bookit_weekplanslot', [
                'weekplanid' => $weekplanid,
                'starttime' => $weekplanevent[0],
                'endtime' => $weekplanevent[1],
            ]);
        }
    }

}
