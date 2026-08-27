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
 * Unit tests for get_possible_starttimes past-date helpers.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

use advanced_testcase;
use context_module;
use mod_bookit\local\install_helper;
use mod_bookit\local\manager\event_access_manager;

/**
 * Unit tests for get_possible_starttimes.
 *
 * @covers \mod_bookit\external\get_possible_starttimes
 */
final class get_possible_starttimes_test extends advanced_testcase {
    /**
     * Past timestamps must be rejected while present and future ones stay valid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_is_starttime_in_past_uses_reference_time(): void {
        $referencetime = 1715000000;

        $this->assertTrue(get_possible_starttimes::is_starttime_in_past($referencetime - 1, $referencetime));
        $this->assertFalse(get_possible_starttimes::is_starttime_in_past($referencetime, $referencetime));
        $this->assertFalse(get_possible_starttimes::is_starttime_in_past($referencetime + 3600, $referencetime));
    }

    /**
     * Past-day slots must stay hidden for requesters but remain available to service-team flows.
     *
     * @runInSeparateProcess
     * @return void
     * @throws \dml_exception
     */
    public function test_list_possible_starttimes_respects_allowpast_flag(): void {
        global $DB;

        $this->resetAfterTest(true);
        install_helper::ensure_fresh_install_baseline();
        install_helper::ensure_optional_part_defaults(false);
        install_helper::ensure_booking_status_notification_defaults();

        $roomid = (int)$DB->get_field_sql(
            'SELECT id
               FROM {bookit_room}
              WHERE ' . $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name'),
            ['name' => install_helper::DEFAULT_ROOM_NAME],
            MUST_EXIST
        );
        $DB->set_field('bookit_weekplan_room', 'starttime', 0, ['roomid' => $roomid]);
        $date = (new \DateTimeImmutable('last friday'))->setTime(0, 0);

        [$blockedslots, $blockedstatus] = get_possible_starttimes::list_possible_starttimes(
            \DateTime::createFromImmutable($date),
            60,
            $roomid,
            null,
            false
        );
        [$allowedslots, $allowedstatus] = get_possible_starttimes::list_possible_starttimes(
            \DateTime::createFromImmutable($date),
            60,
            $roomid,
            null,
            true
        );

        $this->assertSame([], $blockedslots);
        $this->assertSame(0, $blockedstatus);
        $this->assertNotEmpty($allowedslots);
        $this->assertNull($allowedstatus);
    }

    /**
     * Governed room reads must coexist with legacy starttime lookups during rollout.
     *
     * @runInSeparateProcess
     * @return void
     * @throws \dml_exception
     */
    public function test_execute_coexists_with_governed_room_availability_reads(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        install_helper::ensure_fresh_install_baseline();
        install_helper::ensure_optional_part_defaults(false);
        install_helper::ensure_booking_status_notification_defaults();

        $roomid = (int)$DB->get_field_sql(
            'SELECT id
               FROM {bookit_room}
              WHERE ' . $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name'),
            ['name' => install_helper::DEFAULT_ROOM_NAME],
            MUST_EXIST
        );
        $DB->set_field('bookit_weekplan_room', 'starttime', 0, ['roomid' => $roomid]);

        $date = new \DateTimeImmutable('next monday');
        $daystart = $date->setTime(0, 0);
        $dayend = $date->setTime(23, 59, 59);

        $roomresponse = get_room_availability::execute(
            $roomid,
            $daystart->format('Y-m-d\TH:i:s'),
            $dayend->format('Y-m-d\TH:i:s')
        );

        [$slots, $status] = get_possible_starttimes::list_possible_starttimes(
            \DateTime::createFromImmutable($date->setTime(0, 0)),
            60,
            $roomid,
            null,
            true
        );

        $this->assertSame('ok', $roomresponse['status']);
        $this->assertFalse($roomresponse['denied']);
        $this->assertNotEmpty($roomresponse['entries']);
        $this->assertNotEmpty($slots);
        $this->assertNull($status);
    }

    /**
     * Service-team edit keeps the current event starttime when no weekplan applies.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_injects_current_starttime_for_service_team_edit_without_weekplan(): void {
        global $DB;

        $this->resetAfterTest(true);
        ['bookit' => $bookit, 'serviceuser' => $serviceuser, 'baseline' => $baseline, 'generator' => $generator] =
            $this->create_execute_context_for_service_team();

        $DB->delete_records('bookit_weekplan_room', ['roomid' => $baseline['roomid']]);

        $starttime = strtotime('tomorrow 09:00');
        $generator->create_event([
            'name' => 'PHPUnit service exam',
            'startdate' => date('Y-m-d H:i:s', $starttime),
            'enddate' => date('Y-m-d H:i:s', $starttime + 7200),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'institution' => $baseline['institutionid'],
            'roomid' => $baseline['roomid'],
        ]);
        $eventid = (int)$DB->get_field_sql(
            'SELECT id
               FROM {bookit_event}
              WHERE ' . $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name'),
            ['name' => 'PHPUnit service exam'],
            MUST_EXIST
        );

        $this->setUser($serviceuser);
        $response = get_possible_starttimes::execute(
            $bookit->cmid,
            (int)date('Y', $starttime),
            (int)date('n', $starttime),
            (int)date('j', $starttime),
            90,
            $baseline['roomid'],
            $eventid
        );

        $this->assertNull($response['status']);
        $this->assertCount(1, $response['slots']);
        $this->assertSame($starttime, $response['slots'][0]['timestamp']);
    }

    /**
     * Participants must not get a synthetic starttime when slots are unavailable.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_does_not_inject_current_starttime_for_participant_without_allowpast(): void {
        global $DB;

        $this->resetAfterTest(true);
        ['bookit' => $bookit, 'participant' => $participant, 'baseline' => $baseline, 'generator' => $generator] =
            $this->create_execute_context_for_participant();

        $DB->delete_records('bookit_weekplan_room', ['roomid' => $baseline['roomid']]);

        $starttime = strtotime('tomorrow 09:00');
        $generator->create_event([
            'name' => 'PHPUnit participant exam',
            'startdate' => date('Y-m-d H:i:s', $starttime),
            'enddate' => date('Y-m-d H:i:s', $starttime + 7200),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'institution' => $baseline['institutionid'],
            'roomid' => $baseline['roomid'],
        ]);
        $eventid = (int)$DB->get_field_sql(
            'SELECT id
               FROM {bookit_event}
              WHERE ' . $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name'),
            ['name' => 'PHPUnit participant exam'],
            MUST_EXIST
        );

        $this->setUser($participant);
        $response = get_possible_starttimes::execute(
            $bookit->cmid,
            (int)date('Y', $starttime),
            (int)date('n', $starttime),
            (int)date('j', $starttime),
            90,
            $baseline['roomid'],
            $eventid
        );

        $this->assertSame(0, $response['status']);
        $this->assertSame([], $response['slots']);
    }

    /**
     * Build a course, service-team user, and fresh-install baseline for execute() tests.
     *
     * @return array{bookit:\stdClass,context:context_module,serviceuser:\stdClass,baseline:array,generator:\mod_bookit_generator}
     */
    private function create_execute_context_for_service_team(): array {
        $course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'visible' => 1]);
        $context = context_module::instance($bookit->cmid);

        $servicerole = \create_role('Bookit service team execute', 'bookitserviceexec', '');
        foreach (['mod/bookit:addevent', 'mod/bookit:editevent', 'mod/bookit:managebasics', 'mod/bookit:view'] as $capability) {
            \assign_capability($capability, CAP_ALLOW, $servicerole, $context->id, true);
        }

        $serviceuser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);
        \role_assign($servicerole, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $baseline = $generator->create_fresh_install_baseline();

        return [
            'bookit' => $bookit,
            'context' => $context,
            'serviceuser' => $serviceuser,
            'baseline' => $baseline,
            'generator' => $generator,
        ];
    }

    /**
     * Build a course, participant user, and fresh-install baseline for execute() tests.
     *
     * @return array{bookit:\stdClass,context:context_module,participant:\stdClass,baseline:array,generator:\mod_bookit_generator}
     */
    private function create_execute_context_for_participant(): array {
        $course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'visible' => 1]);
        $context = context_module::instance($bookit->cmid);

        $participantrole = \create_role('Bookit participant execute', 'bookitparticipantexec', '');
        \assign_capability('mod/bookit:addevent', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);

        $participant = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($participant->id, $course->id);
        \role_assign($participantrole, $participant->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $baseline = $generator->create_fresh_install_baseline();

        return [
            'bookit' => $bookit,
            'context' => $context,
            'participant' => $participant,
            'baseline' => $baseline,
            'generator' => $generator,
        ];
    }
}
