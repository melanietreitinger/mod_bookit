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
use core_text;
use DateTime;
use dml_exception;
use mod_bookit\event\booking_reactivated;
use mod_bookit\event\booking_status_changed;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\read\calendar_event_read_mapper;
use mod_bookit\local\read\overview_queue_read_mapper;
use mod_bookit\local\read\room_availability_read_mapper;
use stdClass;

/**
 * Manager for accessing and fetching events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * @SuppressWarnings(PHPMD)
 */
class event_manager {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
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
        $context = context_module::instance($instanceid);
        $observerrestricted = event_access_manager::is_observer_restricted_mode($context);
        $facultylabels = self::get_faculties();
        $sql = 'SELECT e.id, e.name, e.semester, e.institutionid, e.roomid, e.bookingstatus, e.starttime, e.endtime,
                    e.extratimebefore, e.extratimeafter, e.personinchargeid, e.otherexaminers, e.supportpersons,
                    e.usermodified, r.eventcolor, r.name as roomname, r.shortname, r.location
                FROM {bookit_event} e
                LEFT JOIN {bookit_room} r ON r.id = e.roomid
                WHERE endtime >= :starttime AND starttime <= :endtime
                ORDER BY starttime';
        $params = ['starttime' => $starttimestamp, 'endtime' => $endtimestamp];

        $records = $DB->get_records_sql($sql, $params);
        $events = [];

        foreach ($records as $record) {
            if (!event_access_manager::can_user_view_event_in_calendar($record, $context, (int)$USER->id)) {
                continue;
            }

            if ($observerrestricted && (int)$record->bookingstatus !== event_access_manager::BOOKINGSTATUS_ACCEPTED) {
                continue;
            }

            $events[] = self::build_calendar_read_event($record, $observerrestricted, $facultylabels);
        }
        return $events;
    }

    /**
     * Return the governed calendar/export read payload for the given user and filter set.
     *
     * @param context_module $context
     * @param int $userid
     * @param string $starttime
     * @param string $endtime
     * @param array $filters
     * @return array
     * @throws dml_exception|coding_exception
     */
    public static function get_governed_calendar_events(
        context_module $context,
        int $userid,
        string $starttime,
        string $endtime,
        array $filters = []
    ): array {
        $filters = event_access_manager::normalise_governed_read_filters(array_merge($filters, [
            'start' => $starttime,
            'end' => $endtime,
        ]));

        $events = self::get_events_in_timerange(
            $filters['start'] ?? $starttime,
            $filters['end'] ?? $endtime,
            (int)$context->instanceid
        );
        $needle = core_text::strtolower($filters['search']);

        $events = array_values(array_filter($events, static function (array $event) use ($filters, $needle): bool {
            $extendedprops = (array)($event['extendedProps'] ?? []);
            $room = (array)($extendedprops['room'] ?? []);
            $faculty = (array)($extendedprops['faculty'] ?? []);

            if (!empty($filters['roomids']) && !in_array((int)($room['roomid'] ?? 0), $filters['roomids'], true)) {
                return false;
            }

            if (!empty($filters['facultyids']) && !in_array((int)($faculty['facultyid'] ?? 0), $filters['facultyids'], true)) {
                return false;
            }

            if (
                !empty($filters['bookingstatuses']) &&
                !in_array((int)($extendedprops['bookingstatus'] ?? -1), $filters['bookingstatuses'], true)
            ) {
                return false;
            }

            if ($needle === '') {
                return true;
            }

            $haystack = core_text::strtolower(trim(implode(' ', [
                (string)($event['title'] ?? ''),
                (string)($room['roomname'] ?? ''),
                (string)($faculty['label'] ?? ''),
            ])));

            return core_text::strpos($haystack, $needle) !== false;
        }));

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
                e.institutionid,
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
     * Return the governed overview queue payload for the given workspace.
     *
     * @param context_module $context
     * @param int $userid
     * @param string $workspace
     * @param array $filters
     * @param int|null $reportstart
     * @param int|null $reportend
     * @return array
     * @throws dml_exception
     */
    public static function get_governed_overview_queue(
        context_module $context,
        int $userid,
        string $workspace,
        array $filters = [],
        ?int $reportstart = null,
        ?int $reportend = null
    ): array {
        $filters = event_access_manager::normalise_governed_read_filters(array_merge($filters, [
            'workspace' => $workspace,
        ]));
        $workspace = $filters['workspace'] ?: $workspace;

        $canmanageopenrequests = event_access_manager::can_manage_open_requests($context);
        $observerrestricted = event_access_manager::is_observer_restricted_mode($context);
        $showreportfilters = $canmanageopenrequests || $observerrestricted;

        if ($workspace === 'openrequests') {
            $events = $canmanageopenrequests ? self::get_open_requests() : [];
        } else if ($workspace === 'rejectedrequests') {
            $events = $canmanageopenrequests ? self::get_rejected_requests() : [];
        } else {
            [$defaultstart, $defaultend] = self::get_reporting_default_range();
            $reportstart ??= $defaultstart;
            $reportend ??= $defaultend;
            $semesterids = $filters['semesterids'];
            if ($showreportfilters) {
                $semesterids = self::resolve_effective_semester_filter_ids($semesterids, !empty($filters['semesterids']));
                $events = self::get_events_for_reporting($context, $userid, $reportstart, $reportend, $semesterids);
            } else {
                $events = self::get_events_for_examiner($userid);
            }
            $filters['semesterids'] = $semesterids;
            $events = self::filter_overview_events(
                $events,
                [
                    'bookingstatuses' => $filters['bookingstatuses'],
                    'facultyids' => $filters['facultyids'],
                    'semesterids' => $filters['semesterids'],
                ],
                $workspace === 'history'
            );
        }

        $historyentries = self::get_latest_booking_history_entries(array_map(
            static fn($event): int => (int)($event->id ?? 0),
            $events
        ));
        $statuscolors = self::get_booking_status_colors();

        return [
            'workspace' => $workspace,
            'filters' => $filters,
            'items' => array_map(
                static fn(stdClass $event): array => overview_queue_read_mapper::map(
                    $event,
                    $context,
                    $userid,
                    $statuscolors,
                    $historyentries[(int)$event->id] ?? null
                ),
                array_values($events)
            ),
            'summary' => [
                'count' => count($events),
                'openrequestcount' => $canmanageopenrequests ? self::count_open_requests() : 0,
                'rejectedrequestcount' => $canmanageopenrequests ? self::count_rejected_requests() : 0,
            ],
        ];
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
     * Return the default reporting semester selection.
     *
     * @param int|null $referencetime
     * @return int[]
     */
    public static function get_reporting_default_semester_ids(?int $referencetime = null): array {
        return [self::get_current_semester($referencetime)];
    }

    /**
     * Resolve the effective semester filter selection used by the overview.
     *
     * @param int[] $semesterids
     * @param bool $hasexplicitsemesterfilter
     * @param int|null $referencetime
     * @return int[]
     */
    public static function resolve_effective_semester_filter_ids(
        array $semesterids,
        bool $hasexplicitsemesterfilter,
        ?int $referencetime = null
    ): array {
        if ($hasexplicitsemesterfilter) {
            return array_values(array_map('intval', $semesterids));
        }

        return self::get_reporting_default_semester_ids($referencetime);
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
        $options = [0 => get_string('overview_filter_semester_legacy', 'mod_bookit')];

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

        $sql = "
            SELECT
                e.id,
                e.name,
                e.institutionid,
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
     * Filter overview events by active/history split and optional status, faculty and semester filters.
     *
     * @param stdClass[] $events
     * @param array $filters
     * @param bool $history
     * @param int|null $referencetime
     * @return stdClass[]
     */
    public static function filter_overview_events(
        array $events,
        array $filters = [],
        bool $history = false,
        ?int $referencetime = null
    ): array {
        $bookingstatuses = self::normalise_filter_ids($filters['bookingstatuses'] ?? []);
        $facultyids = self::normalise_filter_ids($filters['facultyids'] ?? []);
        $semesterids = self::normalise_filter_ids($filters['semesterids'] ?? []);
        $selectedsemesters = array_values(array_filter($semesterids, static fn(int $value): bool => $value !== 0));
        $includelegacysemester = in_array(0, $semesterids, true);

        return array_values(array_filter($events, static function (stdClass $event) use (
            $history,
            $referencetime,
            $bookingstatuses,
            $facultyids,
            $selectedsemesters,
            $includelegacysemester
        ): bool {
            if (self::is_event_in_history($event, $referencetime) !== $history) {
                return false;
            }

            if (!$history && self::is_hidden_from_active_overview($event)) {
                return false;
            }

            if (!empty($bookingstatuses) && !in_array((int)($event->bookingstatus ?? -1), $bookingstatuses, true)) {
                return false;
            }

            if (!empty($facultyids) && !in_array((int)($event->institutionid ?? 0), $facultyids, true)) {
                return false;
            }

            if (empty($selectedsemesters) && !$includelegacysemester) {
                return true;
            }

            $semester = (int)($event->semester ?? 0);
            if (!empty($selectedsemesters) && in_array($semester, $selectedsemesters, true)) {
                return true;
            }

            return $includelegacysemester && self::is_legacy_semester_value($event->semester ?? 0);
        }));
    }

    /**
     * Check whether an event belongs to the history view.
     *
     * @param stdClass $event
     * @param int|null $referencetime
     * @return bool
     */
    public static function is_event_in_history(stdClass $event, ?int $referencetime = null): bool {
        if ((int)($event->bookingstatus ?? -1) === event_access_manager::BOOKINGSTATUS_CANCELED) {
            return true;
        }

        return (int)($event->endtime ?? 0) < ($referencetime ?? time());
    }

    /**
     * Return all rejected requests in the dedicated service-team trash queue.
     *
     * @param int|null $referencetime Deprecated compatibility parameter, ignored.
     * @return array
     * @throws dml_exception
     */
    public static function get_rejected_requests(?int $referencetime = null): array {
        global $DB;

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
            WHERE e.bookingstatus = :status
            ORDER BY e.starttime ASC
        ";

        return $DB->get_records_sql($sql, [
            'status' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]);
    }

    /**
     * Return the number of rejected requests in the dedicated trash queue.
     *
     * @param int|null $referencetime Deprecated compatibility parameter, ignored.
     * @return int
     * @throws dml_exception
     */
    public static function count_rejected_requests(?int $referencetime = null): int {
        global $DB;

        return (int)$DB->count_records_select('bookit_event', 'bookingstatus = :status', [
            'status' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]);
    }

    /**
     * Persist a workflow history entry for the booking event.
     *
     * @param int $eventid
     * @param string $action
     * @param int $userid
     * @param int|null $oldstatus
     * @param int|null $newstatus
     * @param array $changedfields
     * @param bool $recoverymarker
     * @return int
     * @throws dml_exception
     */
    public static function record_booking_history(
        int $eventid,
        string $action,
        int $userid,
        ?int $oldstatus = null,
        ?int $newstatus = null,
        array $changedfields = [],
        bool $recoverymarker = false
    ): int {
        global $DB;

        $record = (object)[
            'eventid' => $eventid,
            'action' => $action,
            'oldstatus' => $oldstatus,
            'newstatus' => $newstatus,
            'changedfields' => empty($changedfields)
                ? null
                : json_encode($changedfields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'recoverymarker' => $recoverymarker ? 1 : 0,
            'usermodified' => $userid,
            'timecreated' => time(),
        ];

        return (int)$DB->insert_record('bookit_event_history', $record);
    }

    /**
     * Return workflow history entries for the booking event.
     *
     * @param int $eventid
     * @param int $limit
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function get_booking_history(int $eventid, int $limit = 50): array {
        global $DB;

        $limit = max(1, $limit);
        $sql = "
            SELECT h.*, u.firstname, u.lastname
              FROM {bookit_event_history} h
         LEFT JOIN {user} u ON u.id = h.usermodified
             WHERE h.eventid = :eventid
          ORDER BY h.timecreated DESC, h.id DESC
        ";

        return $DB->get_records_sql($sql, ['eventid' => $eventid], 0, $limit);
    }

    /**
     * Resolve the effective target status for a requested workflow action.
     *
     * A request for "New" on cancelled items is treated as a restore request and resolves to the
     * last valid pre-cancel status stored in the append-only history table.
     *
     * @param stdClass $event
     * @param int $requestedstatus
     * @return int|null
     * @throws dml_exception
     */
    public static function resolve_requested_booking_status(stdClass $event, int $requestedstatus): ?int {
        $currentstatus = (int)($event->bookingstatus ?? event_access_manager::BOOKINGSTATUS_NEW);
        if (
            $currentstatus === event_access_manager::BOOKINGSTATUS_CANCELED
            && $requestedstatus === event_access_manager::BOOKINGSTATUS_NEW
        ) {
            return self::resolve_restore_booking_status((int)$event->id);
        }

        return $requestedstatus;
    }

    /**
     * Resolve the last valid pre-cancel booking status for a canceled item.
     *
     * @param int $eventid
     * @return int|null
     * @throws dml_exception
     */
    public static function resolve_restore_booking_status(int $eventid): ?int {
        $history = self::get_booking_history($eventid, 50);
        foreach ($history as $entry) {
            if ((int)($entry->newstatus ?? -1) !== event_access_manager::BOOKINGSTATUS_CANCELED) {
                continue;
            }

            $oldstatus = $entry->oldstatus === null ? null : (int)$entry->oldstatus;
            if ($oldstatus !== null) {
                return $oldstatus;
            }
        }

        return null;
    }

    /**
     * Return the latest workflow history entry per event id.
     *
     * @param int[] $eventids
     * @return array<int, stdClass>
     * @throws dml_exception
     */
    public static function get_latest_booking_history_entries(array $eventids): array {
        global $DB;

        $eventids = array_values(array_unique(array_map('intval', array_filter($eventids))));
        if (empty($eventids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED);
        $sql = "
            SELECT h.*, u.firstname, u.lastname
              FROM {bookit_event_history} h
         LEFT JOIN {user} u ON u.id = h.usermodified
             WHERE h.eventid $insql
          ORDER BY h.eventid ASC, h.timecreated DESC, h.id DESC
        ";

        $entries = $DB->get_records_sql($sql, $params);
        $latest = [];
        foreach ($entries as $entry) {
            $eventid = (int)$entry->eventid;
            if (!array_key_exists($eventid, $latest)) {
                $latest[$eventid] = $entry;
            }
        }

        return $latest;
    }

    /**
     * Save a booking event while keeping visible history and audit events aligned.
     *
     * @param bookit_event $event
     * @param bookit_event|null $previousevent
     * @param int $userid
     * @param context_module $context
     * @param int|null $cmid
     * @return bookit_event
     * @throws dml_exception
     */
    public static function save_event_with_lifecycle_tracking(
        bookit_event $event,
        ?bookit_event $previousevent,
        int $userid,
        context_module $context,
        ?int $cmid = null
    ): bookit_event {
        $oldstatus = $previousevent
            ? (int)($previousevent->bookingstatus ?? event_access_manager::BOOKINGSTATUS_NEW)
            : null;

        $event->save($userid);

        $persistedevent = bookit_event::from_database((int)$event->id);
        $persistedrecord = self::get_event((int)$event->id);
        $newstatus = (int)($persistedrecord->bookingstatus ?? event_access_manager::BOOKINGSTATUS_NEW);

        if ($previousevent === null) {
            self::record_booking_history((int)$persistedrecord->id, 'created', $userid, null, $newstatus);
            if ($cmid !== null) {
                booking_notification_manager::notify_status_changed($cmid, (int)$persistedrecord->id, $newstatus, $newstatus);
            }
            self::trigger_booking_lifecycle_audit(
                $persistedrecord,
                $context,
                $userid,
                $newstatus,
                $newstatus,
                'created'
            );
            return $persistedevent;
        }

        $changedfields = self::build_booking_history_changed_fields($previousevent, $persistedevent);
        if (empty($changedfields)) {
            return $persistedevent;
        }

        $isselfcancelnew = $oldstatus !== null
            && $newstatus === event_access_manager::BOOKINGSTATUS_CANCELED
            && self::is_self_cancel_new_transition($persistedrecord, $context, $userid, $oldstatus);
        $action = $oldstatus !== $newstatus
            ? self::resolve_booking_history_action($oldstatus, $newstatus, $isselfcancelnew)
            : 'updated';
        $recoverymarker = in_array($action, ['reactivated', 'restored'], true);

        self::record_booking_history(
            (int)$persistedrecord->id,
            $action,
            $userid,
            $oldstatus,
            $newstatus,
            $changedfields,
            $recoverymarker
        );

        if ($oldstatus !== $newstatus && $cmid !== null) {
            booking_notification_manager::notify_status_changed($cmid, (int)$persistedrecord->id, $oldstatus, $newstatus);
        }

        self::trigger_booking_lifecycle_audit(
            $persistedrecord,
            $context,
            $userid,
            $oldstatus,
            $newstatus,
            $action
        );

        return $persistedevent;
    }

    /**
     * Transition an event booking status and keep notifications, history and audit events aligned.
     *
     * @param stdClass $event
     * @param int $newstatus
     * @param int $userid
     * @param context_module $context
     * @param int|null $cmid
     * @return stdClass
     * @throws dml_exception
     */
    public static function transition_booking_status(
        stdClass $event,
        int $newstatus,
        int $userid,
        context_module $context,
        ?int $cmid = null
    ): stdClass {
        global $DB;

        $oldstatus = (int)($event->bookingstatus ?? event_access_manager::BOOKINGSTATUS_NEW);
        $changed = $oldstatus !== $newstatus;

        $event->bookingstatus = $newstatus;
        $event->usermodified = $userid;
        $event->timemodified = time();
        $DB->update_record('bookit_event', $event);

        if (!$changed) {
            return $event;
        }

        $action = self::resolve_booking_history_action(
            $oldstatus,
            $newstatus,
            $newstatus === event_access_manager::BOOKINGSTATUS_CANCELED
                && self::is_self_cancel_new_transition($event, $context, $userid, $oldstatus)
        );
        $recoverymarker = in_array($action, ['reactivated', 'restored'], true);
        self::record_booking_history(
            (int)$event->id,
            $action,
            $userid,
            $oldstatus,
            $newstatus,
            ['bookingstatus' => ['from' => $oldstatus, 'to' => $newstatus]],
            $recoverymarker
        );

        if ($cmid !== null) {
            booking_notification_manager::notify_status_changed($cmid, (int)$event->id, $oldstatus, $newstatus);
        }

        self::trigger_booking_lifecycle_audit($event, $context, $userid, $oldstatus, $newstatus, $action);

        return $event;
    }

    /**
     * Check whether the event should stay hidden from active participant overviews.
     *
     * This is currently used for rejected requests and self-cancelled New requests, which remain
     * auditable via the append-only history but should leave active operational views immediately.
     *
     * @param stdClass $event
     * @return bool
     * @throws dml_exception
     */
    public static function is_hidden_from_active_overview(stdClass $event): bool {
        if ((int)($event->bookingstatus ?? -1) === event_access_manager::BOOKINGSTATUS_REJECTED) {
            return true;
        }

        if ((int)($event->bookingstatus ?? -1) !== event_access_manager::BOOKINGSTATUS_CANCELED) {
            return false;
        }

        $latest = self::get_booking_history((int)$event->id, 1);
        if (empty($latest)) {
            return false;
        }

        $entry = reset($latest);
        return (string)($entry->action ?? '') === 'self_canceled'
            && (int)($entry->oldstatus ?? -1) === event_access_manager::BOOKINGSTATUS_NEW
            && (int)($entry->newstatus ?? -1) === event_access_manager::BOOKINGSTATUS_CANCELED;
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
                    'id' => 'blocker-' . $blocker->id,
                    'title' => $blocker->name ?? '',
                    'start' => date('Y-m-d H:i', $blocker->starttime),
                    'end' => date('Y-m-d H:i', $blocker->endtime),
                    'backgroundColor' => ($blocker->roomid ?? false) ? '#c78316' : '#a33',
                    'extendedProps' => [
                        'type' => 'blocker',
                        'roomid' => $roomid,
                    ],
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
                            'id' => 'slot-' . $record->id . '-' . $eventstart,
                            'title' => '',
                            'start' => date('Y-m-d H:i', $eventstart),
                            'end' => date('Y-m-d H:i', $eventend),
                            'backgroundColor' => '',
                            'extendedProps' => [
                                'type' => 'slot',
                                'roomid' => $roomid,
                            ],
                    ];
                }

                $weekstartdt->modify('+1 week');
            }
        }
        return $events;
    }

    /**
     * Return the governed room-availability read payload.
     *
     * @param int $starttime
     * @param int $endtime
     * @param int $roomid
     * @return array
     */
    public static function get_governed_room_availability(int $starttime, int $endtime, int $roomid): array {
        return array_map(
            static fn(array $entry): array => room_availability_read_mapper::map($entry, $roomid),
            self::get_slots_in_timerange($starttime, $endtime, $roomid)
        );
    }

    /**
     * Build a canonical calendar event payload from a DB record.
     *
     * @param stdClass $record
     * @param bool $observerrestricted
     * @param array $facultylabels
     * @return array
     */
    private static function build_calendar_read_event(stdClass $record, bool $observerrestricted, array $facultylabels): array {
        $roomname = trim((string)($record->roomname ?? ''));
        if ($roomname === '') {
            $roomname = get_string('resources:all_rooms', 'mod_bookit');
        }

        $location = (string)($record->location ?? '');
        $shortname = (string)($record->shortname ?? '');
        $roominfo = $roomname;
        $addinfos = [];
        if ($shortname !== '') {
            $addinfos[] = $shortname;
        }
        if ($location !== '') {
            $addinfos[] = $location;
        }
        if ($addinfos) {
            $roominfo .= ': ' . implode(', ', $addinfos);
        }

        $title = $observerrestricted
            ? get_string('event_reserved', 'mod_bookit')
            : $record->name . " ($roominfo)";
        $titlehtml = '<h6 class="w-100 text-center">' . date('H:i', $record->starttime) . '-' .
            date('H:i', $record->endtime) . '</h6>' . $title;
        $eventcolor = trim((string)($record->eventcolor ?? '')) !== '' ? $record->eventcolor : '#3a87ad';
        $facultyid = (int)($record->institutionid ?? 0);

        return calendar_event_read_mapper::map([
            'id' => (int)$record->id,
            'title' => $title,
            'start' => date('Y-m-d H:i', $record->starttime - $record->extratimebefore * 60),
            'end' => date('Y-m-d H:i', $record->endtime + $record->extratimeafter * 60),
            'backgroundColor' => $eventcolor,
            'textColor' => color_manager::get_textcolor_for_background($eventcolor),
            'classNames' => [
                'hide-event-time',
                self::get_booking_status_class((int)$record->bookingstatus),
            ],
            'extendedProps' => [
                'titlehtml' => $titlehtml,
                'bookingstatus' => (int)$record->bookingstatus,
                'semesterid' => (int)($record->semester ?? 0),
                'visibilitymode' => $observerrestricted ? 'reserved_projection' : 'full',
                'room' => [
                    'roomid' => (int)($record->roomid ?? 0),
                    'roomname' => $roomname,
                    'location' => $location,
                    'shortname' => $shortname,
                ],
                'faculty' => [
                    'facultyid' => $facultyid,
                    'label' => (string)($facultylabels[$facultyid] ?? ''),
                ],
            ],
        ], $facultylabels);
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

    /**
     * Normalise filter ids from request payloads.
     *
     * @param mixed $values
     * @return int[]
     */
    private static function normalise_filter_ids(mixed $values): array {
        if (!is_array($values)) {
            $values = [$values];
        }

        return array_values(array_unique(array_map('intval', array_filter(
            $values,
            static fn(mixed $value): bool => $value !== '' && $value !== null
        ))));
    }

    /**
     * Check whether the semester value represents a legacy booking without an explicit semester.
     *
     * @param mixed $semester
     * @return bool
     */
    private static function is_legacy_semester_value(mixed $semester): bool {
        return empty((int)$semester);
    }

    /**
     * Build a changed-field map for visible booking history.
     *
     * @param bookit_event $before
     * @param bookit_event $after
     * @return array
     */
    private static function build_booking_history_changed_fields(bookit_event $before, bookit_event $after): array {
        $changes = [];
        $fields = [
            'name',
            'semester',
            'institutionid',
            'starttime',
            'endtime',
            'duration',
            'roomid',
            'participantsamount',
            'timecompensation',
            'compensationfordisadvantages',
            'bookingstatus',
            'personinchargeid',
            'otherexaminers',
            'coursetemplate',
            'notes',
            'internalnotes',
            'supportpersons',
            'extratimebefore',
            'extratimeafter',
            'refcourseid',
        ];

        foreach ($fields as $field) {
            $fromvalue = self::normalise_history_field_value($before->{$field});
            $tovalue = self::normalise_history_field_value($after->{$field});
            if ($fromvalue !== $tovalue) {
                $changes[$field] = ['from' => $fromvalue, 'to' => $tovalue];
            }
        }

        $fromresources = self::normalise_history_resources($before->resources);
        $toresources = self::normalise_history_resources($after->resources);
        if ($fromresources !== $toresources) {
            $changes['resources'] = ['from' => $fromresources, 'to' => $toresources];
        }

        return $changes;
    }

    /**
     * Trigger the lifecycle audit event matching the resolved booking action.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @param int $oldstatus
     * @param int $newstatus
     * @param string $action
     * @return void
     */
    private static function trigger_booking_lifecycle_audit(
        stdClass $event,
        context_module $context,
        int $userid,
        int $oldstatus,
        int $newstatus,
        string $action
    ): void {
        if ($action === 'reactivated') {
            booking_reactivated::create_from_event($event, $context, $userid, $oldstatus, $newstatus)->trigger();
            return;
        }

        booking_status_changed::create_from_event($event, $context, $userid, $oldstatus, $newstatus, $action)->trigger();
    }

    /**
     * Normalise one event field value for history comparisons.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function normalise_history_field_value(mixed $value): mixed {
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Normalise event-resource mappings for deterministic history comparisons.
     *
     * @param array $resources
     * @return array
     */
    private static function normalise_history_resources(array $resources): array {
        $normalised = [];
        foreach ($resources as $resource) {
            $normalised[] = [
                'resourceid' => (int)($resource->resourceid ?? 0),
                'amount' => (int)($resource->amount ?? 0),
            ];
        }

        usort($normalised, static function (array $left, array $right): int {
            if ($left['resourceid'] === $right['resourceid']) {
                return $left['amount'] <=> $right['amount'];
            }

            return $left['resourceid'] <=> $right['resourceid'];
        });

        return $normalised;
    }

    /**
     * Resolve the history action name for a workflow status change.
     *
     * @param int $oldstatus
     * @param int $newstatus
     * @param bool $isselfcancelnew
     * @return string
     */
    private static function resolve_booking_history_action(
        int $oldstatus,
        int $newstatus,
        bool $isselfcancelnew = false
    ): string {
        if ($isselfcancelnew) {
            return 'self_canceled';
        }

        if (
            $oldstatus === event_access_manager::BOOKINGSTATUS_CANCELED
            && in_array($newstatus, [
                event_access_manager::BOOKINGSTATUS_NEW,
                event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            ], true)
        ) {
            return 'restored';
        }

        if (
            $oldstatus === event_access_manager::BOOKINGSTATUS_REJECTED
            && $newstatus === event_access_manager::BOOKINGSTATUS_NEW
        ) {
            return 'reactivated';
        }

        return match ($newstatus) {
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS => 'moved_to_in_progress',
            event_access_manager::BOOKINGSTATUS_ACCEPTED => 'accepted',
            event_access_manager::BOOKINGSTATUS_CANCELED => 'canceled',
            event_access_manager::BOOKINGSTATUS_REJECTED => 'rejected',
            default => 'updated',
        };
    }

    /**
     * Check whether a status transition represents a requester self-cancel of a New request.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @param int $oldstatus
     * @return bool
     */
    private static function is_self_cancel_new_transition(
        stdClass $event,
        context_module $context,
        int $userid,
        int $oldstatus
    ): bool {
        if ($oldstatus !== event_access_manager::BOOKINGSTATUS_NEW) {
            return false;
        }

        $candidate = clone $event;
        $candidate->bookingstatus = $oldstatus;
        return event_access_manager::can_self_cancel_new_request($candidate, $context, $userid);
    }
}
