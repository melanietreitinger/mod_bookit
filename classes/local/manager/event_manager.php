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

use context_module;
use DateTime;
use dml_exception;

/**
 * Manager for accessing and fetching events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_manager {

    /**
     * Get event from id.
     *
     * @param int $id
     * @return false|mixed|\stdClass
     * @throws dml_exception
     */
    public static function get_event(int $id) {
        global $DB;
        $event = $DB->get_record('bookit_event', ['id' => $id]);
        $eventresources = resource_manager::get_resources_of_event($id);
        foreach ($eventresources as $rid => $amount) {
            if ($rid <= 5) {
                $event->room = $rid;
            } else {
                $r = 'resource_' . $rid;
                $c = 'checkbox_' . $rid;
                $event->$r = $amount;
                $event->$c = 1;
            }
        }
        return $event;
    }

    /**
     * function get_events_in_timerange
     *
     * @param string $starttime
     * @param string $endtime
     * @param int|null $instanceid
     * @return array
     * @throws dml_exception
     */
    public static function get_events_in_timerange(string $starttime, string $endtime, int|null $instanceid): array {
        global $DB, $USER;
        // ...@TODO use instance id.
        $starttimestamp = DateTime::createFromFormat('Y-m-d H:i', $starttime)->getTimestamp();
        $endtimestamp = DateTime::createFromFormat('Y-m-d H:i', $endtime)->getTimestamp();

        $context = context_module::instance($instanceid);
        $viewalldetailsofevent = has_capability('mod/bookit:viewalldetailsofevent', $context);
        $viewalldetailsofownevent = has_capability('mod/bookit:viewalldetailsofownevent', $context);
        $viewalldetailsofassignedevent = has_capability('mod/bookit:viewalldetailsofassignedevent', $context);
        $reserved = get_string('event_reserved', 'bookit');

        $sqlreserved = 'SELECT id, "'.$reserved.'" as name, starttime, endtime FROM {bookit_event} ' .
                'WHERE endtime >= :starttime AND starttime <= :endtime';

        // Service-Team: can view all events in detail.
        if ($viewalldetailsofevent) {
            $sql = 'SELECT id, name, starttime, endtime FROM {bookit_event} ' .
                    'WHERE endtime >= :starttime AND starttime <= :endtime';
            $params = ['starttime' => $starttimestamp, 'endtime' => $endtimestamp];
        }


        // ...@TODO: combine viewalldetailsofownevent and viewalldetailsofassignedevent!!!
        elseif ($viewalldetailsofassignedevent) {
            // Examiners: can view assigned events in detail.
            $otherexaminers1 = $DB->sql_like('otherexaminers', ':otherexaminers1');
            $otherexaminers = $DB->sql_like('otherexaminers', ':otherexaminers');
            $sql = 'SELECT id, name, starttime, endtime FROM {bookit_event} ' .
                    'WHERE endtime >= :starttime1 AND starttime <= :endtime1 
                     AND (personinchargeid = :personinchargeid1 OR '.$otherexaminers1.')
                     UNION '.$sqlreserved.' AND personinchargeid != :personinchargeid AND NOT '.$otherexaminers;
            $params = ['starttime1' => $starttimestamp, 'endtime1' => $endtimestamp,
                       'personinchargeid1' => $USER->id, 'otherexaminers1' => $USER->id,
                       'starttime' => $starttimestamp, 'endtime' => $endtimestamp,
                       'personinchargeid' => $USER->id, 'otherexaminers' => $USER->id
            ];
        }


        else if ($viewalldetailsofownevent) {
            // Every user: can view own events in detail.
            $sql = 'SELECT id, name, starttime, endtime FROM {bookit_event} ' .
                    'WHERE endtime >= :starttime1 AND starttime <= :endtime1 AND usermodified = :usermodified1
                     UNION '.$sqlreserved.' AND usermodified != :usermodified';
            $params = ['starttime1' => $starttimestamp, 'endtime1' => $endtimestamp, 'usermodified1' => $USER->id,
                       'starttime' => $starttimestamp, 'endtime' => $endtimestamp, 'usermodified' => $USER->id];
        }


        else {
            // Every user: can view no details.
            $sql = $sqlreserved;
            $params = ['starttime' => $starttimestamp, 'endtime' => $endtimestamp];
        }

        $records = $DB->get_records_sql($sql, $params);
        $events = [];
        foreach ($records as $record) {
            $events[] = [
                    'id' => $record->id,
                    'title' => $record->name,
                    'start' => date('Y-m-d H:i', $record->starttime),
                    'end' => date('Y-m-d H:i', $record->endtime),
            ];
        }
        return $events;
    }
}

