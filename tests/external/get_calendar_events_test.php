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
}
