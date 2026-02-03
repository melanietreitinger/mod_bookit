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

        $sqlreserved =
            'SELECT e.id, NULL as name, e.starttime, e.endtime, e.extratimebefore, e.extratimeafter, r.eventcolor,
                r.name as roomname, r.shortname, r.location ' .
            'FROM {bookit_event} e ' .
            'JOIN {bookit_room} r ON r.id = e.roomid ' .
            'WHERE endtime >= :starttime AND starttime <= :endtime';

        // Service-Team: can view all events in detail.
        if ($viewalldetailsofevent) {
            $sql =
                'SELECT e.id, e.name, e.starttime, e.endtime, e.extratimebefore, e.extratimeafter, r.eventcolor,
                     r.name as roomname, r.shortname, r.location ' .
                'FROM {bookit_event} e ' .
                'JOIN {bookit_room} r ON r.id = e.roomid ' .
                'WHERE endtime >= :starttime AND starttime <= :endtime';
            $params = ['starttime' => $starttimestamp, 'endtime' => $endtimestamp];
        } else if ($viewalldetailsofownevent) {
            $otherexaminers = $DB->sql_like('otherexaminers', ':otherexaminers');
            $otherexaminers1 = $DB->sql_like('otherexaminers', ':otherexaminers1');
            // Every user: can view own events in detail.
            $sql = 'SELECT e.id, e.name, e.starttime, e.endtime, e.extratimebefore, e.extratimeafter, r.eventcolor,
                    r.name as roomname, r.shortname, r.location
                    FROM {bookit_event} e
                    JOIN {bookit_room} r ON r.id = e.roomid
                    WHERE endtime >= :starttime1 AND starttime <= :endtime1
                    AND (usermodified = :usermodified1 OR personinchargeid = :personinchargeid1 OR ' . $otherexaminers1 . ')
                    UNION ' . $sqlreserved . '
                    AND usermodified != :usermodified AND personinchargeid != :personinchargeid AND NOT ' . $otherexaminers;
            $params = [
                'starttime1' => $starttimestamp,
                'endtime1' => $endtimestamp,
                'usermodified1' => $USER->id,
                'personinchargeid1' => $USER->id,
                'otherexaminers1' => $USER->id,
                'starttime' => $starttimestamp,
                'endtime' => $endtimestamp,
                'usermodified' => $USER->id,
                'personinchargeid' => $USER->id,
                'otherexaminers' => $USER->id];
        } else {
            // Every user: can view no details.
            $sql = $sqlreserved;
            $params = ['starttime' => $starttimestamp, 'endtime' => $endtimestamp];
        }

        // Order events by starttime.
        $sql .= ' ORDER BY starttime';

        $records = $DB->get_records_sql($sql, $params);
        $events = [];

        foreach ($records as $record) {
            $events[] = [
                'id' => $record->id,
                'title' => [
                    'html' => '<h6 class="w-100 text-center">' . date('H:i', $record->starttime) . '-' .
                        date('H:i', $record->endtime) . '</h6>' .
                        ($record->name ?? $reserved) . " ($record->roomname: $record->shortname, $record->location)",
                ],
                'start' => date('Y-m-d H:i', $record->starttime - $record->extratimebefore * 60),
                'end' => date('Y-m-d H:i', $record->endtime + $record->extratimeafter * 60),
                'backgroundColor' => $record->eventcolor,
                'textColor' => color_manager::get_textcolor_for_background($record->eventcolor),
                'extendedProps' => (object) ['reserved' => !$record->name],
                'classNames' => 'hide-event-time',
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
            [$insql, $paramsroom] = $DB->get_in_or_equal($roomids, SQL_PARAMS_NAMED, 'roomid');
            $roomidssql = "AND er.resourceid $insql";
        } else {
            // If no rooms exist, room will be null (this should normally not happen).
            $roomidssql = "AND 1 = 0";
            $paramsroom = [];
        }

        // 2. CSV membership checks, cross-DB using sql_concat + sql_like.
        // Wrap CSV fields with commas so we can safely search for ",<id>,"
        $otherwrapped   = $DB->sql_concat("','", "COALESCE(e.otherexaminers, '')", "','");
        $supportwrapped = $DB->sql_concat("','", "COALESCE(e.supportpersons, '')", "','");

        $otherexamcond = "(COALESCE(e.otherexaminers, '') <> '' AND " .
            $DB->sql_like($otherwrapped, ':likeuid3', false, false) . ")";

        $supportcond   = "(COALESCE(e.supportpersons, '') <> '' AND " .
            $DB->sql_like($supportwrapped, ':likeuid4', false, false) . ")";

        // 3. Main SQL query (works on all supported DBs).
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
                MIN(r.name) AS room
            FROM {bookit_event} e
            LEFT JOIN {bookit_event_resources} er
                   ON er.eventid = e.id
            LEFT JOIN {bookit_resource} r
                   ON r.id = er.resourceid
                  AND r.categoryid = 1
                  $roomidssql
            WHERE
                   e.personinchargeid = :uid1
                OR e.usermodified    = :uid2
                OR $otherexamcond
                OR $supportcond
            GROUP BY
                e.id,
                e.name,
                e.bookingstatus,
                e.starttime,
                e.personinchargeid,
                e.otherexaminers,
                e.supportpersons,
                e.usermodified
            ORDER BY e.starttime ASC
        ";

        $params = array_merge([
            'uid1'     => $userid,
            'uid2'     => $userid,
            'likeuid3' => '%,' . $userid . ',%',
            'likeuid4' => '%,' . $userid . ',%',
        ], $paramsroom);

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

        // Get institutions that already appear in events.
        $fromevents = $DB->get_fieldset_sql("
            SELECT DISTINCT institutionid
            FROM {bookit_event}
            WHERE institutionid IS NOT NULL
        ");

        // Also include active institutions from settings.
        $fromsettings = $DB->get_fieldset_sql("
            SELECT name
            FROM {bookit_institution}
            WHERE active = 1
        ");

        // Merge + normalize + sort.
        $faculties = array_unique(array_filter(array_merge($fromevents, $fromsettings)));
        sort($faculties, SORT_NATURAL | SORT_FLAG_CASE);

        return $faculties;
    }




    /**
     * Helper function to place a weekly time into a specific week.
     * It is necessary so that sunday events are the correct time, even in a week with a time change (DST).
     *
     * @param int $weeklytime Timestamp relative to start of week.
     * @param int $weektime Timestamp of the start of a week.
     * @return int
     */
    public static function place_weekly_time_into_week(int $weeklytime, int $weektime): int {
        $week = (new DateTime())->setTimestamp($weektime);
        $actual = (new DateTime())->setTimestamp($weektime + $weeklytime);
        return $actual->getTimestamp() + ($week->getOffset() - $actual->getOffset());
    }

    /**
     * Returns all slots and blockers in timerange for the specified room.
     *
     * @param int $starttime
     * @param int $endtime
     * @param int $roomid
     * @return array
     */
    public static function get_slots_in_timerange(int $starttime, int $endtime, int $roomid): array {
        global $DB;

        $events = [];

        $blockers = $DB->get_records_sql(
            'SELECT id, name, roomid, starttime, endtime FROM {bookit_blocker} ' .
            'WHERE starttime < :endtime AND endtime > :starttime AND (roomid = :roomid OR roomid IS NULL)',
            ['starttime' => $starttime, 'endtime' => $endtime, 'roomid' => $roomid],
        );
        foreach ($blockers as $blocker) {
            $events[] = [
                    'id' => $blocker->id,
                    'title' => $blocker->name ?? '',
                    'start' => date('Y-m-d H:i', $blocker->starttime),
                    'end' => date('Y-m-d H:i', $blocker->endtime),
                    'extendedProps' => (object) ['type' => 'blocker'],
                    'backgroundColor' => ($blocker->roomid ?? false) ? '#c78316' : '#a33',
            ];
        }

        $records = $DB->get_records_sql(
            'SELECT ws.id, ws.starttime as slotstart, ws.endtime as slotend,
                wr.starttime as weekplanstart, wr.endtime as weekplanend
            FROM {bookit_weekplan_room} wr
            JOIN {bookit_weekplanslot} ws ON wr.weekplanid = ws.weekplanid
            WHERE wr.starttime < :endtime AND (wr.endtime > :starttime OR wr.endtime IS NULL) AND wr.roomid = :roomid',
            [
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'roomid' => $roomid,
                ]
        );

        foreach ($records as $record) {
            $weekplanstart = max($starttime, (int) $record->weekplanstart);
            if (null == $record->weekplanend) {
                $weekplanend = $endtime;
            } else {
                $weekplanend = min($endtime, (int) $record->weekplanend + weekplan_manager::SECONDS_PER_DAY);
            }
            [$yearstart, $weekstart] = explode('-', date('Y-W', $weekplanstart));

            $weekstartdt = new DateTime("$yearstart-W$weekstart");

            while ($weekstartdt->getTimestamp() < $weekplanend) {
                $eventstart = self::place_weekly_time_into_week($record->slotstart, $weekstartdt->getTimestamp());
                $eventend = self::place_weekly_time_into_week($record->slotend, $weekstartdt->getTimestamp());

                if ($eventstart < $weekplanend && $eventend > $weekplanstart) {
                    $events[] = [
                            'id' => 0,
                            'title' => '',
                            'start' => date('Y-m-d H:i', $eventstart),
                            'end' => date('Y-m-d H:i', $eventend),
                            'extendedProps' => (object) ['type' => 'slot'],
                    ];
                }

                $weekstartdt->modify('+1 week');
            }
        }
        return $events;
    }
}
