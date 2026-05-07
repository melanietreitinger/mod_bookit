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
            $resource = resource_manager::get_resource_by_id($rid);
            if ($resource && 1 == $resource->get_categoryid()) {
                $event->room = $rid;
            } else {
                $r = 'resource_' . $rid;
                $c = 'checkbox_' . $rid;
                $event->$r = $res->get_amount();
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
            'SELECT e.id, NULL as name, e.bookingstatus, e.starttime, e.endtime, e.extratimebefore, e.extratimeafter, r.eventcolor,
                r.name as roomname, r.shortname, r.location ' .
            'FROM {bookit_event} e ' .
            'JOIN {bookit_room} r ON r.id = e.roomid ' .
            'WHERE endtime >= :starttime AND starttime <= :endtime';

        // Service-Team: can view all events in detail.
        if ($viewalldetailsofevent) {
            $sql =
                'SELECT e.id, e.name, e.bookingstatus, e.starttime, e.endtime, e.extratimebefore, e.extratimeafter, r.eventcolor,
                     r.name as roomname, r.shortname, r.location ' .
                'FROM {bookit_event} e ' .
                'JOIN {bookit_room} r ON r.id = e.roomid ' .
                'WHERE endtime >= :starttime AND starttime <= :endtime';
            $params = ['starttime' => $starttimestamp, 'endtime' => $endtimestamp];
        } else if ($viewalldetailsofownevent) {
            $otherexaminers = $DB->sql_like('otherexaminers', ':otherexaminers');
            $otherexaminers1 = $DB->sql_like('otherexaminers', ':otherexaminers1');
            $supportpersons = $DB->sql_like('supportpersons', ':supportpersons');
            $supportpersons1 = $DB->sql_like('supportpersons', ':supportpersons1');
            // Every user: can view own events in detail.
            $sql = 'SELECT e.id, e.name, e.bookingstatus, e.starttime, e.endtime, e.extratimebefore, e.extratimeafter, r.eventcolor,
                    r.name as roomname, r.shortname, r.location
                    FROM {bookit_event} e
                    JOIN {bookit_room} r ON r.id = e.roomid
                    WHERE endtime >= :starttime1 AND starttime <= :endtime1
                    AND (e.usermodified = :usermodified1 OR personinchargeid = :personinchargeid1 OR '
                    . $otherexaminers1 . ' OR (' . $supportpersons1 . ' AND e.bookingstatus = :acceptedstatus1))
                    UNION ' . $sqlreserved . '
                    AND e.usermodified != :usermodified AND personinchargeid != :personinchargeid
                    AND NOT ' . $otherexaminers . ' AND NOT (' . $supportpersons . ' AND e.bookingstatus = :acceptedstatus)';
            $params = [
                'starttime1' => $starttimestamp,
                'endtime1' => $endtimestamp,
                'usermodified1' => $USER->id,
                'personinchargeid1' => $USER->id,
                'otherexaminers1' => $USER->id,
                'supportpersons1' => $USER->id,
                'acceptedstatus1' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
                'starttime' => $starttimestamp,
                'endtime' => $endtimestamp,
                'usermodified' => $USER->id,
                'personinchargeid' => $USER->id,
                'otherexaminers' => $USER->id,
                'supportpersons' => $USER->id,
                'acceptedstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            ];
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
            $roominfo = $record->roomname;
            $addinfos = [];
            if ($record->shortname) {
                $addinfos[] = $record->shortname;
            }
            if ($record->location) {
                $addinfos[] = $record->location;
            }
            if ($addinfos) {
                $roominfo .= ': ' . implode(', ', $addinfos);
            }

            $events[] = [
                'id' => $record->id,
                'title' => ($record->name ?? $reserved) . " ($roominfo)",
                'titleHTML' => '<h6 class="w-100 text-center">' . date('H:i', $record->starttime) . '-' .
                    date('H:i', $record->endtime) . '</h6>' .
                    ($record->name ?? $reserved) . " ($roominfo)",
                'start' => date('Y-m-d H:i', $record->starttime - $record->extratimebefore * 60),
                'end' => date('Y-m-d H:i', $record->endtime + $record->extratimeafter * 60),
                'backgroundColor' => $record->eventcolor,
                'textColor' => color_manager::get_textcolor_for_background($record->eventcolor),
                'extendedProps' => (object) [
                    'reserved' => !$record->name,
                    'bookingstatus' => (int)($record->bookingstatus ?? event_access_manager::BOOKINGSTATUS_ACCEPTED),
                    'roomname' => $record->roomname,
                ],
                'classNames' => [
                    'hide-event-time',
                    self::get_booking_status_class((int)($record->bookingstatus ?? event_access_manager::BOOKINGSTATUS_ACCEPTED)),
                ],
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

        // CSV membership checks, cross-DB using sql_concat + sql_like.
        // Wrap CSV fields with commas so we can safely search for ",<id>,".
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
                r.name AS room
            FROM {bookit_event} e
            LEFT JOIN {bookit_room} r ON r.id = e.roomid
            WHERE
                   e.personinchargeid = :uid1
                OR e.usermodified    = :uid2
                OR $otherexamcond
                OR $supportcond
            ORDER BY e.starttime ASC
        ";

        $params = [
                'uid1'     => $userid,
                'uid2'     => $userid,
                'likeuid3' => '%,' . $userid . ',%',
                'likeuid4' => '%,' . $userid . ',%',
        ];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Return all open booking requests for service-team overview.
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_open_requests(): array {
        global $DB;

        [$statussql, $params] = $DB->get_in_or_equal(event_access_manager::get_open_request_statuses(), SQL_PARAMS_NAMED);

        $sql = "
            SELECT
                e.id,
                e.name,
                e.bookingstatus,
                e.starttime,
                e.endtime,
                e.personinchargeid,
                e.otherexaminers,
                e.supportpersons,
                e.usermodified,
                r.name AS room
            FROM {bookit_event} e
            LEFT JOIN {bookit_room} r ON r.id = e.roomid
            WHERE e.bookingstatus $statussql
            ORDER BY e.starttime ASC
        ";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Return the number of open booking requests.
     *
     * @return int
     * @throws dml_exception
     */
    public static function count_open_requests(): int {
        global $DB;

        [$statussql, $params] = $DB->get_in_or_equal(event_access_manager::get_open_request_statuses(), SQL_PARAMS_NAMED);
        return (int)$DB->count_records_select('bookit_event', "bookingstatus $statussql", $params);
    }

    /**
     * Get all institutions that should appear in the filter dropdown.
     *
     * Returns an associative array  id => name  so the caller can use
     * the id as the <option> value and the name as its label.
     *
     * Includes every active institution plus any institution that is
     * already referenced by an event (even if meanwhile deactivated).
     *
     * @return array  [int institutionid => string name]
     * @throws \dml_exception
     */
    public static function get_faculties(): array {
        global $DB;

        // Active institutions from settings.
        $active = $DB->get_records('bookit_institution', ['active' => 1], 'name ASC', 'id, name');

        $result = [];
        foreach ($active as $inst) {
            $result[(int) $inst->id] = $inst->name;
        }

        // Institution IDs that are referenced by existing events.
        $fromevents = $DB->get_fieldset_sql("
            SELECT DISTINCT institutionid
              FROM {bookit_event}
             WHERE institutionid IS NOT NULL
        ");

        // Include referenced-but-inactive institutions.
        if (!empty($fromevents)) {
            [$insql, $params] = $DB->get_in_or_equal($fromevents, SQL_PARAMS_NAMED);
            $extra = $DB->get_records_select(
                'bookit_institution',
                "id $insql",
                $params,
                'name ASC',
                'id, name'
            );
            foreach ($extra as $inst) {
                if (!isset($result[(int) $inst->id])) {
                    $result[(int) $inst->id] = $inst->name;
                }
            }
        }
        // Sort by name, preserving id keys.
        asort($result, SORT_NATURAL | SORT_FLAG_CASE);
        return $result;
    }

    /**
     * Return the semester value that corresponds to the given reference time.
     *
     * Winter terms bridge the year boundary, so January through March still belong to the
     * previous winter semester.
     *
     * @param int|null $referencetime
     * @return int
     */
    public static function get_current_semester(?int $referencetime = null): int {
        $date = (new DateTime())->setTimestamp($referencetime ?? time());
        $year = (int)$date->format('Y');
        $month = (int)$date->format('n');

        if ($month >= 4 && $month <= 9) {
            return ($year * 10) + 1;
        }

        if ($month >= 10) {
            return ($year * 10) + 2;
        }

        return (($year - 1) * 10) + 2;
    }

    /**
     * Return the default reporting range for the year of the given reference time.
     *
     * @param int|null $referencetime
     * @return int[]
     */
    public static function get_reporting_default_range(?int $referencetime = null): array {
        $date = (new DateTime())->setTimestamp($referencetime ?? time());
        $year = (int)$date->format('Y');

        $start = (new DateTime())->setDate($year, 1, 1)->setTime(0, 0, 0)->getTimestamp();
        $end = (new DateTime())->setDate($year, 12, 31)->setTime(23, 59, 59)->getTimestamp();

        return [$start, $end];
    }

    /**
     * Build semester filter options around the current semester.
     *
     * @param int|null $referencetime
     * @return array
     */
    public static function get_semester_filter_options(?int $referencetime = null): array {
        $currentsemester = self::get_current_semester($referencetime);
        $baseyear = (int)floor($currentsemester / 10);
        $options = [];

        for ($year = $baseyear - 1; $year <= $baseyear + 1; $year++) {
            $options[($year * 10) + 1] = get_string('summer_semester', 'mod_bookit') . ' ' . $year;
            $options[($year * 10) + 2] = get_string('winter_semester', 'mod_bookit') . ' ' . $year;
        }

        return $options;
    }

    /**
     * Return reporting events filtered by range and semester and then reduced by the current user's access.
     *
     * @param context_module $context
     * @param int $userid
     * @param int $starttime
     * @param int $endtime
     * @param int[] $semesterids
     * @return array
     * @throws dml_exception
     */
    public static function get_events_for_reporting(
        context_module $context,
        int $userid,
        int $starttime,
        int $endtime,
        array $semesterids = []
    ): array {
        global $DB;

        $params = [
            'starttime' => $starttime,
            'endtime' => $endtime,
        ];
        $conditions = [
            'e.endtime >= :starttime',
            'e.starttime <= :endtime',
        ];

        $semesterids = array_values(array_filter(array_map('intval', $semesterids)));
        if (!empty($semesterids)) {
            [$semestersql, $semesterparams] = $DB->get_in_or_equal($semesterids, SQL_PARAMS_NAMED);
            $conditions[] = "(e.semester $semestersql OR e.semester IS NULL OR e.semester = 0)";
            $params = array_merge($params, $semesterparams);
        }

        $sql = "
            SELECT
                e.id,
                e.name,
                e.semester,
                e.bookingstatus,
                e.starttime,
                e.endtime,
                e.personinchargeid,
                e.otherexaminers,
                e.supportpersons,
                e.usermodified,
                r.name AS room
            FROM {bookit_event} e
            LEFT JOIN {bookit_room} r ON r.id = e.roomid
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY e.starttime ASC
        ";

        $records = $DB->get_records_sql($sql, $params);
        $events = [];
        foreach ($records as $record) {
            if (!event_access_manager::can_user_view_event_in_overview($record, $context, $userid)) {
                continue;
            }
            $events[] = $record;
        }

        return $events;
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

    /**
     * Return background and foreground colour for each booking status.
     *
     * @return array Keys are status int values, values are ['bg' => string, 'fg' => string].
     */
    public static function get_booking_status_colors(): array {
        return [
            0 => ['bg' => '#d3d3d3', 'fg' => '#000000'],
            1 => ['bg' => '#fff3cd', 'fg' => '#000000'],
            2 => ['bg' => '#d4edda', 'fg' => '#000000'],
            3 => ['bg' => '#343a40', 'fg' => '#ffffff'],
            4 => ['bg' => '#f8d7da', 'fg' => '#000000'],
        ];
    }

    /**
     * Return booking status options array suitable for a Mustache template dropdown.
     *
     * Each element contains 'value' (int), 'label' (string), 'selected' (bool),
     * 'bg' (string) and 'fg' (string) for colour styling.
     *
     * @param int $current Currently selected status value.
     * @return array
     */
    public static function get_booking_status_options(int $current): array {
        return self::get_booking_status_transition_options($current, true);
    }

    /**
     * Return workflow-aware booking status options.
     *
     * @param int $current
     * @param bool $includecurrent
     * @return array
     */
    public static function get_booking_status_transition_options(int $current, bool $includecurrent = false): array {
        $colors = self::get_booking_status_colors();
        $options = [];
        $allowedvalues = event_access_manager::get_allowed_booking_status_transitions($current, $includecurrent);
        foreach ($allowedvalues as $value) {
            $color = $colors[$value];
            $options[] = [
                'value'    => $value,
                'label'    => get_string('event_bookingstatus_' . $value, 'mod_bookit'),
                'selected' => ($value === $current),
                'bg'       => $color['bg'],
                'fg'       => $color['fg'],
            ];
        }
        return $options;
    }

    /**
     * Return a CSS class name for the current booking status.
     *
     * @param int $status
     * @return string
     */
    public static function get_booking_status_class(int $status): string {
        return 'bookit-bookingstatus-' . $status;
    }
}
