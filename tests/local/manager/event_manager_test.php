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
 * Unit tests for event_manager reporting helpers.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use context_module;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\install_helper;

/**
 * Unit tests for event_manager helper methods.
 *
 * @covers \mod_bookit\local\manager\event_manager
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * Coverage metadata and PHPMD suppression for the reporting workflow test case.
 *
 * @covers \mod_bookit\local\manager\event_manager
 * @SuppressWarnings(PHPMD)
 */
final class event_manager_test extends advanced_testcase {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /**
     * Set up the message providers and default notification configuration for workflow tests.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        message_update_providers('mod_bookit');
        install_helper::ensure_booking_status_notification_defaults();
        set_config('bookingstatus_notify_serviceteam', 1, 'mod_bookit');
        set_config('bookingstatus_notify_bookingperson', 1, 'mod_bookit');
        set_config('bookingstatus_notify_personincharge', 1, 'mod_bookit');
        set_config('bookingstatus_notify_otherexaminers', 1, 'mod_bookit');
        set_config('bookingstatus_service_addresses', '', 'mod_bookit');
    }

    /**
     * Create a Bookit module context for lifecycle notification tests.
     *
     * @return context_module
     */
    private function create_bookit_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Lifecycle notifications',
        ]);
        return context_module::instance($bookit->cmid);
    }

    /**
     * Create a room for event persistence tests.
     *
     * @return int
     */
    private function create_room(): int {
        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        return $generator->create_room([
            'name' => 'Lifecycle notification room',
            'shortname' => 'NTF001',
        ]);
    }

    /**
     * Build a test event entity for lifecycle notification scenarios.
     *
     * @param int $roomid
     * @param int $userid
     * @param array $overrides
     * @return bookit_event
     */
    private function build_bookit_event(int $roomid, int $userid, array $overrides = []): bookit_event {
        return new bookit_event(
            0,
            $overrides['name'] ?? 'Lifecycle notification event',
            $overrides['semester'] ?? 20261,
            $overrides['institutionid'] ?? 1,
            $overrides['starttime'] ?? strtotime('2026-05-08 09:00:00'),
            $overrides['endtime'] ?? strtotime('2026-05-08 11:00:00'),
            $overrides['duration'] ?? 120,
            $overrides['roomid'] ?? $roomid,
            $overrides['participantsamount'] ?? 10,
            $overrides['timecompensation'] ?? 0,
            $overrides['compensationfordisadvantages'] ?? '',
            $overrides['bookingstatus'] ?? event_access_manager::BOOKINGSTATUS_NEW,
            $overrides['personinchargeid'] ?? null,
            $overrides['otherexaminers'] ?? '',
            $overrides['coursetemplate'] ?? 0,
            $overrides['notes'] ?? 'External note',
            $overrides['internalnotes'] ?? 'Internal note',
            $overrides['supportpersons'] ?? '',
            $overrides['extratimebefore'] ?? 0,
            $overrides['extratimeafter'] ?? 0,
            $overrides['refcourseid'] ?? null,
            $userid,
            time(),
            time(),
            $overrides['resources'] ?? [],
        );
    }

    /**
     * Create a Bookit module context with an observer role assigned to the provided user.
     *
     * @param int $userid
     * @return context_module
     */
    private function create_observer_context_for_user(int $userid): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Observer reporting']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit observer', 'bookitobserver', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewrestrictedobserver', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Insert a minimal event record for tests.
     *
     * @param array $overrides
     * @return int
     */
    private function create_event_record(array $overrides = []): int {
        global $DB;

        $defaults = [
            'name' => 'Test event',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        return (int)$DB->insert_record('bookit_event', (object)array_merge($defaults, $overrides));
    }

    /**
     * Winter terms must bridge the year boundary correctly.
     *
     * @return void
     */
    public function test_get_current_semester_handles_winter_bridge(): void {
        $this->assertSame(20252, event_manager::get_current_semester(strtotime('2026-01-15 12:00:00')));
        $this->assertSame(20261, event_manager::get_current_semester(strtotime('2026-05-15 12:00:00')));
        $this->assertSame(20262, event_manager::get_current_semester(strtotime('2026-11-15 12:00:00')));
    }

    /**
     * Reporting defaults must span the full current year.
     *
     * @return void
     */
    public function test_get_reporting_default_range_returns_full_year(): void {
        [$start, $end] = event_manager::get_reporting_default_range(strtotime('2026-05-07 10:00:00'));

        $this->assertSame(strtotime('2026-01-01 00:00:00'), $start);
        $this->assertSame(strtotime('2026-12-31 23:59:59'), $end);
    }

    /**
     * Reporting defaults must apply the real current semester on first load.
     *
     * @return void
     */
    public function test_get_reporting_default_semester_ids_returns_current_semester(): void {
        $this->assertSame([20261], event_manager::get_reporting_default_semester_ids(strtotime('2026-05-07 10:00:00')));
    }

    /**
     * Explicit semester selections must bypass the automatic current-semester default.
     *
     * @return void
     */
    public function test_resolve_effective_semester_filter_ids_preserves_explicit_selection(): void {
        $this->assertSame(
            [20261],
            event_manager::resolve_effective_semester_filter_ids([], false, strtotime('2026-05-07 10:00:00'))
        );
        $this->assertSame(
            [20262],
            event_manager::resolve_effective_semester_filter_ids([20262], true, strtotime('2026-05-07 10:00:00'))
        );
    }

    /**
     * Observer mode must return only accepted events in a neutral reserved projection.
     *
     * @return void
     */
    public function test_get_events_in_timerange_returns_accepted_only_for_observer(): void {
        $this->resetAfterTest(true);
        $observer = $this->getDataGenerator()->create_user();
        $context = $this->create_observer_context_for_user($observer->id);
        $this->setUser($observer);

        $this->create_event_record([
            'name' => 'Observer hidden request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-05-08 08:00:00'),
            'endtime' => strtotime('2026-05-08 09:00:00'),
        ]);
        $acceptedid = $this->create_event_record([
            'name' => 'Observer accepted booking',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('2026-05-08 10:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ]);

        $events = event_manager::get_events_in_timerange('2026-05-08 00:00', '2026-05-08 23:59', (int)$context->instanceid);

        $this->assertCount(1, $events);
        $this->assertSame($acceptedid, (int)$events[0]['id']);
        $this->assertSame('Reserved', $events[0]['title']);
        $this->assertTrue((bool)$events[0]['extendedProps']->reserved);
    }

    /**
     * Semester filtering must exclude legacy semester bookings unless the explicit legacy option is selected.
     *
     * @return void
     */
    public function test_filter_overview_events_handles_legacy_semester_explicitly(): void {
        $activeevent = (object)[
            'id' => 1,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('+1 day'),
            'endtime' => strtotime('+1 day +2 hours'),
        ];
        $legacyevent = (object)[
            'id' => 2,
            'semester' => 0,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('+2 days'),
            'endtime' => strtotime('+2 days +2 hours'),
        ];

        $filtered = event_manager::filter_overview_events([$activeevent, $legacyevent], [
            'semesterids' => [20261],
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame(1, (int)$filtered[0]->id);

        $filteredwithlegacy = event_manager::filter_overview_events([$activeevent, $legacyevent], [
            'semesterids' => [20261, 0],
        ]);

        $this->assertCount(2, $filteredwithlegacy);
    }

    /**
     * History filtering must separate past and upcoming bookings while keeping additional filters aligned.
     *
     * @return void
     */
    public function test_filter_overview_events_splits_history_and_applies_personal_filters(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $futureevent = (object)[
            'id' => 11,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];
        $pastevent = (object)[
            'id' => 12,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('2026-05-01 09:00:00'),
            'endtime' => strtotime('2026-05-01 11:00:00'),
        ];
        $filteredout = (object)[
            'id' => 13,
            'semester' => 20262,
            'institutionid' => 2,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-05-09 09:00:00'),
            'endtime' => strtotime('2026-05-09 11:00:00'),
        ];

        $active = event_manager::filter_overview_events([$futureevent, $pastevent, $filteredout], [
            'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_ACCEPTED],
            'facultyids' => [1],
            'semesterids' => [20261],
        ], false, $referencetime);
        $history = event_manager::filter_overview_events([$futureevent, $pastevent, $filteredout], [
            'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_ACCEPTED],
            'facultyids' => [1],
            'semesterids' => [20261],
        ], true, $referencetime);

        $this->assertCount(1, $active);
        $this->assertSame(11, (int)$active[0]->id);
        $this->assertCount(1, $history);
        $this->assertSame(12, (int)$history[0]->id);
    }

    /**
     * Workflow history entries are persisted and returned newest-first.
     *
     * @return void
     */
    public function test_record_booking_history_persists_entries(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'History test event',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        event_manager::record_booking_history(
            $eventid,
            'rejected',
            (int)$user->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_REJECTED,
            ['bookingstatus' => ['from' => 1, 'to' => 4]],
            false
        );

        $history = array_values(event_manager::get_booking_history($eventid));
        $this->assertCount(1, $history);
        $this->assertSame('rejected', $history[0]->action);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$history[0]->oldstatus);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_REJECTED, (int)$history[0]->newstatus);
        $this->assertSame((int)$user->id, (int)$history[0]->usermodified);
        $this->assertSame(
            ['bookingstatus' => ['from' => 1, 'to' => 4]],
            json_decode($history[0]->changedfields, true)
        );
    }

    /**
     * Creating a booking through the lifecycle path must send the initial New notification.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_sends_notification_for_initial_creation(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_bookit_event($roomid, (int)$user->id, ['name' => 'Created notification']);

        $sink = $this->redirectMessages();
        event_manager::save_event_with_lifecycle_tracking(
            $event,
            null,
            (int)$user->id,
            $context,
            (int)$context->instanceid
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertSame((string)$user->id, $messages[0]->useridto);
        $this->assertStringContainsString('Created notification', $messages[0]->subject);
    }

    /**
     * Reactivating a canceled request back to New through the lifecycle path must send a notification.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_notifies_on_reactivation_to_new(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_bookit_event($roomid, (int)$user->id, [
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'name' => 'Reactivated notification',
        ]);

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, (int)$user->id, $context);

        $beforecancel = bookit_event::from_database($saved->id);
        $canceled = bookit_event::from_database($saved->id);
        $canceled->bookingstatus = event_access_manager::BOOKINGSTATUS_CANCELED;
        event_manager::save_event_with_lifecycle_tracking($canceled, $beforecancel, (int)$user->id, $context);

        $beforerestore = bookit_event::from_database($saved->id);
        $reactivated = bookit_event::from_database($saved->id);
        $reactivated->bookingstatus = event_access_manager::BOOKINGSTATUS_NEW;

        $sink = $this->redirectMessages();
        event_manager::save_event_with_lifecycle_tracking(
            $reactivated,
            $beforerestore,
            (int)$user->id,
            $context,
            (int)$context->instanceid
        );
        $messages = $sink->get_messages();
        $sink->close();

        $history = array_values(event_manager::get_booking_history($saved->id));

        $this->assertCount(1, $messages);
        $this->assertSame((string)$user->id, $messages[0]->useridto);
        $this->assertSame('restored', $history[0]->action);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$history[0]->newstatus);
    }

    /**
     * An empty optional service-team group must not block status delivery to the remaining recipients.
     *
     * @return void
     */
    public function test_transition_booking_status_keeps_delivery_when_service_team_group_is_empty(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_bookit_event($roomid, (int)$user->id, ['name' => 'No service team available']);
        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, (int)$user->id, $context);
        $record = event_manager::get_event((int)$saved->id);

        $sink = $this->redirectMessages();
        event_manager::transition_booking_status(
            $record,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            (int)$user->id,
            $context,
            (int)$context->instanceid
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertSame((string)$user->id, $messages[0]->useridto);
    }

    /**
     * Rejected requests remain in the trash queue regardless of whether the requested slot is past or future.
     *
     * @return void
     */
    public function test_get_rejected_requests_keeps_past_and_future_items(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $common = [
            'semester' => 20261,
            'institutionid' => null,
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $futureid = $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Future rejected',
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]));
        $pastid = $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Past rejected',
            'starttime' => strtotime('2026-05-01 09:00:00'),
            'endtime' => strtotime('2026-05-01 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]));

        $rejected = array_values(event_manager::get_rejected_requests(strtotime('2026-05-07 10:00:00')));
        $this->assertCount(2, $rejected);
        $this->assertSame([$pastid, $futureid], array_map(static fn($event): int => (int)$event->id, $rejected));
        $this->assertSame(2, event_manager::count_rejected_requests(strtotime('2026-05-07 10:00:00')));
    }

    /**
     * Rejected requests must leave active overview projections immediately.
     *
     * @return void
     */
    public function test_filter_overview_events_hides_rejected_requests_from_active_projection(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $accepted = (object)[
            'id' => 101,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];
        $rejected = (object)[
            'id' => 102,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'starttime' => strtotime('2026-05-08 12:00:00'),
            'endtime' => strtotime('2026-05-08 14:00:00'),
        ];

        $active = event_manager::filter_overview_events([$accepted, $rejected], [], false, $referencetime);

        $this->assertCount(1, $active);
        $this->assertSame(101, (int)$active[0]->id);
        $this->assertTrue(event_manager::is_hidden_from_active_overview($rejected));
    }

    /**
     * Reactivated requests move from the rejected queue back to the open queue counts.
     *
     * @return void
     */
    public function test_reactivating_rejected_request_updates_queue_counts(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit queue test']);
        $context = context_module::instance($bookit->cmid);
        $referencetime = strtotime('2026-05-07 10:00:00');

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Reactivatable request',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->assertSame(0, event_manager::count_open_requests($referencetime));
        $this->assertSame(1, event_manager::count_rejected_requests($referencetime));

        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$user->id,
            $context
        );

        $this->assertSame(1, event_manager::count_open_requests($referencetime));
        $this->assertSame(0, event_manager::count_rejected_requests($referencetime));

        $DB->set_field(
            'bookit_event',
            'bookingstatus',
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            ['id' => $eventid]
        );
        $this->assertSame(1, event_manager::count_open_requests($referencetime));
    }

    /**
     * Status transitions keep workflow history and audit events aligned.
     *
     * @return void
     */
    public function test_transition_booking_status_records_history_and_triggers_audit_event(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit test']);
        $context = context_module::instance($bookit->cmid);

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Transition event',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $sink = $this->redirectEvents();
        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$user->id,
            $context
        );
        $events = $sink->get_events();
        $sink->close();

        $updated = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$updated->bookingstatus);

        $history = array_values(event_manager::get_booking_history($eventid));
        $this->assertCount(1, $history);
        $this->assertSame('reactivated', $history[0]->action);
        $this->assertSame(1, (int)$history[0]->recoverymarker);
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\mod_bookit\event\booking_reactivated::class, $events[0]);
    }

    /**
     * Restore requests must resolve to the last valid state stored before cancellation.
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_resolve_requested_booking_status_uses_last_pre_cancel_state(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Restorable event',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'canceled',
            (int)$user->id,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            ['bookingstatus' => ['from' => 2, 'to' => 3]]
        );

        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $this->assertSame(
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            event_manager::resolve_requested_booking_status($event, event_access_manager::BOOKINGSTATUS_NEW)
        );
    }

    /**
     * Self-cancelled New requests must leave active overview projections immediately.
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_filter_overview_events_hides_self_cancelled_new_requests_from_active_view(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Cancelled request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'self_canceled',
            (int)$user->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            ['bookingstatus' => ['from' => 0, 'to' => 3]]
        );

        $visible = event_manager::filter_overview_events([
            (object)[
                'id' => $eventid,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'starttime' => strtotime('2026-05-08 09:00:00'),
                'endtime' => strtotime('2026-05-08 11:00:00'),
            ],
        ], [], false, strtotime('2026-05-07 10:00:00'));

        $this->assertSame([], $visible);
    }

    /**
     * Self-cancelled New requests must keep a distinct history action from service-team cancellations.
     *
     * @return void
     */
    public function test_transition_booking_status_marks_requester_self_cancel_distinctly(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Self cancel']);
        $context = context_module::instance($bookit->cmid);
        $requester = $this->getDataGenerator()->create_user();

        $participantrole = \create_role('Bookit participant', 'bookitparticipant', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \role_assign($participantrole, $requester->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($requester);

        $eventid = $this->create_event_record([
            'name' => 'Requester self cancel',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $requester->id,
            'usermodified' => $requester->id,
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            (int)$requester->id,
            $context
        );

        $history = array_values(event_manager::get_booking_history($eventid));
        $this->assertCount(1, $history);
        $this->assertSame('self_canceled', $history[0]->action);
        $this->assertSame((int)$requester->id, (int)$history[0]->usermodified);
        $this->assertTrue(event_manager::is_hidden_from_active_overview(
            $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST)
        ));
    }
}
