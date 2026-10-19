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
 * Privacy API implementation for mod_bookit.
 *
 * @package     mod_bookit
 * @category    privacy
 * @copyright   2026 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\privacy;

use coding_exception;
use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\helper;
use core_privacy\local\request\user_preference_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use dml_exception;

/**
 * Privacy API implementation for BookIt.
 *
 * BookIt bookings and administration data are shared between all course-module
 * instances, so their privacy context is the system context.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider,
    user_preference_provider {
    /** @var array Database tables containing the user who last modified a record. */
    private const MODIFIED_TABLES = [
        'bookit_blocker',
        'bookit_checklist_category',
        'bookit_checklist_item',
        'bookit_checklist_master',
        'bookit_event',
        'bookit_event_checklist_state',
        'bookit_event_history',
        'bookit_event_resource',
        'bookit_institution',
        'bookit_notification_slots',
        'bookit_resource',
        'bookit_resource_category',
        'bookit_resource_settings',
        'bookit_room',
        'bookit_weekplan',
        'bookit_weekplan_room',
    ];

    /**
     * Fields directly referencing a user in the database.
     *
     * @return array<string, string[]>
     */
    private static function get_userid_fields(): array {
        return [
            'bookit_event' => ['usercreated', 'usermodified', 'personinchargeid'],
            'bookit_event_checklist_state' => ['userid', 'usermodified'],
            'bookit_event_history' => ['usermodified'],
            'bookit_event_resource' => ['usermodified'],
            'bookit_blocker' => ['usermodified'],
            'bookit_checklist_category' => ['usermodified'],
            'bookit_checklist_item' => ['usermodified'],
            'bookit_checklist_master' => ['usermodified'],
            'bookit_institution' => ['usermodified'],
            'bookit_notification_slots' => ['usermodified'],
            'bookit_resource' => ['usermodified'],
            'bookit_resource_category' => ['usermodified'],
            'bookit_resource_settings' => ['usermodified'],
            'bookit_room' => ['usermodified'],
            'bookit_weekplan' => ['usermodified'],
            'bookit_weekplan_room' => ['usermodified'],
        ];
    }

    /**
     * Describe the personal data stored by BookIt.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'bookit_event',
            [
                'name' => 'privacy:metadata:bookit_event:name',
                'semester' => 'privacy:metadata:bookit_event:semester',
                'institutionid' => 'privacy:metadata:bookit_event:institutionid',
                'starttime' => 'privacy:metadata:bookit_event:starttime',
                'endtime' => 'privacy:metadata:bookit_event:endtime',
                'duration' => 'privacy:metadata:bookit_event:duration',
                'roomid' => 'privacy:metadata:bookit_event:roomid',
                'participantsamount' => 'privacy:metadata:bookit_event:participantsamount',
                'compensationfordisadvantages' => 'privacy:metadata:bookit_event:compensationfordisadvantages',
                'bookingstatus' => 'privacy:metadata:bookit_event:bookingstatus',
                'personinchargeid' => 'privacy:metadata:bookit_event:personinchargeid',
                'otherexaminers' => 'privacy:metadata:bookit_event:otherexaminers',
                'coursetemplate' => 'privacy:metadata:bookit_event:coursetemplate',
                'supportpersons' => 'privacy:metadata:bookit_event:supportpersons',
                'notes' => 'privacy:metadata:bookit_event:notes',
                'internalnotes' => 'privacy:metadata:bookit_event:internalnotes',
                'extratimebefore' => 'privacy:metadata:bookit_event:extratime',
                'extratimeafter' => 'privacy:metadata:bookit_event:extratime',
                'refcourseid' => 'privacy:metadata:bookit_event:refcourseid',
                'usercreated' => 'privacy:metadata:bookit_event:usercreated',
                'usermodified' => 'privacy:metadata:usermodified',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:bookit_event'
        );
        $collection->add_database_table(
            'bookit_event_checklist_state',
            [
                'eventid' => 'privacy:metadata:bookit_event_checklist_state:eventid',
                'checklistitemid' => 'privacy:metadata:bookit_event_checklist_state:checklistitemid',
                'userid' => 'privacy:metadata:bookit_event_checklist_state:userid',
                'done' => 'privacy:metadata:bookit_event_checklist_state:done',
                'usermodified' => 'privacy:metadata:usermodified',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:bookit_event_checklist_state'
        );
        $collection->add_database_table(
            'bookit_event_history',
            [
                'eventid' => 'privacy:metadata:bookit_event_history:eventid',
                'action' => 'privacy:metadata:bookit_event_history:action',
                'oldstatus' => 'privacy:metadata:bookit_event_history:status',
                'newstatus' => 'privacy:metadata:bookit_event_history:status',
                'changedfields' => 'privacy:metadata:bookit_event_history:changedfields',
                'usermodified' => 'privacy:metadata:usermodified',
                'timecreated' => 'privacy:metadata:timecreated',
            ],
            'privacy:metadata:bookit_event_history'
        );
        $collection->add_database_table(
            'bookit_event_resource',
            [
                'eventid' => 'privacy:metadata:bookit_event_resource:eventid',
                'resourceid' => 'privacy:metadata:bookit_event_resource:resourceid',
                'amount' => 'privacy:metadata:bookit_event_resource:amount',
                'status' => 'privacy:metadata:bookit_event_resource:status',
                'usermodified' => 'privacy:metadata:usermodified',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:bookit_event_resource'
        );

        foreach (
            array_diff(self::MODIFIED_TABLES, [
            'bookit_event',
            'bookit_event_checklist_state',
            'bookit_event_history',
            'bookit_event_resource',
            ]) as $table
        ) {
            $collection->add_database_table(
                $table,
                [
                    'usermodified' => 'privacy:metadata:usermodified',
                    'timecreated' => 'privacy:metadata:timecreated',
                    'timemodified' => 'privacy:metadata:timemodified',
                ],
                'privacy:metadata:modifiedrecords'
            );
        }

        $collection->add_user_preference(
            'mod_bookit_overview_sort',
            'privacy:metadata:preference:overview_sort'
        );
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:core_message');

        return $collection;
    }

    /**
     * Get the context containing the plugin-wide BookIt personal data.
     *
     * @param int $userid User ID.
     * @return contextlist
     * @throws dml_exception
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        if (!self::user_has_data($userid)) {
            return $contextlist;
        }

        context_system::instance();
        $contextlist->add_system_context();

        return $contextlist;
    }

    /**
     * Add users with BookIt data to the system-context user list.
     *
     * @param userlist $userlist The user list.
     * @return void
     * @throws dml_exception
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        if (!$userlist->get_context() instanceof context_system) {
            return;
        }

        $queries = [];
        foreach (self::get_userid_fields() as $table => $fields) {
            foreach ($fields as $field) {
                $queries[] = "SELECT {$field} AS userid FROM {{$table}} WHERE {$field} > 0";
            }
        }

        $userlist->add_from_sql('userid', implode(' UNION ', $queries), []);
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {user_preferences} WHERE name = :name',
            ['name' => 'mod_bookit_overview_sort']
        );

        $events = $DB->get_records('bookit_event', null, '', 'id, otherexaminers, supportpersons');
        $assigneduserids = [];
        foreach ($events as $event) {
            foreach (['otherexaminers', 'supportpersons'] as $field) {
                $assigneduserids = array_merge($assigneduserids, self::parse_userids((string)($event->$field ?? '')));
            }
        }
        $userlist->add_users(array_values(array_unique($assigneduserids)));
    }

    /**
     * Export personal data for a user.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $context = context_system::instance();
        if (!in_array($context->id, array_map('intval', $contextlist->get_contextids()), true)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = (int)$user->id;
        $eventrecords = self::get_events_for_user($userid);
        $eventids = array_map('intval', array_keys($eventrecords));

        $export = [
            'events' => array_values($eventrecords),
            'event_checklist_state' => self::get_user_records('bookit_event_checklist_state', $userid),
            'event_history' => self::get_user_records('bookit_event_history', $userid),
            'event_resource' => self::get_user_records('bookit_event_resource', $userid),
            'modified_records' => [],
        ];

        if (!empty($eventids)) {
            [$eventsql, $eventparams] = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED);
            $export['event_checklist_state'] = array_merge(
                $export['event_checklist_state'],
                $DB->get_records_select('bookit_event_checklist_state', "eventid {$eventsql}", $eventparams)
            );
            $export['event_history'] = array_merge(
                $export['event_history'],
                $DB->get_records_select('bookit_event_history', "eventid {$eventsql}", $eventparams)
            );
            $export['event_resource'] = array_merge(
                $export['event_resource'],
                $DB->get_records_select('bookit_event_resource', "eventid {$eventsql}", $eventparams)
            );
        }

        foreach (self::MODIFIED_TABLES as $table) {
            if (
                in_array($table, [
                'bookit_event',
                'bookit_event_checklist_state',
                'bookit_event_history',
                'bookit_event_resource',
                ], true)
            ) {
                continue;
            }
            $records = $DB->get_records($table, ['usermodified' => $userid]);
            if (!empty($records)) {
                $export['modified_records'][$table] = array_values($records);
            }
        }

        $contextdata = helper::get_context_data($context, $user);
        writer::with_context($context)->export_data([], (object)array_merge((array)$contextdata, $export));
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all BookIt personal data in the system context.
     *
     * BookIt has a single shared booking store, rather than per-activity data.
     *
     * @param context $context Context to delete from.
     * @return void
     * @throws dml_exception
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_system) {
            return;
        }

        $DB->delete_records('bookit_event_checklist_state');
        $DB->delete_records('bookit_event_history');
        $DB->delete_records('bookit_event_resource');
        $DB->delete_records('bookit_event');
        self::anonymise_modified_records();
        $DB->delete_records('user_preferences', ['name' => 'mod_bookit_overview_sort']);
    }

    /**
     * Delete BookIt personal data for the specified user and contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if ($contextlist->count() === 0) {
            return;
        }

        $context = context_system::instance();
        if (!in_array($context->id, array_map('intval', $contextlist->get_contextids()), true)) {
            return;
        }

        self::delete_user_data((int)$contextlist->get_user()->id);
        unset_user_preference('mod_bookit_overview_sort', (int)$contextlist->get_user()->id);
    }

    /**
     * Delete BookIt personal data for a list of users.
     *
     * @param approved_userlist $userlist Approved users.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if (!$userlist->get_context() instanceof context_system) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_data($userid);
            unset_user_preference('mod_bookit_overview_sort', $userid);
        }
    }

    /**
     * Export BookIt user preferences.
     *
     * @param int $userid User ID.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_user_preferences(int $userid): void {
        $preference = get_user_preferences('mod_bookit_overview_sort', null, $userid);
        if ($preference !== null) {
            writer::with_context(context_system::instance())->export_user_preference(
                'mod_bookit',
                'mod_bookit_overview_sort',
                $preference,
                get_string('privacy:metadata:preference:overview_sort', 'mod_bookit')
            );
        }
    }

    /**
     * Determine whether the user is referenced in BookIt data.
     *
     * @param int $userid User ID.
     * @return bool
     * @throws dml_exception
     */
    private static function user_has_data(int $userid): bool {
        global $DB;

        foreach (self::get_userid_fields() as $table => $fields) {
            foreach ($fields as $field) {
                if ($DB->record_exists($table, [$field => $userid])) {
                    return true;
                }
            }
        }

        foreach (['otherexaminers', 'supportpersons'] as $field) {
            $userids = $DB->sql_concat("','", "COALESCE({$field}, '')", "','");
            if (
                $DB->record_exists_select(
                    'bookit_event',
                    $DB->sql_like($userids, ':userid', false),
                    ['userid' => '%,' . $userid . ',%']
                )
            ) {
                return true;
            }
        }

        return $DB->record_exists('user_preferences', [
            'userid' => $userid,
            'name' => 'mod_bookit_overview_sort',
        ]);
    }

    /**
     * Get the booking events associated with a user.
     *
     * @param int $userid User ID.
     * @return array
     * @throws dml_exception
     */
    private static function get_events_for_user(int $userid): array {
        global $DB;

        $roleassignmentconditions = [];
        $params = ['usercreated' => $userid, 'usermodified' => $userid, 'personinchargeid' => $userid];
        foreach (['otherexaminers', 'supportpersons'] as $index => $field) {
            $userids = $DB->sql_concat("','", "COALESCE({$field}, '')", "','");
            $key = 'userid' . $index;
            $roleassignmentconditions[] = $DB->sql_like($userids, ':' . $key, false);
            $params[$key] = '%,' . $userid . ',%';
        }
        $select = 'usercreated = :usercreated OR usermodified = :usermodified '
            . 'OR personinchargeid = :personinchargeid OR ' . implode(' OR ', $roleassignmentconditions);

        return $DB->get_records_select('bookit_event', $select, $params);
    }

    /**
     * Get records modified by the specified user.
     *
     * @param string $table Database table.
     * @param int $userid User ID.
     * @return array
     * @throws dml_exception
     */
    private static function get_user_records(string $table, int $userid): array {
        global $DB;

        if ($table === 'bookit_event_checklist_state') {
            return $DB->get_records_select(
                $table,
                'userid = :userid OR usermodified = :usermodified',
                ['userid' => $userid, 'usermodified' => $userid]
            );
        }

        return $DB->get_records($table, ['usermodified' => $userid]);
    }

    /**
     * Remove references to the user from their BookIt records.
     *
     * @param int $userid User ID.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    private static function delete_user_data(int $userid): void {
        global $DB;

        $createdevents = $DB->get_records('bookit_event', ['usercreated' => $userid], '', 'id');
        $deletedids = array_map('intval', array_keys($createdevents));
        if (!empty($deletedids)) {
            [$insql, $params] = $DB->get_in_or_equal($deletedids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('bookit_event_checklist_state', "eventid {$insql}", $params);
            $DB->delete_records_select('bookit_event_history', "eventid {$insql}", $params);
            $DB->delete_records_select('bookit_event_resource', "eventid {$insql}", $params);
            $DB->delete_records_select('bookit_event', "id {$insql}", $params);
        }

        $DB->delete_records('bookit_event_checklist_state', ['userid' => $userid]);
        $DB->set_field('bookit_event_checklist_state', 'usermodified', 0, ['usermodified' => $userid]);
        $DB->set_field('bookit_event_history', 'usermodified', 0, ['usermodified' => $userid]);

        $events = self::get_events_for_user($userid);
        foreach ($events as $event) {
            $event->usercreated = (int)$event->usercreated === $userid ? 0 : $event->usercreated;
            $event->usermodified = (int)$event->usermodified === $userid ? 0 : $event->usermodified;
            $event->personinchargeid = (int)$event->personinchargeid === $userid ? null : $event->personinchargeid;
            $event->otherexaminers = self::remove_userid_from_list((string)$event->otherexaminers, $userid);
            $event->supportpersons = self::remove_userid_from_list((string)$event->supportpersons, $userid);
            $DB->update_record('bookit_event', $event);

            // History stores before/after values, so remove it to avoid retaining role IDs there.
            $DB->delete_records('bookit_event_history', ['eventid' => $event->id]);
        }

        $DB->set_field('bookit_event_resource', 'usermodified', 0, ['usermodified' => $userid]);
        foreach (self::MODIFIED_TABLES as $table) {
            if (
                in_array($table, [
                'bookit_event',
                'bookit_event_checklist_state',
                'bookit_event_history',
                'bookit_event_resource',
                ], true)
            ) {
                continue;
            }
            $DB->set_field($table, 'usermodified', 0, ['usermodified' => $userid]);
        }
    }

    /**
     * Anonymise user references in BookIt configuration records.
     *
     * @return void
     * @throws dml_exception
     */
    private static function anonymise_modified_records(): void {
        global $DB;

        foreach (self::MODIFIED_TABLES as $table) {
            $DB->set_field_select($table, 'usermodified', 0, 'usermodified <> 0');
        }
        $DB->set_field('bookit_event', 'usercreated', 0);
        $DB->set_field('bookit_event', 'personinchargeid', null);
        $DB->set_field('bookit_event', 'otherexaminers', null);
        $DB->set_field('bookit_event', 'supportpersons', null);
    }

    /**
     * Parse a comma-separated user ID field.
     *
     * @param string $value User ID list.
     * @return int[]
     */
    private static function parse_userids(string $value): array {
        return array_values(array_unique(array_filter(array_map('intval', explode(',', $value)))));
    }

    /**
     * Remove a user ID from a comma-separated list.
     *
     * @param string $value User ID list.
     * @param int $userid User ID to remove.
     * @return string|null
     */
    private static function remove_userid_from_list(string $value, int $userid): ?string {
        $userids = array_values(array_filter(
            self::parse_userids($value),
            static fn(int $id): bool => $id !== $userid
        ));

        return empty($userids) ? null : implode(',', $userids);
    }
}
