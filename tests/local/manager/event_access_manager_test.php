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
use stdClass;

/**
 * Unit tests for event_access_manager.
 *
 * @covers \mod_bookit\local\manager\event_access_manager
 */
final class event_access_manager_test extends advanced_testcase {
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
     * Rejected requests remain visible until their requested slot is over.
     *
     * @return void
     */
    public function test_is_rejected_request_uses_requested_end_time(): void {
        $event = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'endtime' => strtotime('2026-05-07 12:00:00'),
        ];

        $this->assertTrue(event_access_manager::is_rejected_request($event, strtotime('2026-05-07 11:59:59')));
        $this->assertFalse(event_access_manager::is_rejected_request($event, strtotime('2026-05-07 12:00:01')));
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
     * Support persons may only see accepted bookings unless another participant role also applies.
     *
     * @return void
     */
    public function test_supportperson_visibility_requires_accepted_booking(): void {
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
        $this->assertFalse(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_user_view_event_in_history($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_supportperson_view_internal_fields($event, $context, $user->id));
        $this->assertFalse(event_access_manager::can_supportperson_edit_internal_notes($event, $context, $user->id));

        $event->bookingstatus = event_access_manager::BOOKINGSTATUS_ACCEPTED;

        $this->assertTrue(event_access_manager::can_user_view_event_details($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_overview($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_calendar($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_user_view_event_in_history($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_view_internal_fields($event, $context, $user->id));
        $this->assertTrue(event_access_manager::can_supportperson_edit_internal_notes($event, $context, $user->id));
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
}
