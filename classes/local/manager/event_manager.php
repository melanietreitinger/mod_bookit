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
 * Manager for accessing and fetching events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

/**
 * Manager for accessing and fetching events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_manager {

    /**
     * function get_events_in_timerange
     * @param $starttime
     * @param $endtime
     * @param $instanceid
     * @return array
     * @throws \dml_exception
     */
    public static function get_events_in_timerange($starttime, $endtime, $instanceid): array {
        global $DB;
        // ...@TODO use instance id.
        $starttimestamp = \DateTime::createFromFormat('Y-m-d H:i', $starttime)->getTimestamp();
        $endtimestamp = \DateTime::createFromFormat('Y-m-d H:i', $endtime)->getTimestamp();
        $records = $DB->get_records_sql(
                'SELECT id, name, starttime, endtime FROM {bookit_event} ' .
                'WHERE endtime >= :starttime AND starttime <= :endtime',
                ['starttime' => $starttimestamp, 'endtime' => $endtimestamp]
        );
        $events = [];
        foreach ($records as $record) {
            $events[] = [
                    'title' => $record->name,
                    'start' => date('Y-m-d H:i', $record->starttime),
                    'end' => date('Y-m-d H:i', $record->endtime),
            ];
        }
        return $events;
    }
}

