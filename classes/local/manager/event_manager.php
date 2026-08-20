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
use context_course;
use context_module;
use core\context;
use core_text;
use core_user\fields;
use DateTime;
use dml_exception;
use mod_bookit\event\booking_reactivated;
use mod_bookit\event\booking_status_changed;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\read\calendar_event_read_mapper;
use mod_bookit\local\read\room_availability_read_mapper;
use mod_bookit\local\tabs;
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
     * @var string[] Role shortnames whose holders may be assigned as support persons of an event.
     */
    public const SUPPORT_PERSON_ROLES = [
        'bookit_serviceteam',
        'bookit_supportonsite',
    ];

    /**
     * Get the users that can be assigned as support persons of an event.
     *
     * Returns an associative array  userid => fullname  so the caller can use it directly as the
     * options of a select or autocomplete element.
     *
     * Only users holding one of the SUPPORT_PERSON_ROLES are returned. Role assignments are taken
     * into account for the given context and all of its parents, because the BookIt roles may be
     * assigned at module, course, category, or system level.
     *
     * @param context $context Course or module context the event belongs to.
     * @param string $selectedids Comma-separated user IDs already stored on the event. These are
     *      always included, even if the user lost the role meanwhile, so that the form does not
     *      silently drop them.
     * @return array [int userid => string fullname]
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_support_person_candidates(context $context, string $selectedids = ''): array {
        global $DB;

        [$roleinsql, $params] = $DB->get_in_or_equal(self::SUPPORT_PERSON_ROLES, SQL_PARAMS_NAMED, 'rsn');
        [$ctxinsql, $ctxparams] = $DB->get_in_or_equal(
            $context->get_parent_context_ids(true),
            SQL_PARAMS_NAMED,
            'ctx'
        );
        $params += $ctxparams;

        $where = "u.suspended = 0 AND r.shortname $roleinsql AND ra.contextid $ctxinsql";

        $selected = array_filter(array_map('intval', explode(',', $selectedids)));
        if ($selected) {
            [$selinsql, $selparams] = $DB->get_in_or_equal($selected, SQL_PARAMS_NAMED, 'sel');
            $params += $selparams;
            $where = "($where) OR u.id $selinsql";
        }

        // DISTINCT is required because a user may hold both roles, or the same role in several
        // contexts. Selecting u.* instead of the name fields would break DISTINCT on databases
        // that cannot compare TEXT columns.
        $namefields = fields::for_name()->get_sql('u')->selects;
        $sql = "SELECT DISTINCT u.id $namefields
                  FROM {user} u
             LEFT JOIN {role_assignments} ra ON ra.userid = u.id
             LEFT JOIN {role} r ON r.id = ra.roleid
                 WHERE u.deleted = 0
                   AND ($where)
              ORDER BY u.lastname, u.firstname";

        $candidates = [];
        foreach ($DB->get_records_sql($sql, $params) as $id => $user) {
            $candidates[(int) $id] = fullname($user);
        }
        return $candidates;
    }

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
            $amountfield = 'resource_' . $rid;
            $checkboxfield = 'checkbox_' . $rid;
            $event->$amountfield = $res->get_amount();
            $event->$checkboxfield = 1;
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
                    e.usercreated, e.usermodified, r.eventcolor, r.name as roomname, r.shortname, r.location
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

            if ($observerrestricted && (int)$record->bookingstatus !== event_access_manager::BOOKINGSTATUS_CONFIRMED) {
                continue;
            }

            $events[] = self::build_calendar_read_event($record, $observerrestricted, $facultylabels, $context, (int)$USER->id);
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

        if (
            !empty($filters['exportmode'])
            && in_array(event_access_manager::BOOKINGSTATUS_CANCELED, $filters['bookingstatuses'] ?? [], true)
            && (
                event_access_manager::can_manage_open_requests($context)
                || has_capability('mod/bookit:viewalldetailsofevent', $context, $userid)
            )
        ) {
            $events = self::merge_service_team_canceled_export_events(
                $events,
                $filters['start'] ?? $starttime,
                $filters['end'] ?? $endtime,
                $context
            );
        }

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
                e.usercreated,
                e.usermodified,
                r.name AS room
            FROM {bookit_event} e
            LEFT JOIN {bookit_room} r ON r.id = e.roomid
            WHERE
                   e.personinchargeid = :uid1
                OR e.usercreated    = :uid2
                OR $otherexamcond
                OR $supportcond
            ORDER BY e.starttime DESC, e.id DESC
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
     * Build the governed SQL contract used by the request workspace Core table.
     *
     * The returned scope is complete before Core applies count, sorting, or paging.
     *
     * @param context_module $context
     * @param int $userid
     * @param string $workspace
     * @param array $filters
     * @param int|null $reportstart
     * @param int|null $reportend
     * @return array{fields:string,from:string,where:string,params:array,countsql:string,countparams:array}
     */
    public static function get_request_workspace_query(
        context_module $context,
        int $userid,
        string $workspace,
        array $filters = [],
        ?int $reportstart = null,
        ?int $reportend = null
    ): array {
        global $DB;

        $workspace = tabs::validate_workspace_tab($workspace);
        if (!event_access_manager::can_view_request_workspace($context)) {
            throw new \required_capability_exception($context, 'mod/bookit:viewownoverview', 'nopermissions', '');
        }

        $filters = event_access_manager::normalise_governed_read_filters(array_merge($filters, [
            'workspace' => $workspace,
        ]));
        $assignmentfilter = $filters['assignmentfilter'] === 'assigned' ? 'assigned' : 'all';
        $params = [];
        $conditions = [];

        if ($workspace === 'openrequests') {
            [$statussql, $statusparams] = $DB->get_in_or_equal(
                event_access_manager::get_open_request_statuses(),
                SQL_PARAMS_NAMED,
                'openstatus'
            );
            $conditions[] = "e.bookingstatus $statussql";
            $params += $statusparams;
            [$conditions, $params] = self::append_queue_reporting_filters(
                $conditions,
                $params,
                $filters,
                $reportstart,
                $reportend
            );
        } else if ($workspace === 'confirmedrequests') {
            $conditions[] = 'e.bookingstatus = :confirmedstatus';
            $conditions[] = 'e.endtime >= :confirmedreferencetime';
            $params['confirmedstatus'] = event_access_manager::BOOKINGSTATUS_CONFIRMED;
            $params['confirmedreferencetime'] = time();
            [$conditions, $params] = self::append_queue_reporting_filters(
                $conditions,
                $params,
                $filters,
                $reportstart,
                $reportend
            );
        } else if ($workspace === 'rejectedcancelled') {
            $conditions[] = self::get_rejected_requests_where_sql();
            $params += self::get_rejected_requests_params();
            [$conditions, $params] = self::append_queue_reporting_filters(
                $conditions,
                $params,
                $filters,
                $reportstart,
                $reportend
            );
            $rejectedstatuses = array_values(array_intersect(
                self::normalise_filter_ids($filters['bookingstatuses'] ?? []),
                self::get_rejectedcancelled_default_booking_status_filter()
            ));
            // Both (or empty after normalise) ⇒ membership SQL alone; one status ⇒ narrow.
            if (count($rejectedstatuses) === 1) {
                [$statussql, $statusparams] = $DB->get_in_or_equal(
                    $rejectedstatuses,
                    SQL_PARAMS_NAMED,
                    'rejstatus'
                );
                $conditions[] = "e.bookingstatus $statussql";
                $params += $statusparams;
            }
        } else {
            [$defaultstart, $defaultend] = self::get_reporting_default_range(
                null,
                true,
                $workspace === 'history'
            );
            $reportstart ??= $defaultstart;
            $reportend ??= $defaultend;
            $semesterids = self::resolve_effective_semester_filter_ids(
                $filters['semesterids'],
                !empty($filters['semesterids'])
            );
            $bookingstatuses = self::resolve_overview_booking_status_filter_ids(
                $filters['bookingstatuses'],
                !empty($filters['bookingstatuses']),
                $workspace === 'history',
                $workspace === 'history'
            );
            [$where, $reportparams] = self::build_reporting_overview_sql_filter(
                $workspace,
                $filters,
                $reportstart,
                $reportend,
                $semesterids,
                $bookingstatuses
            );
            $conditions[] = $where;
            $params += $reportparams;
        }

        if ($assignmentfilter === 'assigned') {
            [$assignmentsql, $assignmentparams] = self::build_assigned_involvement_sql($userid);
            $conditions[] = $assignmentsql;
            $params += $assignmentparams;
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $searchfields = [
                'e.name',
                "COALESCE(r.name, '')",
                "COALESCE(pic.firstname, '')",
                "COALESCE(pic.lastname, '')",
                "COALESCE(creator.firstname, '')",
                "COALESCE(creator.lastname, '')",
            ];
            $searchconditions = [];
            foreach ($searchfields as $index => $field) {
                $paramname = 'workspacesearch' . $index;
                $searchconditions[] = $DB->sql_like($field, ':' . $paramname, false, false);
                $params[$paramname] = '%' . $DB->sql_like_escape($search) . '%';
            }
            $conditions[] = '(' . implode(' OR ', $searchconditions) . ')';
        }

        $from = '{bookit_event} e
            LEFT JOIN {bookit_room} r ON r.id = e.roomid
            LEFT JOIN {user} pic ON pic.id = e.personinchargeid
            LEFT JOIN {user} creator ON creator.id = e.usercreated';
        $where = $conditions ? implode(' AND ', $conditions) : '1 = 1';
        $fields = "e.id,
            e.name AS title,
            e.name,
            e.institutionid,
            e.semester,
            e.bookingstatus,
            e.starttime,
            e.endtime,
            e.personinchargeid,
            e.otherexaminers,
            e.supportpersons,
            e.usercreated,
            e.usermodified,
            creator.firstname AS creatorfirstname,
            creator.lastname AS creatorlastname,
            creator.deleted AS creatordeleted,
            COALESCE(r.name, '') AS room,
            " . $DB->sql_fullname('pic.firstname', 'pic.lastname') . " AS personincharge,
            " . $DB->sql_fullname('creator.firstname', 'creator.lastname') . ' AS createdby';

        return [
            'fields' => $fields,
            'from' => $from,
            'where' => $where,
            'params' => $params,
            'countsql' => "SELECT COUNT(1) FROM $from WHERE $where",
            'countparams' => $params,
        ];
    }

    /**
     * Return the number of open booking requests.
     *
     * @return int
     * @throws dml_exception
     */
    public static function count_open_requests(): int {
        return self::count_new_requests() + self::count_in_progress_requests();
    }

    /**
     * Return the number of open booking requests in status New.
     *
     * @return int
     * @throws dml_exception
     */
    public static function count_new_requests(): int {
        global $DB;

        return (int)$DB->count_records('bookit_event', [
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]);
    }

    /**
     * Return the number of open booking requests in status In progress.
     *
     * @return int
     * @throws dml_exception
     */
    public static function count_in_progress_requests(): int {
        global $DB;

        return (int)$DB->count_records('bookit_event', [
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
        ]);
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
     * @param bool $serviceteam
     * @param bool $ishistorytab
     * @param bool $isrejectedcancelledtab Rejected and cancelled uses full calendar year for service team.
     * @return int[]
     */
    public static function get_reporting_default_range(
        ?int $referencetime = null,
        bool $serviceteam = false,
        bool $ishistorytab = false,
        bool $isrejectedcancelledtab = false
    ): array {
        $timestamp = $referencetime ?? time();
        $date = (new DateTime())->setTimestamp($timestamp);
        $year = (int)$date->format('Y');
        $yearstart = (new DateTime())->setDate($year, 1, 1)->setTime(0, 0, 0)->getTimestamp();
        $yearend = (new DateTime())->setDate($year, 12, 31)->setTime(23, 59, 59)->getTimestamp();

        if ($serviceteam && $isrejectedcancelledtab) {
            $start = $yearstart;
            $end = $yearend;
        } else if ($serviceteam && $ishistorytab) {
            $start = $yearstart;
            $end = (new DateTime())->setTimestamp(usergetmidnight($timestamp))->setTime(23, 59, 59)->getTimestamp();
        } else if ($serviceteam) {
            $start = usergetmidnight($timestamp);
            $end = $yearend;
        } else {
            $start = $yearstart;
            $end = $yearend;
        }

        return [$start, $end];
    }

    /**
     * Return the default reporting status filter for service-team overview loads.
     *
     * @return int[]
     */
    public static function get_reporting_default_booking_status_filter(): array {
        return [
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
        ];
    }

    /**
     * Return the default history-tab status filter for Request-Workspace reporting.
     *
     * All five booking statuses are selected so History is not limited to terminal statuses.
     *
     * @return int[]
     */
    public static function get_history_default_booking_status_filter(): array {
        return [
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            event_access_manager::BOOKINGSTATUS_REJECTED,
        ];
    }

    /**
     * Return the default Rejected-and-cancelled tab status filter (Canceled + Rejected only).
     *
     * @return int[]
     */
    public static function get_rejectedcancelled_default_booking_status_filter(): array {
        return [
            event_access_manager::BOOKINGSTATUS_CANCELED,
            event_access_manager::BOOKINGSTATUS_REJECTED,
        ];
    }

    /**
     * Resolve overview booking status filter ids from request payload.
     *
     * @param int[] $rawids Raw status ids from bookingstatusfilter[].
     * @param bool $hasexplicitfilter Whether the request included bookingstatusfilter.
     * @param bool $ishistorytab Whether the active overview tab is History.
     * @param bool $isreportinghistory Whether the user is on the service-team reporting History path.
     * @param bool $isrejectedcancelledtab Whether the active tab is Rejected and cancelled.
     * @return int[]
     */
    public static function resolve_overview_booking_status_filter_ids(
        array $rawids,
        bool $hasexplicitfilter,
        bool $ishistorytab = false,
        bool $isreportinghistory = false,
        bool $isrejectedcancelledtab = false
    ): array {
        if ($isrejectedcancelledtab) {
            $allowed = self::get_rejectedcancelled_default_booking_status_filter();
            $default = $allowed;

            if (!$hasexplicitfilter) {
                return $default;
            }

            $normalised = self::normalise_filter_ids($rawids);
            if ($normalised === []) {
                return $default;
            }

            $filtered = array_values(array_intersect($normalised, $allowed));
            return $filtered !== [] ? $filtered : $default;
        }

        $allowed = [
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            event_access_manager::BOOKINGSTATUS_REJECTED,
        ];

        if ($ishistorytab) {
            $default = $isreportinghistory
                ? self::get_history_default_booking_status_filter()
                : [];
        } else {
            $default = self::get_reporting_default_booking_status_filter();
        }

        if (!$hasexplicitfilter) {
            return $default;
        }

        $normalised = self::normalise_filter_ids($rawids);
        if ($normalised === []) {
            return $default;
        }

        $filtered = array_values(array_intersect($normalised, $allowed));
        return $filtered !== [] ? $filtered : $default;
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
        $options = [];

        for ($year = $baseyear - 1; $year <= $baseyear + 1; $year++) {
            $options[($year * 10) + 1] = get_string('summer_semester', 'mod_bookit') . ' ' . $year;
            $options[($year * 10) + 2] = get_string('winter_semester', 'mod_bookit') . ' ' . $year;
        }

        return $options;
    }

    /**
     * Return the filter profile for a Request Workspace tab (service team only).
     *
     * @param string $workspacetab Canonical workspace tab slug.
     * @param bool $serviceteam Whether the viewer is on the service-team workspace path.
     * @return array{
     *     show_reporting_filters: bool,
     *     show_status_filter: bool,
     *     show_semester_filter: bool,
     *     show_assignment_filter: bool
     * }
     */
    public static function get_workspace_filter_profile(string $workspacetab, bool $serviceteam): array {
        if (!$serviceteam) {
            return [
                'show_reporting_filters' => true,
                'show_status_filter' => true,
                'show_semester_filter' => true,
                'show_assignment_filter' => true,
            ];
        }

        if (in_array($workspacetab, ['openrequests', 'confirmedrequests'], true)) {
            return [
                'show_reporting_filters' => true,
                'show_status_filter' => false,
                'show_semester_filter' => true,
                'show_assignment_filter' => true,
            ];
        }

        if ($workspacetab === 'rejectedcancelled') {
            return [
                'show_reporting_filters' => true,
                'show_status_filter' => true,
                'show_semester_filter' => true,
                'show_assignment_filter' => true,
            ];
        }

        if ($workspacetab === 'history') {
            return [
                'show_reporting_filters' => true,
                'show_status_filter' => true,
                'show_semester_filter' => true,
                'show_assignment_filter' => true,
            ];
        }

        return [
            'show_reporting_filters' => true,
            'show_status_filter' => true,
            'show_semester_filter' => true,
            'show_assignment_filter' => true,
        ];
    }

    /**
     * Return the unified workspace table profile for the active service-team tab.
     *
     * @param string $workspacetab
     * @return array{
     *     workspacetab: string,
     *     headerbackgroundcolor: string,
     *     tableid: string,
     *     tablecssclass: string,
     *     rowcssclass: string,
     *     showdatetimecolumn: bool,
     *     showdatecolumn: bool,
     *     showidcolumn: bool,
     *     showcreatedbycolumn: bool,
     *     showprogresscolumn: bool,
     *     showchecklistcolumn: bool,
     *     showresourcescolumn: bool,
     *     showcancelcolumn: bool,
     *     showreactivatecolumn: bool,
     *     showtablesearch: bool,
     *     enableworkflowhistory: bool,
     *     emptymessage: string,
     *     requesttab: string
     * }
     */
    public static function get_workspace_table_profile(string $workspacetab): array {
        $isreporting = in_array($workspacetab, ['allrequests', 'history'], true);
        $isqueue = in_array($workspacetab, ['openrequests', 'confirmedrequests', 'rejectedcancelled'], true);

        $headercolors = [
            'allrequests' => '#cfe2ff',
            'history' => '#cfe2ff',
            'openrequests' => '#fff3cd',
            'confirmedrequests' => '#d4edda',
            'rejectedcancelled' => '#f8d7da',
        ];
        $tableids = [
            'allrequests' => 'overview-table',
            'history' => 'overview-table',
            'openrequests' => 'open-requests-table',
            'confirmedrequests' => 'confirmed-requests-table',
            'rejectedcancelled' => 'rejected-requests-table',
        ];
        $tablecssclasses = [
            'openrequests' => 'mod-bookit-open-requests-table',
            'confirmedrequests' => 'mod-bookit-confirmed-requests-table',
            'rejectedcancelled' => 'mod-bookit-rejected-requests-table',
        ];
        $rowcssclasses = [
            'openrequests' => 'mod-bookit-open-request-row',
            'confirmedrequests' => 'mod-bookit-confirmed-request-row',
            'rejectedcancelled' => 'mod-bookit-rejected-request-row',
        ];
        $emptymessages = [
            'allrequests' => 'overview_no_results',
            'history' => 'overview_no_results',
            'openrequests' => 'overview_open_requests_empty',
            'confirmedrequests' => 'overview_confirmed_requests_empty',
            'rejectedcancelled' => 'overview_rejected_requests_empty',
        ];

        return [
            'workspacetab' => $workspacetab,
            'headerbackgroundcolor' => $headercolors[$workspacetab] ?? '#cfe2ff',
            'tableid' => $tableids[$workspacetab] ?? 'overview-table',
            'tablecssclass' => $tablecssclasses[$workspacetab] ?? '',
            'rowcssclass' => $rowcssclasses[$workspacetab] ?? '',
            'showdatetimecolumn' => $isreporting,
            'showdatecolumn' => $isqueue,
            'showidcolumn' => true,
            'showcreatedbycolumn' => $isreporting,
            'showprogresscolumn' => $isreporting,
            'showchecklistcolumn' => $isreporting,
            'showresourcescolumn' => $isreporting,
            'showcancelcolumn' => false,
            'showreactivatecolumn' => in_array($workspacetab, ['history', 'rejectedcancelled'], true),
            'showtablesearch' => $isqueue,
            'enableworkflowhistory' => true,
            'emptymessage' => $emptymessages[$workspacetab] ?? 'overview_no_results',
            'requesttab' => $workspacetab,
        ];
    }

    /**
     * Build a one-line summary for the latest booking workflow history entry.
     *
     * @param stdClass|null $latesthistory
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return string
     */
    public static function build_overview_workflow_latest_summary(
        ?stdClass $latesthistory,
        stdClass $event,
        context_module $context,
        int $userid
    ): string {
        if (!$latesthistory) {
            return '';
        }

        $action = trim((string)($latesthistory->action ?? ''));
        if ($action === '') {
            return '';
        }

        $summary = get_string('history_action_' . $action, 'mod_bookit') . ' · '
            . userdate((int)$latesthistory->timecreated, get_string('strftimedatetime', 'langconfig'));
        if (event_access_manager::can_user_view_workflow_history_actor_identity($event, $context, $userid)) {
            $actorname = trim(($latesthistory->firstname ?? '') . ' ' . ($latesthistory->lastname ?? ''));
            if ($actorname !== '') {
                $summary .= ' · ' . $actorname;
            }
        }

        return $summary;
    }

    /**
     * Build structured workflow history entries for the overview status cell expander.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public static function build_overview_workflow_history(
        stdClass $event,
        context_module $context,
        int $userid,
        int $limit = 10
    ): array {
        $historyfieldlabels = [
            'bookingstatus' => 'event_bookingstatus',
            'institutionid' => 'event_department',
            'internalnotes' => 'event_internalnotes',
            'name' => 'event_name',
            'notes' => 'event_notes',
            'otherexaminers' => 'event_otherexaminers',
            'participantsamount' => 'event_students',
            'personinchargeid' => 'event_personincharge',
            'roomid' => 'event_room',
            'starttime' => 'event_start',
        ];
        $resolvefieldlabel = static function (string $field) use ($historyfieldlabels): string {
            if (!empty($historyfieldlabels[$field])) {
                return get_string($historyfieldlabels[$field], 'mod_bookit');
            }

            return ucfirst(str_replace('_', ' ', $field));
        };
        $formathistoryvalue = static function (string $field, $value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return match ($field) {
                'bookingstatus' => self::get_booking_status_label((int)$value),
                'starttime', 'endtime' => userdate((int)$value, get_string('strftimedatetime', 'langconfig')),
                default => is_scalar($value) ? (string)$value : '-',
            };
        };

        $entries = [];
        foreach (array_values(self::get_booking_history((int)$event->id, $limit)) as $entry) {
            $changes = [];
            $rawchangedfields = json_decode((string)($entry->changedfields ?? ''), true);
            if (is_array($rawchangedfields)) {
                foreach ($rawchangedfields as $field => $change) {
                    if (
                        !event_access_manager::can_user_view_workflow_history_field(
                            $event,
                            $context,
                            $userid,
                            (string)$field
                        )
                    ) {
                        continue;
                    }
                    $changes[] = [
                        'text' => get_string('overview_workflow_history_change', 'mod_bookit', (object)[
                            'field' => $resolvefieldlabel((string)$field),
                            'from' => $formathistoryvalue((string)$field, $change['from'] ?? null),
                            'to' => $formathistoryvalue((string)$field, $change['to'] ?? null),
                        ]),
                    ];
                }
            }

            if (empty($changes)) {
                continue;
            }

            $summary = get_string('history_action_' . $entry->action, 'mod_bookit') . ' · '
                . userdate((int)$entry->timecreated, get_string('strftimedatetime', 'langconfig'));
            if (event_access_manager::can_user_view_workflow_history_actor_identity($event, $context, $userid)) {
                $actorname = trim(($entry->firstname ?? '') . ' ' . ($entry->lastname ?? ''));
                if ($actorname !== '') {
                    $summary .= ' · ' . $actorname;
                }
            }

            $entries[] = [
                'summary' => s($summary),
                'haschanges' => true,
                'changes' => $changes,
                'hasrecoverymarker' => !empty($entry->recoverymarker),
                'recoverymarkertext' => get_string('overview_workflow_history_recovery', 'mod_bookit'),
            ];
        }

        return $entries;
    }

    /**
     * Narrow overview events by involvement roles (All requests Assigned to me).
     *
     * Keeps events where the user is creator, person in charge, other examiner, or support person.
     *
     * @param stdClass[] $events
     * @param int $userid
     * @param string $assignmentfilter all|assigned
     * @return stdClass[]
     */
    public static function filter_events_by_assignment(array $events, int $userid, string $assignmentfilter): array {
        if ($assignmentfilter !== 'assigned') {
            return $events;
        }

        $involvementroles = ['bookingperson', 'personincharge', 'otherexaminer', 'supportperson'];

        return array_values(array_filter(
            $events,
            static function (stdClass $event) use ($userid, $involvementroles): bool {
                $roles = event_access_manager::get_user_roles_for_event($event, $userid);
                return !empty(array_intersect($roles, $involvementroles));
            }
        ));
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
                e.usercreated,
                e.usermodified,
                creator.firstname AS creatorfirstname,
                creator.lastname AS creatorlastname,
                creator.deleted AS creatordeleted,
                r.name AS room
            FROM {bookit_event} e
            LEFT JOIN {bookit_room} r ON r.id = e.roomid
            LEFT JOIN {user} creator ON creator.id = e.usercreated
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY e.starttime DESC, e.id DESC
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
     * Build SQL filters for service-team governed reporting tabs.
     *
     * @param string $workspace
     * @param array $filters
     * @param int $reportstart
     * @param int $reportend
     * @param int[] $semesterids
     * @param int[] $bookingstatuses
     * @return array
     * @throws dml_exception
     */
    private static function build_reporting_overview_sql_filter(
        string $workspace,
        array $filters,
        int $reportstart,
        int $reportend,
        array $semesterids,
        array $bookingstatuses
    ): array {
        global $DB;

        $params = [
            'reportstart' => $reportstart,
            'reportend' => $reportend,
            'referencetime' => time(),
        ];
        $conditions = [
            'e.endtime >= :reportstart',
            'e.starttime <= :reportend',
        ];

        if ($workspace === 'history') {
            $conditions[] = '(e.bookingstatus IN (:historycanceled, :historyrejected) OR e.endtime < :referencetime)';
            $params['historycanceled'] = event_access_manager::BOOKINGSTATUS_CANCELED;
            $params['historyrejected'] = event_access_manager::BOOKINGSTATUS_REJECTED;
        } else if (empty($bookingstatuses)) {
            $conditions[] = 'e.bookingstatus NOT IN (:activecanceled, :activerejected)';
            $conditions[] = 'e.endtime >= :referencetime';
            $params['activecanceled'] = event_access_manager::BOOKINGSTATUS_CANCELED;
            $params['activerejected'] = event_access_manager::BOOKINGSTATUS_REJECTED;
        }

        if (!empty($bookingstatuses)) {
            [$statussql, $statusparams] = $DB->get_in_or_equal($bookingstatuses, SQL_PARAMS_NAMED, 'status');
            $conditions[] = "e.bookingstatus $statussql";
            $params += $statusparams;
        }

        $facultyids = self::normalise_filter_ids($filters['facultyids'] ?? []);
        if (!empty($facultyids)) {
            [$facultysql, $facultyparams] = $DB->get_in_or_equal($facultyids, SQL_PARAMS_NAMED, 'faculty');
            $conditions[] = "e.institutionid $facultysql";
            $params += $facultyparams;
        }

        $selectedsemesters = array_values(array_filter($semesterids, static fn(int $value): bool => $value !== 0));
        if (!empty($selectedsemesters)) {
            [$semestersql, $semesterparams] = $DB->get_in_or_equal($selectedsemesters, SQL_PARAMS_NAMED, 'semester');
            $conditions[] = "e.semester $semestersql";
            $params += $semesterparams;
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * SQL fragment for Assigned-to-me involvement (creator / PIC / other examiners / support).
     *
     * @param int $userid
     * @return array{0:string,1:array}
     */
    private static function build_assigned_involvement_sql(int $userid): array {
        global $DB;

        $supportwrapped = $DB->sql_concat("','", "COALESCE(e.supportpersons, '')", "','");
        $otherwrapped = $DB->sql_concat("','", "COALESCE(e.otherexaminers, '')", "','");
        $sql = '(e.usercreated = :assignuserid'
            . ' OR e.personinchargeid = :assignpicuserid'
            . ' OR (e.otherexaminers IS NOT NULL AND e.otherexaminers <> \'\' AND '
            . $DB->sql_like($otherwrapped, ':assignotheruserid', false) . ')'
            . ' OR (e.supportpersons IS NOT NULL AND e.supportpersons <> \'\' AND '
            . $DB->sql_like($supportwrapped, ':assignsupportuserid', false) . '))';

        return [
            $sql,
            [
                'assignuserid' => $userid,
                'assignpicuserid' => $userid,
                'assignotheruserid' => '%,' . $userid . ',%',
                'assignsupportuserid' => '%,' . $userid . ',%',
            ],
        ];
    }

    /**
     * Apply date / faculty / semester filters on queue-tab workspace queries.
     *
     * @param string[] $conditions
     * @param array $params
     * @param array $filters
     * @param int|null $reportstart
     * @param int|null $reportend
     * @return array{0:string[],1:array}
     */
    private static function append_queue_reporting_filters(
        array $conditions,
        array $params,
        array $filters,
        ?int $reportstart,
        ?int $reportend
    ): array {
        global $DB;

        if ($reportstart !== null && $reportend !== null) {
            $conditions[] = 'e.endtime >= :queuereportstart';
            $conditions[] = 'e.starttime <= :queuereportend';
            $params['queuereportstart'] = $reportstart;
            $params['queuereportend'] = $reportend;
        }

        $facultyids = self::normalise_filter_ids($filters['facultyids'] ?? []);
        if (!empty($facultyids)) {
            [$facultysql, $facultyparams] = $DB->get_in_or_equal($facultyids, SQL_PARAMS_NAMED, 'queuefaculty');
            $conditions[] = "e.institutionid $facultysql";
            $params += $facultyparams;
        }

        $semesterids = array_values(array_filter(
            array_map('intval', $filters['semesterids'] ?? []),
            static fn(int $value): bool => $value !== 0
        ));
        if (!empty($semesterids)) {
            [$semestersql, $semesterparams] = $DB->get_in_or_equal($semesterids, SQL_PARAMS_NAMED, 'queuesemester');
            $conditions[] = "e.semester $semestersql";
            $params += $semesterparams;
        }

        return [$conditions, $params];
    }

    /**
     * Count service-team reporting events for a governed overview filter.
     *
     * @param string $where
     * @param array $params
     * @return int
     * @throws dml_exception
     */
    private static function count_reporting_overview_events(string $where, array $params): int {
        global $DB;

        return (int)$DB->get_field_sql(
            "
                SELECT COUNT(1)
                  FROM {bookit_event} e
             LEFT JOIN {user} creator ON creator.id = e.usercreated
                 WHERE $where
            ",
            $params
        );
    }

    /**
     * Read a bounded page of service-team reporting events.
     *
     * @param string $where
     * @param array $params
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws dml_exception
     */
    private static function get_reporting_overview_events_page(string $where, array $params, int $offset, int $limit): array {
        global $DB;

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
                e.usercreated,
                e.usermodified,
                creator.firstname AS creatorfirstname,
                creator.lastname AS creatorlastname,
                creator.deleted AS creatordeleted,
                r.name AS room
            FROM {bookit_event} e
            LEFT JOIN {bookit_room} r ON r.id = e.roomid
            LEFT JOIN {user} creator ON creator.id = e.usercreated
            WHERE $where
            ORDER BY e.starttime DESC, e.id DESC
        ";

        return $DB->get_records_sql($sql, $params, max(0, $offset), max(1, $limit));
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
        $explicitterminal = array_values(array_intersect(
            $bookingstatuses,
            [
                event_access_manager::BOOKINGSTATUS_CANCELED,
                event_access_manager::BOOKINGSTATUS_REJECTED,
            ]
        ));

        return array_values(array_filter($events, static function (stdClass $event) use (
            $history,
            $referencetime,
            $bookingstatuses,
            $facultyids,
            $selectedsemesters,
            $explicitterminal
        ): bool {
            $eventstatus = (int)($event->bookingstatus ?? -1);
            $isinhistory = self::is_event_in_history($event, $referencetime);
            if ($isinhistory !== $history) {
                $explicitlyselectedterminal = !$history
                    && !empty($explicitterminal)
                    && in_array($eventstatus, $bookingstatuses, true)
                    && in_array($eventstatus, $explicitterminal, true);
                if (!$explicitlyselectedterminal) {
                    return false;
                }
            }

            if (
                !$history
                && self::is_hidden_from_active_overview($event)
                && (empty($bookingstatuses) || !in_array($eventstatus, $bookingstatuses, true))
            ) {
                return false;
            }

            if (!empty($bookingstatuses) && !in_array((int)($event->bookingstatus ?? -1), $bookingstatuses, true)) {
                return false;
            }

            if (!empty($facultyids) && !in_array((int)($event->institutionid ?? 0), $facultyids, true)) {
                return false;
            }

            if (!empty($selectedsemesters)) {
                $semester = (int)($event->semester ?? 0);
                if (!in_array($semester, $selectedsemesters, true)) {
                    return false;
                }
            }

            return true;
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
        $bookingstatus = (int)($event->bookingstatus ?? -1);
        if ($bookingstatus === event_access_manager::BOOKINGSTATUS_CANCELED) {
            return true;
        }

        if ($bookingstatus === event_access_manager::BOOKINGSTATUS_REJECTED) {
            return true;
        }

        return (int)($event->endtime ?? 0) < ($referencetime ?? time());
    }

    /**
     * SQL condition for rejected/cancelled queue membership.
     *
     * @return string
     */
    private static function get_rejected_requests_where_sql(): string {
        return "
            (
                e.bookingstatus = :rejected
                OR (
                    e.bookingstatus = :canceled
                    AND EXISTS (
                        SELECT 1
                          FROM {bookit_event_history} h
                         WHERE h.eventid = e.id
                           AND h.newstatus = :historycanceled
                           AND (h.usermodified = 0 OR h.usermodified = e.usercreated)
                           AND NOT EXISTS (
                               SELECT 1
                                 FROM {bookit_event_history} newer
                                WHERE newer.eventid = e.id
                                  AND newer.newstatus = :newerhistorycanceled
                                  AND (
                                      newer.timecreated > h.timecreated
                                      OR (newer.timecreated = h.timecreated AND newer.id > h.id)
                                  )
                           )
                    )
                )
            )
        ";
    }

    /**
     * SQL params for rejected/cancelled queue membership.
     *
     * @return array
     */
    private static function get_rejected_requests_params(): array {
        return [
            'rejected' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'canceled' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'historycanceled' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'newerhistorycanceled' => event_access_manager::BOOKINGSTATUS_CANCELED,
        ];
    }

    /**
     * Return the number of accepted bookings in the dedicated service-team workspace.
     *
     * @param int|null $referencetime
     * @return int
     * @throws dml_exception
     */
    public static function count_confirmed_requests(?int $referencetime = null): int {
        global $DB;

        $referencetime ??= time();
        return (int)$DB->count_records_select(
            'bookit_event',
            'bookingstatus = :status AND endtime >= :referencetime',
            [
                'status' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'referencetime' => $referencetime,
            ]
        );
    }

    /**
     * Return the number of rejected requests in the dedicated trash queue.
     *
     * @return int
     * @throws dml_exception
     */
    public static function count_rejected_requests(): int {
        global $DB;

        return (int)$DB->get_field_sql(
            "
                SELECT COUNT(1)
                  FROM {bookit_event} e
                 WHERE " . self::get_rejected_requests_where_sql(),
            self::get_rejected_requests_params()
        );
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
        // Last editor is the actor performing the transition; booking person is usercreated.
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
     * Merge canceled bookings into export reads when service team explicitly requests them.
     *
     * @param array $events
     * @param string $starttime
     * @param string $endtime
     * @param context_module $context
     * @return array
     * @throws dml_exception|coding_exception
     */
    private static function merge_service_team_canceled_export_events(
        array $events,
        string $starttime,
        string $endtime,
        context_module $context
    ): array {
        global $DB, $USER;

        $starttimestamp = DateTime::createFromFormat('Y-m-d H:i', $starttime)->getTimestamp();
        $endtimestamp = DateTime::createFromFormat('Y-m-d H:i', $endtime)->getTimestamp();
        $observerrestricted = event_access_manager::is_observer_restricted_mode($context);
        $facultylabels = self::get_faculties();

        $sql = 'SELECT e.id, e.name, e.semester, e.institutionid, e.roomid, e.bookingstatus, e.starttime, e.endtime,
                    e.extratimebefore, e.extratimeafter, e.personinchargeid, e.otherexaminers, e.supportpersons,
                    e.usercreated, e.usermodified, r.eventcolor, r.name as roomname, r.shortname, r.location
                FROM {bookit_event} e
                LEFT JOIN {bookit_room} r ON r.id = e.roomid
                WHERE e.bookingstatus = :status
                  AND e.endtime >= :starttime AND e.starttime <= :endtime
                ORDER BY e.starttime';
        $records = $DB->get_records_sql($sql, [
            'status' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => $starttimestamp,
            'endtime' => $endtimestamp,
        ]);

        $existingids = [];
        foreach ($events as $event) {
            $existingids[(int)($event['id'] ?? 0)] = true;
        }

        foreach ($records as $record) {
            if (!empty($existingids[(int)$record->id])) {
                continue;
            }

            $events[] = self::build_calendar_read_event($record, $observerrestricted, $facultylabels, $context, (int)$USER->id);
        }

        return $events;
    }

    /**
     * Build a canonical calendar event payload from a DB record.
     *
     * @param stdClass $record
     * @param bool $observerrestricted
     * @param array $facultylabels
     * @param context_module $context
     * @param int $userid
     * @return array
     */
    private static function build_calendar_read_event(
        stdClass $record,
        bool $observerrestricted,
        array $facultylabels,
        context_module $context,
        int $userid
    ): array {
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
                'modalfootermode' => $observerrestricted
                    ? event_access_manager::MODAL_FOOTER_MODE_VIEW_ONLY
                    : event_access_manager::get_event_modal_footer_mode($record, $context, $userid),
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
                'label'    => self::get_booking_status_label($value),
                'selected' => ($value === $current),
                'bg'       => $color['bg'],
                'fg'       => $color['fg'],
            ];
        }
        return $options;
    }

    /**
     * Return the presentation group key for a booking status value.
     *
     * @param int $status
     * @return string One of open, confirmed, closed.
     */
    public static function get_booking_status_group_key(int $status): string {
        return match ($status) {
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS => 'open',
            event_access_manager::BOOKINGSTATUS_CONFIRMED => 'confirmed',
            default => 'closed',
        };
    }

    /**
     * Return the localized group label for a booking status value.
     *
     * @param int $status
     * @return string
     */
    public static function get_booking_status_group_label(int $status): string {
        return get_string('overview_status_group_' . self::get_booking_status_group_key($status), 'mod_bookit');
    }

    /**
     * Return the authoritative booking status definition for presentation.
     *
     * @param int $status
     * @return array{value:int,label:string,groupkey:string,grouplabel:string,colors:array,cssclass:string}
     */
    public static function get_booking_status_definition(int $status): array {
        $colors = self::get_booking_status_colors();
        $color = $colors[$status] ?? ['bg' => '#ffffff', 'fg' => '#000000'];
        $groupkey = self::get_booking_status_group_key($status);

        return [
            'value' => $status,
            'label' => self::get_booking_status_label($status),
            'groupkey' => $groupkey,
            'grouplabel' => get_string('overview_status_group_' . $groupkey, 'mod_bookit'),
            'colors' => $color,
            'cssclass' => self::get_booking_status_class($status),
        ];
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
     * Return the customer-facing label for a booking status value.
     *
     * @param int $status
     * @return string
     */
    public static function get_booking_status_label(int $status): string {
        return get_string('event_bookingstatus_' . $status, 'mod_bookit');
    }

    /**
     * Return the customer-facing label for a resource line status value.
     *
     * @param string $status
     * @return string
     */
    public static function get_resource_status_label(string $status): string {
        return match ($status) {
            'requested' => self::get_booking_status_label(event_access_manager::BOOKINGSTATUS_NEW),
            'confirmed' => self::get_booking_status_label(event_access_manager::BOOKINGSTATUS_CONFIRMED),
            'inprogress' => self::get_booking_status_label(event_access_manager::BOOKINGSTATUS_IN_PROGRESS),
            'rejected' => self::get_booking_status_label(event_access_manager::BOOKINGSTATUS_REJECTED),
            default => $status,
        };
    }

    /**
     * Return Bootstrap badge class for a resource line status value.
     *
     * @param string $status
     * @return string
     */
    public static function get_resource_status_bootstrap_badge_class(string $status): string {
        return match ($status) {
            'requested' => 'badge-secondary',
            'confirmed' => 'badge-success',
            'inprogress' => 'badge-primary',
            'rejected' => 'badge-danger',
            default => 'badge-secondary',
        };
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
            event_access_manager::BOOKINGSTATUS_CONFIRMED => 'confirmed',
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

    /**
     * Sort overview event rows by start time with id tie-break.
     *
     * @param stdClass[] $events
     * @param bool $desc When true, newest start time first.
     * @return stdClass[]
     */
    public static function sort_overview_events_by_starttime(array $events, bool $desc = true): array {
        usort($events, static function (stdClass $a, stdClass $b) use ($desc): int {
            $astart = (int)($a->starttime ?? 0);
            $bstart = (int)($b->starttime ?? 0);
            if ($astart !== $bstart) {
                return $desc ? ($bstart <=> $astart) : ($astart <=> $bstart);
            }

            $aid = (int)($a->id ?? 0);
            $bid = (int)($b->id ?? 0);

            return $desc ? ($bid <=> $aid) : ($aid <=> $bid);
        });

        return array_values($events);
    }
}
