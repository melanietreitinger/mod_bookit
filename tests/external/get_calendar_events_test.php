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

use externallib_advanced_testcase;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\tests\read_contract_assertions_trait;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once(__DIR__ . '/read_contract_assertions_trait.php');

/**
 * External contract tests for the governed calendar read.
 *
 * @covers \mod_bookit\external\get_calendar_events
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_calendar_events_test extends externallib_advanced_testcase {
    use read_contract_assertions_trait;

    /**
     * The governed calendar read must return the filtered event set consumed by calendar/export clients.
     *
     * @return void
     */
    public function test_execute_returns_filtered_calendar_events(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Calendar read']);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Calendar room',
            'shortname' => 'CR-1',
            'location' => 'North',
        ]);

        $visibleid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Calendar export parity',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
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
        $DB->insert_record('bookit_event', (object)[
            'name' => 'Calendar hidden draft',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 12:00:00'),
            'endtime' => strtotime('2026-05-20 13:00:00'),
            'duration' => 60,
            'roomid' => $roomid,
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

        $response = get_calendar_events::execute(
            $bookit->cmid,
            '2026-05-20T00:00:00',
            '2026-05-20T23:59:59',
            [$roomid],
            [],
            [event_access_manager::BOOKINGSTATUS_ACCEPTED],
            '',
            false
        );

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertCount(1, $response['events']);
        $this->assert_is_canonical_calendar_event($response['events'][0]);
        $this->assertSame($visibleid, (int)$response['events'][0]['id']);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_ACCEPTED, $response['events'][0]['extendedProps']['bookingstatus']);
        $this->assertSame('full', $response['events'][0]['extendedProps']['visibilitymode']);
        $this->assertStringContainsString('Calendar export parity', $response['events'][0]['title']);
    }

    /**
     * Governed calendar reads must keep booking-person events visible after service-team transitions.
     *
     * @return void
     */
    public function test_execute_returns_booking_person_event_after_service_team_status_transition(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Calendar transition']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($bookingperson->id, $course->id);

        \update_capabilities('mod_bookit');
        $participantrole = \create_role('Bookit calendar participant', 'bookitcalendarparticipant', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \role_assign($participantrole, $bookingperson->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Transition room',
            'shortname' => 'TR-1',
            'location' => 'East',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Calendar transition exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
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
            'usermodified' => (int)$bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->setUser($serviceuser);
        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            (int)$serviceuser->id,
            $context
        );

        $this->setUser($bookingperson);
        $response = get_calendar_events::execute(
            $bookit->cmid,
            '2026-05-20T00:00:00',
            '2026-05-20T23:59:59',
            [$roomid],
            [],
            [],
            '',
            false
        );

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertCount(1, $response['events']);
        $this->assertSame($eventid, (int)$response['events'][0]['id']);
        $this->assertStringContainsString('Calendar transition exam', $response['events'][0]['title']);
    }

    /**
     * Governed calendar reads must keep booking-person events visible after foreign form saves.
     *
     * @return void
     */
    public function test_execute_returns_booking_person_event_after_foreign_form_save(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $admin = get_admin();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Calendar foreign save']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($bookingperson->id, $course->id);

        \update_capabilities('mod_bookit');
        $participantrole = \create_role('Bookit calendar foreign participant', 'bookitcalendarforeign', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \role_assign($participantrole, $bookingperson->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Foreign save room',
            'shortname' => 'FS-1',
            'location' => 'South',
        ]);

        $this->setUser($bookingperson);
        $event = new \mod_bookit\local\entity\bookit_event(
            0,
            'Calendar foreign save exam',
            20261,
            1,
            strtotime('2026-05-20 09:00:00'),
            strtotime('2026-05-20 11:00:00'),
            120,
            $roomid,
            12,
            0,
            '',
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            0,
            '',
            null,
            '',
            '',
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
        $updated->notes = 'Admin foreign save';

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
        $response = get_calendar_events::execute(
            $bookit->cmid,
            '2026-05-20T00:00:00',
            '2026-05-20T23:59:59',
            [$roomid],
            [],
            [],
            '',
            false
        );

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertCount(1, $response['events']);
        $this->assertSame((int)$saved->id, (int)$response['events'][0]['id']);
        $this->assertStringContainsString('Calendar foreign save exam', $response['events'][0]['title']);
    }

    /**
     * Governed calendar reads must hide canceled bookings from the service-team active calendar.
     *
     * @return void
     */
    public function test_execute_hides_canceled_event_after_service_team_transition(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Calendar canceled']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $servicerole = \create_role('Bookit calendar service', 'bookitcalservice016', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $servicerole, $context->id, true);
        \role_assign($servicerole, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Canceled room',
            'shortname' => 'CN-1',
            'location' => 'West',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Canceled calendar exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'personinchargeid' => 0,
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

        $this->setUser($serviceuser);
        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            (int)$serviceuser->id,
            $context
        );

        $response = get_calendar_events::execute(
            $bookit->cmid,
            '2026-05-20T00:00:00',
            '2026-05-20T23:59:59',
            [$roomid],
            [],
            [],
            '',
            false
        );

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertCount(0, $response['events']);
    }

    /**
     * Self-canceled bookings must not appear in the service-team active calendar read.
     *
     * @return void
     */
    public function test_execute_hides_self_canceled_event_from_service_team_calendar(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Self cancel calendar']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($bookingperson->id, $course->id);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $participantrole = \create_role('Bookit self-cancel participant', 'bookitselfcancel016', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \role_assign($participantrole, $bookingperson->id, $context->id);

        $servicerole = \create_role('Bookit self-cancel service', 'bookitselfcancelsvc016', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $servicerole, $context->id, true);
        \role_assign($servicerole, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Self-cancel room',
            'shortname' => 'SC-1',
            'location' => 'South',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Self canceled calendar exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-21 09:00:00'),
            'endtime' => strtotime('2026-05-21 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
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
            'usermodified' => (int)$bookingperson->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->setUser($bookingperson);
        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            (int)$bookingperson->id,
            $context
        );

        $this->setUser($serviceuser);
        $response = get_calendar_events::execute(
            $bookit->cmid,
            '2026-05-21T00:00:00',
            '2026-05-21T23:59:59',
            [$roomid],
            [],
            [],
            '',
            false
        );

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertCount(0, $response['events']);
    }

    /**
     * Export preview may include canceled bookings when the service team explicitly filters for them.
     *
     * @return void
     */
    public function test_execute_includes_canceled_event_in_export_mode_with_explicit_status_filter(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Export canceled']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $servicerole = \create_role('Bookit export service', 'bookitexport016', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $servicerole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $servicerole, $context->id, true);
        \role_assign($servicerole, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Export canceled room',
            'shortname' => 'EC-1',
            'location' => 'North',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Export canceled exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-22 09:00:00'),
            'endtime' => strtotime('2026-05-22 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
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
            'usermodified' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->setUser($serviceuser);
        $response = get_calendar_events::execute(
            $bookit->cmid,
            '2026-05-22T00:00:00',
            '2026-05-22T23:59:59',
            [$roomid],
            [],
            [event_access_manager::BOOKINGSTATUS_CANCELED],
            '',
            true
        );

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertCount(1, $response['events']);
        $this->assertSame($eventid, (int)$response['events'][0]['id']);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CANCELED, $response['events'][0]['extendedProps']['bookingstatus']);
    }

    /**
     * Support on site receives unassigned open-status bookings through the governed calendar read.
     *
     * @return void
     */
    public function test_support_sees_unassigned_open_statuses_in_calendar_read(): void {
        global $DB;

        $this->resetAfterTest(true);

        $supportuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Support calendar read']);
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
        \role_assign($supportroleid, $supportuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Support calendar room',
            'shortname' => 'SC-1',
            'location' => 'West',
        ]);

        $newid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Support calendar new',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-21 09:00:00'),
            'endtime' => strtotime('2026-05-21 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
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
            'usermodified' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $inprogressid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Support calendar in progress',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-21 12:00:00'),
            'endtime' => strtotime('2026-05-21 13:00:00'),
            'duration' => 60,
            'roomid' => $roomid,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            'personinchargeid' => 0,
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

        $this->setUser($supportuser);
        $response = get_calendar_events::execute(
            $bookit->cmid,
            '2026-05-21T00:00:00',
            '2026-05-21T23:59:59',
            [$roomid],
            [],
            [],
            '',
            false
        );

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $returnedids = array_map(static fn(array $event): int => (int)$event['id'], $response['events']);
        $this->assertContains($newid, $returnedids);
        $this->assertContains($inprogressid, $returnedids);
    }
}
