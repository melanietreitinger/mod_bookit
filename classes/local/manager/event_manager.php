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
use function get_string;
use function has_capability;
use function optional_param;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/weblib.php');

// Constants for parameter types
use const PARAM_INT;
use const PARAM_TEXT;

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
        
        $starttimestamp = DateTime::createFromFormat('Y-m-d H:i', $starttime)->getTimestamp();
        $endtimestamp = DateTime::createFromFormat('Y-m-d H:i', $endtime)->getTimestamp();

        $context = context_module::instance($instanceid);
        $viewalldetailsofevent = has_capability('mod/bookit:viewalldetailsofevent', $context);
        $viewalldetailsofownevent = has_capability('mod/bookit:viewalldetailsofownevent', $context);
        $reserved = get_string('event_reserved', 'bookit');

        // Get filter parameters from request
        $roomid = optional_param('roomid', 0, PARAM_INT);
        $facultyid = optional_param('facultyid', 0, PARAM_INT);
        $status = optional_param('status', null, PARAM_INT);
        $search = optional_param('search', '', PARAM_TEXT);
        $timeslot = optional_param('timeslot', '', PARAM_TEXT);

        // Base WHERE clause
        $where = 'endtime >= :starttime AND starttime <= :endtime';
        $params = ['starttime' => $starttimestamp, 'endtime' => $endtimestamp];

        // Add filters
        if ($roomid) {
            $where .= ' AND id IN (SELECT eventid FROM {bookit_event_resources} WHERE resourceid = :roomid)';
            $params['roomid'] = $roomid;
        }
        if ($facultyid) {
            $where .= ' AND facultyid = :facultyid';
            $params['facultyid'] = $facultyid;
        }
        if ($status !== null && $status !== '') {
            $where .= ' AND bookingstatus = :status';
            $params['status'] = $status;
        }
        if ($search) {
            $where .= ' AND ' . $DB->sql_like('name', ':search', false);
            $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
        }
        if ($timeslot) {
            // Implement timeslot filter
            $morning_start = strtotime('07:00');
            $morning_end = strtotime('12:00');
            $afternoon_start = strtotime('12:00');
            $afternoon_end = strtotime('17:00');
            $evening_start = strtotime('17:00');
            $evening_end = strtotime('22:00');

            switch($timeslot) {
                case 'morning':
                    $where .= ' AND (TIME(FROM_UNIXTIME(starttime)) >= :slot_start AND TIME(FROM_UNIXTIME(starttime)) < :slot_end)';
                    $params['slot_start'] = date('H:i', $morning_start);
                    $params['slot_end'] = date('H:i', $morning_end);
                    break;
                case 'afternoon':
                    $where .= ' AND (TIME(FROM_UNIXTIME(starttime)) >= :slot_start AND TIME(FROM_UNIXTIME(starttime)) < :slot_end)';
                    $params['slot_start'] = date('H:i', $afternoon_start);
                    $params['slot_end'] = date('H:i', $afternoon_end);
                    break;
                case 'evening':
                    $where .= ' AND (TIME(FROM_UNIXTIME(starttime)) >= :slot_start AND TIME(FROM_UNIXTIME(starttime)) < :slot_end)';
                    $params['slot_start'] = date('H:i', $evening_start);
                    $params['slot_end'] = date('H:i', $evening_end);
                    break;
            }
        }

        // Service-Team: can view all events in detail
        if ($viewalldetailsofevent) {
            $sql = "SELECT id, name, starttime, endtime FROM {bookit_event} WHERE $where";
        } else if ($viewalldetailsofownevent) {
            $otherexaminers = $DB->sql_like('otherexaminers', ':otherexaminers');
            $otherexaminers1 = $DB->sql_like('otherexaminers', ':otherexaminers1');
            
            // Every user: can view own events in detail
            $sql = "SELECT id, name, starttime, endtime FROM {bookit_event}
                    WHERE $where AND (usermodified = :usermodified1 OR personinchargeid = :personinchargeid1 OR $otherexaminers1)
                    UNION 
                    SELECT id, '$reserved' as name, starttime, endtime FROM {bookit_event}
                    WHERE $where AND usermodified != :usermodified AND personinchargeid != :personinchargeid AND NOT $otherexaminers";
            $params = ['starttime1' => $starttimestamp, 'endtime1' => $endtimestamp,
                       'usermodified1' => $USER->id, 'personinchargeid1' => $USER->id, 'otherexaminers1' => $USER->id,
                       'starttime' => $starttimestamp, 'endtime' => $endtimestamp,
                       'usermodified' => $USER->id, 'personinchargeid' => $USER->id, 'otherexaminers' => $USER->id];
        } else {
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
                    'end' => date('Y-m-d H:i', $record->endtime)
            ];
        }
        return $events;
    }
}

