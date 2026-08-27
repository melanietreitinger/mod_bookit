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
use core_external\external_api;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;

/**
 * Regression tests for booking-status updates returning to the canonical workspace.
 *
 * @covers \mod_bookit\external\update_event_booking_status
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_event_booking_status_test extends advanced_testcase {
    /**
     * Booking-status updates return only the canonical redirect contract.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_updates_status_and_returns_canonical_redirect(): void {
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
            'usercreated' => 0,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'openrequests'
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_CONFIRMED, (int)$response['status']);
        $this->assertSame('openrequests', $response['tab']);
        $this->assertStringContainsString('tab=openrequests', $response['redirecturl']);
        $this->assertStringNotContainsString('&amp;', $response['redirecturl']);
        $this->assertSame(['status', 'tab', 'redirecturl'], array_keys($response));

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CONFIRMED, (int)$record->bookingstatus);
    }

    /**
     * Status updates do not carry obsolete page state into the canonical redirect.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_redirect_does_not_include_page_state(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Queue page preserve']);

        $targeteventid = 0;
        for ($i = 1; $i <= 26; $i++) {
            $eventid = (int)$DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Preserve page %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => strtotime('2026-05-20 09:00:00') + $i,
                'endtime' => strtotime('2026-05-20 11:00:00') + $i,
                'duration' => 120,
                'roomid' => null,
                'participantsamount' => 12,
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
                'usercreated' => 0,
            'usermodified' => get_admin()->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
            if ($i === 26) {
                $targeteventid = $eventid;
            }
        }

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $targeteventid,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            'openrequests'
        );

        $this->assertStringNotContainsString('page=', $response['redirecturl']);
        $this->assertSame(['status', 'tab', 'redirecturl'], array_keys($response));
    }

    /**
     * Status updates use the same canonical redirect regardless of result count.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_redirect_is_independent_of_result_count(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Queue page correction',
        ]);

        $targeteventid = 0;
        for ($i = 1; $i <= 26; $i++) {
            $eventid = (int)$DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Correct page %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => strtotime('2026-06-20 09:00:00') + $i,
                'endtime' => strtotime('2026-06-20 11:00:00') + $i,
                'duration' => 120,
                'roomid' => null,
                'participantsamount' => 12,
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
                'usercreated' => 0,
            'usermodified' => get_admin()->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
            if ($i === 1) {
                $targeteventid = $eventid;
            }
        }

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $targeteventid,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'openrequests'
        );

        $this->assertStringNotContainsString('page=1', $response['redirecturl']);
        $this->assertSame('openrequests', $response['tab']);
        $this->assertSame(['status', 'tab', 'redirecturl'], array_keys($response));
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            'myevents'
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
            'usercreated' => 0,
            'usermodified' => 999,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\required_capability_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            'myevents'
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
            'usercreated' => (int)$bookingperson->id,
            'usermodified' => (int)$bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->setUser($bookingperson);
        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history'
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('history', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus,usercreated,usermodified', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
        $this->assertSame((int)$bookingperson->id, (int)$record->usercreated);
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
            'usercreated' => (int)$bookingperson->id,
            'usermodified' => (int)$bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'status_changed',
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $this->setUser($bookingperson);
        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history'
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('history', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus,usercreated,usermodified', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
        $this->assertSame((int)$bookingperson->id, (int)$record->usercreated);
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
            'usercreated' => (int)$owner->id,
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
            'history'
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
            'usercreated' => (int)$booker->id,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'status_changed',
            (int)$booker->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'rejectedcancelled'
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('rejectedcancelled', $response['tab']);

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
            'usercreated' => (int)$booker->id,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history'
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
            'usercreated' => (int)$booker->id,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'history'
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
            'usercreated' => (int)$booker->id,
            'usermodified' => (int)$booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'rejectedcancelled'
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$response['status']);
        $this->assertSame('rejectedcancelled', $response['tab']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$record->bookingstatus);
    }

    /**
     * Participants cannot use workspace tabs such as rejectedcancelled.
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
            'usercreated' => (int)$owner->id,
            'usermodified' => (int)$owner->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\invalid_parameter_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'rejectedcancelled'
        );
    }

    /**
     * Support-only viewers cannot change booking status via the API.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_support_only_denied_status_change(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Support status deny']);
        $context = \context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $supportrole = $DB->get_record('role', ['shortname' => 'bookit_supportonsite']);
        if (!$supportrole) {
            $supportroleid = \create_role('Bookit support on site', 'bookit_supportonsite', 'student');
        } else {
            $supportroleid = (int)$supportrole->id;
        }
        \assign_capability('mod/bookit:view', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $supportroleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $supportuser = $this->getDataGenerator()->create_user();
        \role_assign($supportroleid, $supportuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->getDataGenerator()->enrol_user($supportuser->id, $course->id, 'student');
        $this->setUser($supportuser);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Support read-only request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
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
            'usercreated' => 0,
            'usermodified' => (int)$supportuser->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\required_capability_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'openrequests'
        );
    }

    /**
     * Support-only viewers cannot reactivate rejected requests.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_support_only_denied_reactivate(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module(
            'bookit',
            ['course' => $course->id, 'name' => 'Support reactivate deny']
        );
        $context = \context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $supportrole = $DB->get_record('role', ['shortname' => 'bookit_supportonsite']);
        if (!$supportrole) {
            $supportroleid = \create_role('Bookit support on site', 'bookit_supportonsite', 'student');
        } else {
            $supportroleid = (int)$supportrole->id;
        }
        \assign_capability('mod/bookit:view', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $supportroleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $supportuser = $this->getDataGenerator()->create_user();
        \role_assign($supportroleid, $supportuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->getDataGenerator()->enrol_user($supportuser->id, $course->id, 'student');
        $this->setUser($supportuser);

        $owner = $this->getDataGenerator()->create_user();
        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected support read-only',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
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
            'usercreated' => (int)$owner->id,
            'usermodified' => (int)$owner->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\required_capability_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            'rejectedcancelled'
        );
    }

    /**
     * Service-team status updates without tab default to allrequests.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_service_team_status_update_without_tab_defaults_to_allrequests(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Tab default service']);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Tab default open request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
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
            'usercreated' => 0,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$response['status']);
        $this->assertSame('allrequests', $response['tab']);
    }

    /**
     * Participant status updates without tab default to myevents.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_participant_status_update_without_tab_defaults_to_myevents(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Tab default participant',
        ]);
        $context = \context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant tab default', 'bookitparticipanttabdefault', 'student');
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
            'name' => 'Participant tab default cancel',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('+1 day'),
            'endtime' => strtotime('+1 day +2 hours'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_CANCELED, (int)$response['status']);
        $this->assertSame('myevents', $response['tab']);
    }

    /**
     * Explicit invalid tabs still fail validation for workspace viewers.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_invalid_explicit_tab_throws_for_workspace_viewer(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Invalid tab']);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Invalid tab event',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
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
            'usercreated' => 0,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\invalid_parameter_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            'myevents'
        );
    }

    /**
     * Return cleaning retains only status, tab and redirect URL.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_return_clean_keeps_canonical_redirect_contract(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Queue reactivate clean']);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Reject for return clean',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
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
            'usercreated' => 0,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_REJECTED,
            'rejectedcancelled'
        );

        $cleaned = external_api::clean_returnvalue(
            update_event_booking_status::execute_returns(),
            $response
        );

        $this->assertSame(['status', 'tab', 'redirecturl'], array_keys($cleaned));
        $this->assertSame('rejectedcancelled', $cleaned['tab']);
        $this->assertStringContainsString('tab=rejectedcancelled', $cleaned['redirecturl']);
    }

    /**
     * Service-team callers must not silently remap myevents to allrequests.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_service_team_myevents_tab_is_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'No myevents remap',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Open request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
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
            'usercreated' => 0,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\invalid_parameter_exception::class);
        update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            'myevents'
        );
    }
}
