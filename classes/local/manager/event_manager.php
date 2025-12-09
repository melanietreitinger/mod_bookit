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

use coding_exception;
use context_module;
use DateTime;
use dml_exception;
use stdClass;

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
     * @return false|mixed|stdClass
     * @throws dml_exception
     */
    public static function get_event(int $id): mixed {
        global $DB;
        $event = $DB->get_record('bookit_event', ['id' => $id]);
        $eventresources = resource_manager::get_resources_of_event($id);
        foreach ($eventresources as $rid => $res) {
            if (1 == $res->categoryid) {
                $event->room = $rid;
            } else {
                $r = 'resource_' . $rid;
                $c = 'checkbox_' . $rid;
                $event->$r = $res->amount;
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
     * @throws dml_exception|coding_exception
     */
    public static function get_events_in_timerange(string $starttime, string $endtime, int|null $instanceid): array {
        global $DB, $USER;
        // ...@TODO use instance id.
        $starttimestamp = DateTime::createFromFormat('Y-m-d H:i', $starttime)->getTimestamp();
        $endtimestamp = DateTime::createFromFormat('Y-m-d H:i', $endtime)->getTimestamp();
        $reserved = get_string('event_reserved', 'bookit');

        $context = context_module::instance($instanceid);
        $viewalldetailsofevent = has_capability('mod/bookit:viewalldetailsofevent', $context);
        $viewalldetailsofownevent = has_capability('mod/bookit:viewalldetailsofownevent', $context);

        $sqlbase = "
            SELECT
                e.id,
                e.name,
                e.starttime,
                e.endtime,
                MIN(r.name) AS roomname
            FROM {bookit_event} e
            LEFT JOIN {bookit_event_resources} er ON er.eventid = e.id
            LEFT JOIN {bookit_resource} r ON r.id = er.resourceid AND r.categoryid = 1
            WHERE e.endtime >= :starttime
            AND e.starttime <= :endtime
            GROUP BY e.id
        ";

        // Reserved name rows (no details allowed).
       $sqlreserved = "
            SELECT 
                e.id,
                NULL AS name,
                e.starttime,
                e.endtime,
                MIN(r.name) AS roomname
                FROM {bookit_event} e
                LEFT JOIN {bookit_event_resources} er ON er.eventid = e.id
                LEFT JOIN {bookit_resource} r ON r.id = er.resourceid AND r.categoryid = 1
                WHERE e.endtime >= :starttime
                AND e.starttime <= :endtime
                GROUP BY e.id
        ";

        $params = [
            'starttime' => $starttimestamp,
            'endtime'   => $endtimestamp
        ];
        
        // Capability-based SQL selection.
        if ($viewalldetailsofevent) {
            // Service-team: see all details of all events.
            $sql = $sqlbase;

        } else if ($viewalldetailsofownevent) {

            // Normal examiner: see only own events with details, everything else is reserved (no details).
            $sql = "
                SELECT DISTINCT e.id,
                    e.name,
                    e.starttime,
                    e.endtime,
                    r.name AS roomname,
                    0 AS reserved
                FROM {bookit_event} e
            LEFT JOIN {bookit_event_resources} er ON er.eventid = e.id
            LEFT JOIN {bookit_resource}        r  ON r.id       = er.resourceid
                                                AND r.categoryid = 1
                WHERE e.endtime  >= :starttime
                AND e.starttime <= :endtime
                AND (
                        e.usermodified      = :uid
                    OR e.personinchargeid  = :uid
                    OR e.otherexaminers LIKE :likeuid
                )

                UNION

                SELECT DISTINCT e.id,
                    NULL AS name,
                    e.starttime,
                    e.endtime,
                    r.name AS roomname,
                    1 AS reserved
                FROM {bookit_event} e
                LEFT JOIN {bookit_event_resources} er ON er.eventid = e.id
                LEFT JOIN {bookit_resource}        r  ON r.id       = er.resourceid
                                                    AND r.categoryid = 1
                    WHERE e.endtime  >= :starttime
                    AND e.starttime <= :endtime
                    AND NOT (
                            e.usermodified      = :uid
                        OR e.personinchargeid  = :uid
                        OR e.otherexaminers LIKE :likeuid
                    )
                ";
                $params = [
                    'starttime' => $starttimestamp,
                    'endtime'   => $endtimestamp,
                    'uid'       => $USER->id,
                    'likeuid'   => "%{$USER->id}%"
                ];
            } else {
                // Student, support, etc.: only see reserved (no details).
                $sql = $sqlreserved;
            }
        $records = $DB->get_records_sql($sql, $params);
        // Output formatting.
        $events = [];
        foreach ($records as $record) {
            $roomname = $record->roomname ?? '-';

            $events[] = [
                'id' => $record->id,
                'title' => ($record->name ?? $reserved) . ' (' . $roomname . ')',
                'start' => date('Y-m-d H:i', $record->starttime),
                'end' => date('Y-m-d H:i', $record->endtime),
                'backgroundColor' => '#333399',  
                'textColor' => '#ffffff',
                'extendedProps' => (object)['reserved' => !$record->name],
            ];
        }

        return $events;
    }
    /**
     * Fetch all events where this user participates in any role:
     * - person in charge (main examiner)
     * - other examiner
     * - booking person
     * - support person
     *
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public static function get_events_for_examiner(int $userid): array {
    global $DB;

    // 1. Collect room resource IDs.
    $resources = resource_manager::get_resources();

    $roomids = [];
    if (!empty($resources['Rooms']['resources'])) {
        $roomids = array_keys($resources['Rooms']['resources']);
    }

    // Room filter SQL.
    if (!empty($roomids)) {
        list($insql, $paramsroom) = $DB->get_in_or_equal($roomids, SQL_PARAMS_NAMED, 'roomid');
        $roomidssql = "AND er.resourceid $insql";
    } else {
        // If no rooms exist, room will be null.
        $roomidssql = "AND 1 = 0";
        $paramsroom = [];
    }

    // 2) DB-specific CSV membership checks (Postgres has no FIND_IN_SET).
    $dbfamily = $DB->get_dbfamily();

    if ($dbfamily === 'mysql') {
        $otherexamcond = "(e.otherexaminers <> '' AND FIND_IN_SET(:uid3, e.otherexaminers) > 0)";
        $supportcond   = "(e.supportpersons <> '' AND FIND_IN_SET(:uid4, e.supportpersons) > 0)";
        $params = [
            'uid1' => $userid,
            'uid2' => $userid,
            'uid3' => $userid,
            'uid4' => $userid,
        ];
    } else {
        $otherwrapped  = $DB->sql_concat(["','", "COALESCE(e.otherexaminers,'')", "','"]);
        $supportwrapped = $DB->sql_concat(["','", "COALESCE(e.supportpersons,'')", "','"]);

        $otherexamcond = "(COALESCE(e.otherexaminers,'') <> '' AND " . $DB->sql_like($otherwrapped, ':likeuid3', false, false) . ")";
        $supportcond   = "(COALESCE(e.supportpersons,'') <> '' AND " . $DB->sql_like($supportwrapped, ':likeuid4', false, false) . ")";

        $params = [
            'uid1'     => $userid,
            'uid2'     => $userid,
            'likeuid3' => "%,{$userid},%",
            'likeuid4' => "%,{$userid},%",
        ];
    }

    // 3. Main SQL query.
    $sql = "
        SELECT
            e.id,
            e.name,
            e.bookingstatus,
            e.starttime,
            e.personinchargeid,
            e.otherexaminers,
            e.supportpersons,
            e.usermodified,
            r.name AS room
        FROM {bookit_event} e
        LEFT JOIN {bookit_event_resources} er
               ON er.eventid = e.id
        LEFT JOIN {bookit_resource} r
               ON r.id = er.resourceid
              $roomidssql
        WHERE
               e.personinchargeid = :uid1
            OR e.usermodified    = :uid2
            OR $otherexamcond
            OR $supportcond
        GROUP BY e.id, e.name, e.bookingstatus, e.starttime, e.personinchargeid, e.otherexaminers, e.supportpersons, e.usermodified, r.name
        ORDER BY e.starttime ASC
    ";

    $params = array_merge($params, $paramsroom);

    return $DB->get_records_sql($sql, $params);
}

    /**
     * Get all faculties (departments) that appear in bookit events.
     *
     * @return array List of faculty names (strings).
     * @throws \dml_exception
     */
    public static function get_faculties(): array {
        global $DB;

        $sql = "SELECT DISTINCT department
                  FROM {bookit_event}
                 WHERE department IS NOT NULL
                   AND department <> ''
              ORDER BY department ASC";

        return $DB->get_fieldset_sql($sql);
    }

}
