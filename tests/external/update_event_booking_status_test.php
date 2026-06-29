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

namespace mod_bookit\external;

use advanced_testcase;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;

/**
 * Regression tests for booking-status updates during governed-read rollout.
 *
 * @covers \mod_bookit\external\update_event_booking_status
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_event_booking_status_test extends advanced_testcase {
    /**
     * Booking-status updates must coexist with governed queue refreshes.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_updates_status_and_refreshes_governed_queues(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Workflow queue']);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Queue refresh event',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $before = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 1, '', '');
        $this->assertSame(1, (int)$before['summary']['openrequestcount']);
        $this->assertSame($eventid, (int)$before['items'][0]['eventid']);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'openrequests',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_ACCEPTED, (int)$response['status']);
        $this->assertSame('openrequests', $response['tab']);
        $this->assertNotEmpty($response['queue']);
        $this->assertSame(0, (int)$response['queue']['summary']['openrequestcount']);
        $this->assertSame([], $response['queue']['items']);

        $after = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 1, '', '');
        $this->assertSame(0, (int)$after['summary']['openrequestcount']);
        $this->assertSame([], $after['items']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_ACCEPTED, (int)$record->bookingstatus);
    }

    /**
     * Participants may cancel their own bookings from the personal overview without managebasics.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_allows_participant_overview_cancel(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Participant cancel']);
        $context = \context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant', 'bookitparticipant', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $user = $this->getDataGenerator()->create_user();
        \role_assign($roleid, $user->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Participant overview cancel',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('+1 day'),
            'endtime' => strtotime('+1 day +2 hours'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $user->id,
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

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            'myevents',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_CANCELED, (int)$response['status']);
        $this->assertSame('myevents', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CANCELED, (int)$record->bookingstatus);
    }

    /**
     * Unauthorized users cannot cancel bookings they do not own via the participant path.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_rejects_unauthorized_participant_overview_cancel(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Unauthorized cancel']);
        $context = \context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant', 'bookitparticipant', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $outsider = $this->getDataGenerator()->create_user();
        \role_assign($roleid, $outsider->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->getDataGenerator()->enrol_user($outsider->id, $course->id, 'student');
        $this->setUser($outsider);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Foreign booking',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('+1 day'),
            'endtime' => strtotime('+1 day +2 hours'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 999,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => 999,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\required_capability_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            'myevents',
            1
        );
    }

    /**
     * Booking persons may reactivate their own rejected requests without managebasics.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_booking_person_rejected_to_new_via_api(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Booker reactivate']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($bookingperson->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant reactivate', 'bookitparticipantreactivate', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $bookingperson->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected reactivate exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->setUser($bookingperson);
        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('history', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus,usermodified', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
        $this->assertSame((int)$bookingperson->id, (int)$record->usermodified);
    }

    /**
     * Booking persons may reactivate their own canceled bookings from History without managebasics.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_booking_person_canceled_to_new_via_history_tab(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Booker canceled reactivate',
        ]);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($bookingperson->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant canceled reactivate', 'bookitparticipantcancelreact', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $bookingperson->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Canceled reactivate exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'status_changed',
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $this->setUser($bookingperson);
        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('history', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus,usermodified', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
        $this->assertSame((int)$bookingperson->id, (int)$record->usermodified);
    }

    /**
     * Non-owners cannot reactivate canceled bookings via History tab.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_non_owner_canceled_to_new_via_history_tab_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);

        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Non-owner canceled']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($other->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant non-owner', 'bookitparticipantnonowner', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $other->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Foreign canceled exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$owner->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->setUser($other);
        $this->expectException(\required_capability_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history',
            1
        );
    }

    /**
     * Service team must restore booker-canceled requests to New from the terminal tab.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_service_team_canceled_to_new_from_rejected_tab(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $booker = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Terminal restore']);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Canceled terminal request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'status_changed',
            (int)$booker->id,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'rejectedrequests',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('rejectedrequests', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
    }

    /**
     * Service team may restore canceled bookings from History tab.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_service_team_canceled_to_new_via_history_tab(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $booker = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'History service restore',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Canceled history restore',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('history', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
    }

    /**
     * Service team may restore rejected bookings from History tab.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_service_team_rejected_to_new_via_history_tab(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $booker = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'History service rejected',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected history restore',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('history', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
    }

    /**
     * Service team may restore rejected bookings from the rejected-requests tab.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_service_team_rejected_to_new_from_rejected_tab(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $booker = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Rejected tab restore']);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected terminal request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'rejectedrequests',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('rejectedrequests', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
    }

    /**
     * Unauthorized users cannot restore from the rejected-requests tab.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_rejects_unauthorized_rejected_tab_restore(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Rejected tab deny']);
        $context = \context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant', 'bookitparticipant050', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $owner = $this->getDataGenerator()->create_user();
        $outsider = $this->getDataGenerator()->create_user();
        \role_assign($roleid, $outsider->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->getDataGenerator()->enrol_user($outsider->id, $course->id, 'student');
        $this->setUser($outsider);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected foreign request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$owner->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\required_capability_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'rejectedrequests',
            1
        );
    }
}
