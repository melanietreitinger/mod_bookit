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
 * Unit tests for event_access_manager.
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
use mod_bookit\local\install_helper;
use stdClass;

/**
 * Unit tests for event_access_manager.
 *
 * @covers \mod_bookit\local\manager\event_access_manager
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * Coverage metadata and PHPMD suppression for the large scenario-based test case.
 *
 * @covers \mod_bookit\local\manager\event_access_manager
 * @SuppressWarnings(PHPMD)
 */
final class event_access_manager_test extends advanced_testcase {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /**
     * Create a Bookit module context with a participant role that may open the overview and own-event details.
     *
     * @return context_module
     */
    private function create_bookit_context_with_participant_role(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant', 'bookitparticipant', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Assign the participant role to the given user in the module context.
     *
     * @param context_module $context
     * @param int $userid
     * @return void
     */
    private function assign_participant_role(context_module $context, int $userid): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'bookitparticipant'], 'id', MUST_EXIST);
        \role_assign($role->id, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * Create a Bookit module context with an observer role.
     *
     * @return context_module
     */
    private function create_bookit_context_with_observer_role(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit observer test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit observer', 'bookitobserver', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewrestrictedobserver', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Create a Bookit module context with a service-team role.
     *
     * @param string $roleshortname
     * @return context_module
     */
    private function create_bookit_context_with_service_role(string $roleshortname = 'bookitservice'): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit service test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit service team', $roleshortname, 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Create a Bookit module context with a support-on-site role.
     *
     * @return context_module
     */
    private function create_bookit_context_with_support_role(): context_module {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit support test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $role = $DB->get_record('role', ['shortname' => 'bookit_supportonsite']);
        if (!$role) {
            $roleid = \create_role('Bookit support on site', 'bookit_supportonsite', 'student');
        } else {
            $roleid = (int)$role->id;
        }
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Assign the support-on-site role to the given user.
     *
     * @param context_module $context
     * @param int $userid
     * @return void
     */
    private function assign_support_role(context_module $context, int $userid): void {
        $this->assign_role_by_shortname($context, $userid, 'bookit_supportonsite');
    }

    /**
     * Assign a role by shortname to the given user.
     *
     * @param context_module $context
     * @param int $userid
     * @param string $roleshortname
     * @return void
     */
    private function assign_role_by_shortname(context_module $context, int $userid, string $roleshortname): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => $roleshortname], 'id', MUST_EXIST);
        \role_assign($role->id, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * Test valid and invalid booking workflow transitions.
     *
     * @return void
     */
    public function test_can_transition_booking_status(): void {
        $this->assertTrue(event_access_manager::can_transition_booking_status(
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS
        ));
        $this->assertTrue(event_access_manager::can_transition_booking_status(
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_ACCEPTED
        ));
        $this->assertTrue(event_access_manager::can_transition_booking_status(
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_REJECTED
        ));
        $this->assertTrue(event_access_manager::can_transition_booking_status(
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            event_access_manager::BOOKINGSTATUS_CANCELED
        ));
        $this->assertTrue(event_access_manager::can_transition_booking_status(
            event_access_manager::BOOKINGSTATUS_REJECTED,
            event_access_manager::BOOKINGSTATUS_NEW
        ));

        $this->assertFalse(event_access_manager::can_transition_booking_status(
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            event_access_manager::BOOKINGSTATUS_NEW
        ));
        $this->assertFalse(event_access_manager::can_transition_booking_status(
            event_access_manager::BOOKINGSTATUS_REJECTED,
            event_access_manager::BOOKINGSTATUS_ACCEPTED
        ));
    }

    /**
     * Test open-request status helper methods.
     *
     * @return void
     */
    public function test_open_request_helpers(): void {
        $event = (object)['bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW];
        $this->assertTrue(event_access_manager::is_open_request($event));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_IN_PROGRESS;
        $this->assertTrue(event_access_manager::is_open_request($event));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;
        $this->assertFalse(event_access_manager::is_open_request($event));
    }

    /**
     * Governed read filters and response helpers must stay stable for all read contracts.
     *
     * @return void
     */
    public function test_governed_read_filter_helpers_normalise_and_shape_payloads(): void {
        $filters = event_access_manager::normalise_governed_read_filters([
            'start' => '2026-05-20T10:15:00',
            'end' => '2026-05-21T12:30:00',
            'roomids' => '1,2,2',
            'facultyids' => ['3', '4'],
            'bookingstatuses' => ['0', '2'],
            'search' => '  Physics  ',
            'workspace' => ' openrequests ',
            'exportmode' => 1,
        ]);

        $this->assertSame('2026-05-20 10:15', $filters['start']);
        $this->assertSame('2026-05-21 12:30', $filters['end']);
        $this->assertSame([1, 2], $filters['roomids']);
        $this->assertSame([3, 4], $filters['facultyids']);
        $this->assertSame([0, 2], $filters['bookingstatuses']);
        $this->assertSame('Physics', $filters['search']);
        $this->assertSame('openrequests', $filters['workspace']);
        $this->assertTrue($filters['exportmode']);

        $empty = event_access_manager::build_governed_empty_response('calendar', $filters);
        $denied = event_access_manager::build_governed_denied_response('calendar', $filters);

        $this->assertSame('ok', $empty['status']);
        $this->assertFalse($empty['denied']);
        $this->assertSame('no_matches', $empty['emptyreason']);
        $this->assertSame('denied', $denied['status']);
        $this->assertTrue($denied['denied']);
        $this->assertSame('access_denied', $denied['emptyreason']);
    }

    /**
     * Rejected-request trash membership is status-based and does not expire by end time.
     *
     * @return void
     */
    public function test_is_rejected_request_is_status_based(): void {
        $rejected = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'endtime' => strtotime('2026-05-01 12:00:00'),
        ];
        $accepted = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'endtime' => strtotime('2026-06-01 12:00:00'),
        ];

        $this->assertTrue(event_access_manager::is_rejected_request($rejected, strtotime('2026-06-07 12:00:01')));
        $this->assertFalse(event_access_manager::is_rejected_request($accepted, strtotime('2026-05-07 12:00:01')));
    }

    /**
     * Test participant role resolution.
     *
     * @return void
     */
    public function test_get_user_roles_for_event(): void {
        $event = new stdClass();
        $event->personinchargeid = 11;
        $event->usermodified = 11;
        $event->otherexaminers = '12,13';
        $event->supportpersons = '13,14';

        $this->assertSame(
            ['personincharge', 'bookingperson'],
            event_access_manager::get_user_roles_for_event($event, 11)
        );
        $this->assertSame(
            ['otherexaminer', 'supportperson'],
            event_access_manager::get_user_roles_for_event($event, 13)
        );
        $this->assertSame([], event_access_manager::get_user_roles_for_event($event, 99));
    }

    /**
     * Test participant edit permissions.
     *
     * @return void
     */
    public function test_can_participant_edit_event(): void {
        $event = new stdClass();
        $event->personinchargeid = 10;
        $event->usermodified = 11;
        $event->otherexaminers = '12';
        $event->supportpersons = '13';
        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_NEW;

        $this->assertTrue(event_access_manager::can_participant_edit_event($event, 10));
        $this->assertTrue(event_access_manager::can_participant_edit_event($event, 11));
        $this->assertTrue(event_access_manager::can_participant_edit_event($event, 12));
        $this->assertFalse(event_access_manager::can_participant_edit_event($event, 13));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;
        $this->assertFalse(event_access_manager::can_participant_edit_event($event, 10));
    }

    /**
     * Historical New requests are blocked only for participant edit paths, not for service-team overrides.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_should_block_participant_past_edit(): void {
        $this->resetAfterTest(true);

        $participantcontext = $this->create_bookit_context_with_participant_role();
        $participant = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($participantcontext, $participant->id);
        $this->setUser($participant);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => time() - 3600,
            'personinchargeid' => $participant->id,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertTrue(event_access_manager::should_block_participant_past_edit($event, $participantcontext, $participant->id));

        $event->starttime = time() + 3600;
        $this->assertFalse(event_access_manager::should_block_participant_past_edit($event, $participantcontext, $participant->id));

        $event->starttime = time() - 3600;
        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;
        $this->assertFalse(event_access_manager::should_block_participant_past_edit($event, $participantcontext, $participant->id));

        $servicecontext = $this->create_bookit_context_with_service_role('bookitservicepastedit');
        $serviceuser = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($servicecontext, $serviceuser->id, 'bookitservicepastedit');
        $this->setUser($serviceuser);

        $serviceevent = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => time() - 3600,
            'personinchargeid' => $serviceuser->id,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertFalse(event_access_manager::should_block_participant_past_edit(
            $serviceevent,
            $servicecontext,
            $serviceuser->id
        ));
    }

    /**
     * Support-only users see assigned New/In progress/Accepted in overview and calendar.
     *
     * @return void
     */
    public function test_supportperson_visibility_requires_accessible_booking(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);
        $this->setUser($user);

        $event = new stdClass();
        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_NEW;
        $event->personinchargeid = 999;
        $event->usermodified = 998;
        $event->otherexaminers = '';
        $event->supportpersons = (string)$user->id;

        $this->assertFalse(event_access_manager::can_user_view_event_details($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_history($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_supportperson_view_internal_fields($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_supportperson_edit_internal_notes($event, $context, $user->id));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_IN_PROGRESS;

        $this->assertTrue(event_access_manager::can_user_view_event_details($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_history($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_view_internal_fields($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_edit_internal_notes($event, $context, $user->id));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;

        $this->assertTrue(event_access_manager::can_user_view_event_details($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_history($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_view_internal_fields($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_edit_internal_notes($event, $context, $user->id));
    }

    /**
     * Module support users see unassigned New bookings in the calendar.
     *
     * @return void
     */
    public function test_support_unassigned_new_visible_in_calendar(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_support_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_support_role($context, $user->id);
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 0,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
    }

    /**
     * Module support users see unassigned In progress bookings in the calendar.
     *
     * @return void
     */
    public function test_support_unassigned_in_progress_visible_in_calendar(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_support_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_support_role($context, $user->id);
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            'personinchargeid' => 0,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
    }

    /**
     * Assigned support users see New bookings in overview.
     *
     * @return void
     */
    public function test_support_assigned_new_visible_in_overview(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 0,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => (string)$user->id,
        ];

        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
    }

    /**
     * Module support users do not see unassigned Accepted bookings in the calendar.
     *
     * @return void
     */
    public function test_support_unassigned_accepted_hidden_in_calendar(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_support_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_support_role($context, $user->id);
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'personinchargeid' => 0,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
    }

    /**
     * Support users do not see Canceled or Rejected bookings in the calendar.
     *
     * @return void
     */
    public function test_support_canceled_rejected_hidden_in_calendar(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_support_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_support_role($context, $user->id);
        $this->setUser($user);

        $canceled = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => 0,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];
        $rejected = clone $canceled;
        $rejected->bookingstatus = event_access_manager::BOOKINGSTATUS_REJECTED;

        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($canceled, $context, $user->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($rejected, $context, $user->id));
    }

    /**
     * Assigned support users see Accepted bookings in the calendar.
     *
     * @return void
     */
    public function test_support_assigned_accepted_visible_in_calendar(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_support_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_support_role($context, $user->id);
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'personinchargeid' => 0,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => (string)$user->id,
        ];

        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
    }

    /**
     * Module support users do not see unassigned New bookings in overview.
     *
     * @return void
     */
    public function test_support_unassigned_new_hidden_in_overview(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_support_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_support_role($context, $user->id);
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 0,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertFalse(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
    }

    /**
     * Assigned support users see New, In progress, and Accepted bookings in overview.
     *
     * @return void
     */
    public function test_support_assigned_open_and_accepted_visible_in_overview(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);
        $this->setUser($user);

        foreach (
            [
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            ] as $status
        ) {
            $event = (object)[
                'bookingstatus' => $status,
                'personinchargeid' => 0,
                'usermodified' => 999,
                'otherexaminers' => '',
                'supportpersons' => (string)$user->id,
            ];

            $this->assertTrue(
                event_access_manager::can_user_view_event_in_overview($event, $context, $user->id),
                'Expected overview visibility for status ' . $status
            );
        }
    }

    /**
     * Observer restricted mode is driven only by the dedicated capability and blocks details.
     *
     * @return void
     */
    public function test_observer_restricted_mode_and_visibility_rules(): void {
        $this->resetAfterTest(true);

        $observercontext = $this->create_bookit_context_with_observer_role();
        $observer = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($observercontext, $observer->id, 'bookitobserver');
        $this->setUser($observer);

        $accepted = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'personinchargeid' => 0,
            'usermodified' => 0,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];
        $newrequest = clone $accepted;
        $newrequest->bookingstatus = event_access_manager::BOOKINGSTATUS_NEW;

        $this->assertTrue(event_access_manager::is_observer_restricted_mode($observercontext));
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($accepted, $observercontext, $observer->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($accepted, $observercontext, $observer->id));
        $this->assertFalse(event_access_manager::can_user_view_event_details($accepted, $observercontext, $observer->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_history($accepted, $observercontext, $observer->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_overview($newrequest, $observercontext, $observer->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($newrequest, $observercontext, $observer->id));

        $participantcontext = $this->create_bookit_context_with_participant_role();
        $participant = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($participantcontext, $participant->id);
        $this->setUser($participant);
        $this->assertFalse(event_access_manager::is_observer_restricted_mode($participantcontext));
    }

    /**
     * Multi-role users keep visibility even if the support role would otherwise be restricted.
     *
     * @return void
     */
    public function test_multi_role_user_keeps_visibility_union(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);
        $this->setUser($user);

        $event = new stdClass();
        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_IN_PROGRESS;
        $event->personinchargeid = 999;
        $event->usermodified = 998;
        $event->otherexaminers = (string)$user->id;
        $event->supportpersons = (string)$user->id;

        $this->assertTrue(event_access_manager::can_user_view_event_details($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_history($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_view_internal_fields($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_edit_internal_notes($event, $context, $user->id));
    }

    /**
     * Participants may only cancel bookings once the request left the editable New state.
     *
     * @return void
     */
    public function test_can_participant_cancel_only(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);
        $this->setUser($user);

        $event = new stdClass();
        $event->personinchargeid = 999;
        $event->usermodified = $user->id;
        $event->otherexaminers = '';
        $event->supportpersons = '';
        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_NEW;

        $this->assertFalse(event_access_manager::can_participant_cancel_only($event, $context, $user->id));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;
        $this->assertTrue(event_access_manager::can_cancel_event($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_participant_cancel_only($event, $context, $user->id));
    }

    /**
     * Editable New requests expose a self-cancel path for participant roles.
     *
     * @return void
     */
    public function test_can_self_cancel_new_request(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $user->id,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertTrue(event_access_manager::can_self_cancel_new_request($event, $context, $user->id));
        foreach (
            [
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            event_access_manager::BOOKINGSTATUS_REJECTED,
            ] as $status
        ) {
            $event->bookingstatus = $status;
            $this->assertFalse(event_access_manager::can_self_cancel_new_request($event, $context, $user->id));
        }
    }

    /**
     * Overview cancel is available for self-cancel and cancel-only participant paths.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_can_participant_overview_cancel(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $user->id,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
            'starttime' => strtotime('+1 day'),
        ];

        $this->assertTrue(event_access_manager::can_participant_overview_cancel($event, $context, $user->id));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;
        $this->assertTrue(event_access_manager::can_participant_overview_cancel($event, $context, $user->id));

        foreach (
            [
                event_access_manager::BOOKINGSTATUS_CANCELED,
                event_access_manager::BOOKINGSTATUS_REJECTED,
            ] as $status
        ) {
            $event->bookingstatus = $status;
            $this->assertFalse(event_access_manager::can_participant_overview_cancel($event, $context, $user->id));
        }
    }

    /**
     * Overview cancel is not offered in observer or unrelated participant contexts.
     *
     * @return void
     */
    public function test_can_participant_overview_cancel_boundaries(): void {
        $this->resetAfterTest(true);
        $observercontext = $this->create_bookit_context_with_observer_role();
        $observer = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($observercontext, $observer->id, 'bookitobserver');

        $acceptedevent = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'personinchargeid' => 999,
            'usermodified' => 999,
            'otherexaminers' => '',
            'supportpersons' => '',
            'starttime' => strtotime('+1 day'),
        ];
        $this->assertFalse(
            event_access_manager::can_participant_overview_cancel($acceptedevent, $observercontext, $observer->id)
        );

        $participantcontext = $this->create_bookit_context_with_participant_role();
        $outsider = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($participantcontext, $outsider->id);
        $this->assertFalse(
            event_access_manager::can_participant_overview_cancel($acceptedevent, $participantcontext, $outsider->id)
        );
    }

    /**
     * Past booking management is reserved for service-team style capabilities.
     *
     * @return void
     */
    public function test_can_manage_past_bookings_requires_service_capabilities(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit test']);
        $context = context_module::instance($bookit->cmid);

        $servicerole = \create_role('Bookit service team', 'bookitservice', 'manager');
        \assign_capability('mod/bookit:editevent', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $servicerole, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $requester = $this->getDataGenerator()->create_user();
        $serviceuser = $this->getDataGenerator()->create_user();
        \role_assign($servicerole, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($requester);
        $this->assertFalse(event_access_manager::can_manage_past_bookings($context));
        $this->setUser($serviceuser);
        $this->assertTrue(event_access_manager::can_manage_past_bookings($context));
    }

    /**
     * Open-request navigation is available through either service-team owner capability.
     *
     * @return void
     */
    public function test_can_manage_open_requests_requires_service_capabilities(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Bookit request tab test',
        ]);
        $context = context_module::instance($bookit->cmid);

        $participantrole = \create_role('Bookit overview only', 'bookitoverviewonly', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);

        $detailservicerole = \create_role('Bookit detail manager', 'bookitdetailmanager', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $detailservicerole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $detailservicerole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $detailservicerole, $context->id, true);

        $basicservicerole = \create_role('Bookit basic manager', 'bookitbasicmanager', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $basicservicerole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $basicservicerole, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $basicservicerole, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $participant = $this->getDataGenerator()->create_user();
        $detailserviceuser = $this->getDataGenerator()->create_user();
        $basicserviceuser = $this->getDataGenerator()->create_user();
        \role_assign($participantrole, $participant->id, $context->id);
        \role_assign($detailservicerole, $detailserviceuser->id, $context->id);
        \role_assign($basicservicerole, $basicserviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($participant);
        $this->assertFalse(event_access_manager::can_manage_open_requests($context));

        $this->setUser($detailserviceuser);
        $this->assertTrue(event_access_manager::can_manage_open_requests($context));

        $this->setUser($basicserviceuser);
        $this->assertTrue(event_access_manager::can_manage_open_requests($context));
    }

    /**
     * Rejected requests leave active views, and unrelated open requests stay in the service queue instead
     * of leaking into the service-team calendar.
     *
     * @return void
     */
    public function test_rejected_requests_are_hidden_from_active_views_but_reactivatable_for_service_team(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_service_role();
        $serviceuser = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($context, $serviceuser->id, 'bookitservice');
        $this->setUser($serviceuser);

        $rejected = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'endtime' => strtotime('2026-05-01 12:00:00'),
            'personinchargeid' => 0,
            'usermodified' => 0,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];
        $openrequest = clone $rejected;
        $openrequest->bookingstatus = event_access_manager::BOOKINGSTATUS_NEW;

        $this->assertFalse(event_access_manager::can_user_view_event_in_overview($rejected, $context, $serviceuser->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($rejected, $context, $serviceuser->id));
        $this->assertTrue(event_access_manager::can_reactivate_rejected_request($rejected, $context));

        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($openrequest, $context, $serviceuser->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($openrequest, $context, $serviceuser->id));
        $this->assertFalse(event_access_manager::can_reactivate_rejected_request($openrequest, $context));
    }

    /**
     * A full-detail role must still win over observer restrictions in mixed-role assignments.
     *
     * @return void
     */
    public function test_full_detail_role_overrides_observer_restriction_in_mixed_assignments(): void {
        $this->resetAfterTest(true);

        $context = $this->create_bookit_context_with_observer_role();
        $detailrole = \create_role('Bookit detail service', 'bookitdetailservice', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $detailrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $detailrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $detailrole, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $user = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($context, $user->id, 'bookitobserver');
        \role_assign($detailrole, $user->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 0,
            'usermodified' => 0,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $this->assertFalse(event_access_manager::is_observer_restricted_mode($context));
        $this->assertTrue(event_access_manager::can_user_view_event_details($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_history($event, $context, $user->id));
    }

    /**
     * Booking persons must keep participant visibility after service-team status transitions.
     *
     * @return void
     */
    public function test_booking_person_retains_visibility_after_service_team_status_transitions(): void {
        global $DB;

        $this->resetAfterTest(true);

        $context = $this->create_bookit_context_with_service_role('bookitservicevis007');
        $participantrole = \create_role('Bookit participant vis', 'bookitparticipantvis007', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $serviceuser = $this->getDataGenerator()->create_user();
        $bookingperson = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($context, $serviceuser->id, 'bookitservicevis007');
        \role_assign($participantrole, $bookingperson->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Visibility transition exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
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
            'usermodified' => $bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->setUser($serviceuser);
        foreach (
            [
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            ] as $targetstatus
        ) {
            $event = event_manager::transition_booking_status(
                $event,
                $targetstatus,
                (int)$serviceuser->id,
                $context
            );
            $this->assertSame((int)$bookingperson->id, (int)$event->usermodified);

            $this->setUser($bookingperson);
            $this->assertTrue(
                event_access_manager::can_user_view_event_in_overview($event, $context, (int)$bookingperson->id),
                'Overview visibility must remain after transition to status ' . $targetstatus
            );
            $this->assertTrue(
                event_access_manager::can_user_view_event_in_calendar($event, $context, (int)$bookingperson->id),
                'Calendar visibility must remain after transition to status ' . $targetstatus
            );
            $this->setUser($serviceuser);
        }
    }

    /**
     * Foreign form saves must keep overview and history visibility for the booking person.
     *
     * @return void
     */
    public function test_foreign_form_save_keeps_booking_person_overview_and_history_visibility(): void {
        global $DB;

        $this->resetAfterTest(true);

        $context = $this->create_bookit_context_with_participant_role();
        $bookingperson = $this->getDataGenerator()->create_user();
        $admin = get_admin();
        $this->assign_participant_role($context, (int)$bookingperson->id);

        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Foreign overview room',
            'shortname' => 'FOR001',
        ]);

        $this->setUser($bookingperson);
        $event = new \mod_bookit\local\entity\bookit_event(
            0,
            'Foreign overview exam',
            20261,
            1,
            strtotime('2026-05-20 09:00:00'),
            strtotime('2026-05-20 11:00:00'),
            120,
            $roomid,
            10,
            0,
            '',
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            null,
            '',
            0,
            'External note',
            'Internal note',
            '',
            0,
            0,
            null,
            (int)$bookingperson->id,
            time(),
            time(),
            []
        );
        $saved = event_manager::save_event_with_lifecycle_tracking(
            $event,
            null,
            (int)$bookingperson->id,
            $context
        );

        $before = \mod_bookit\local\entity\bookit_event::from_database($saved->id);
        $updated = \mod_bookit\local\entity\bookit_event::from_database($saved->id);
        $updated->notes = 'Foreign save overview check';

        $this->setUser($admin);
        event_manager::save_event_with_lifecycle_tracking(
            $updated,
            $before,
            (int)$admin->id,
            $context
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $saved->id], '*', MUST_EXIST);
        $this->assertSame((int)$bookingperson->id, (int)$persisted->usermodified);

        $this->setUser($bookingperson);
        $this->assertTrue(
            event_access_manager::can_user_view_event_in_overview($persisted, $context, (int)$bookingperson->id)
        );
        $this->assertTrue(
            event_access_manager::can_user_view_event_in_history($persisted, $context, (int)$bookingperson->id)
        );
    }

    /**
     * Assigned examiners must keep visibility after service-team status transitions.
     *
     * @return void
     */
    public function test_examiner_participants_retain_visibility_after_service_team_status_transitions(): void {
        global $DB;

        $this->resetAfterTest(true);

        $context = $this->create_bookit_context_with_service_role('bookitserviceexam007');
        $participantrole = \create_role('Bookit participant exam', 'bookitparticipantexam007', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $serviceuser = $this->getDataGenerator()->create_user();
        $bookingperson = $this->getDataGenerator()->create_user();
        $examiner = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($context, $serviceuser->id, 'bookitserviceexam007');
        \role_assign($participantrole, $bookingperson->id, $context->id);
        \role_assign($participantrole, $examiner->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Examiner visibility transition',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-21 09:00:00'),
            'endtime' => strtotime('2026-05-21 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $examiner->id,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->setUser($serviceuser);
        $event = event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            (int)$serviceuser->id,
            $context
        );

        $this->setUser($examiner);
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, (int)$examiner->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, (int)$examiner->id));
    }

    /**
     * Event checklist and resources pages must disappear completely when the optional module parts are disabled.
     *
     * @return void
     */
    public function test_optional_module_toggles_block_event_checklist_and_resources(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_participant_role();
        $user = $this->getDataGenerator()->create_user();
        $this->assign_participant_role($context, $user->id);
        $this->setUser($user);

        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'personinchargeid' => 999,
            'usermodified' => $user->id,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        set_config(install_helper::CONFIG_CHECKLIST_ENABLED, 0, 'mod_bookit');
        set_config(install_helper::CONFIG_RESOURCES_ENABLED, 0, 'mod_bookit');
        $this->assertFalse(event_access_manager::can_view_event_checklist($event, $context, (int)$user->id));
        $this->assertFalse(event_access_manager::can_view_event_resources($event, $context, (int)$user->id));

        set_config(install_helper::CONFIG_CHECKLIST_ENABLED, 1, 'mod_bookit');
        set_config(install_helper::CONFIG_RESOURCES_ENABLED, 1, 'mod_bookit');
        $this->assertTrue(event_access_manager::can_view_event_checklist($event, $context, (int)$user->id));
        $this->assertTrue(event_access_manager::can_view_event_resources($event, $context, (int)$user->id));
    }

    /**
     * Service-team users must not see canceled bookings in the active calendar projection.
     *
     * @return void
     */
    public function test_service_team_cannot_view_canceled_event_in_calendar(): void {
        $this->resetAfterTest(true);
        $context = $this->create_bookit_context_with_service_role('bookitservice016');
        $serviceuser = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($context, $serviceuser->id, 'bookitservice016');
        $this->setUser($serviceuser);

        $canceled = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'personinchargeid' => 0,
            'usermodified' => 0,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];
        $accepted = clone $canceled;
        $accepted->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;

        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($canceled, $context, $serviceuser->id));
        $this->assertTrue(event_access_manager::can_user_view_event_details($canceled, $context, $serviceuser->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($accepted, $context, $serviceuser->id));
    }

    /**
     * Reactivating a canceled booking must restore service-team calendar visibility.
     *
     * @return void
     */
    public function test_service_team_calendar_visibility_returns_after_canceled_reactivation(): void {
        global $DB;

        $this->resetAfterTest(true);

        $context = $this->create_bookit_context_with_service_role('bookitservice016react');
        $serviceuser = $this->getDataGenerator()->create_user();
        $this->assign_role_by_shortname($context, $serviceuser->id, 'bookitservice016react');
        $this->setUser($serviceuser);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Reactivate canceled exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
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
            'usermodified' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($event, $context, $serviceuser->id));

        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$serviceuser->id,
            $context
        );
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $serviceuser->id));
    }
}
