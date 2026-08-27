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

namespace mod_bookit\local\manager;

use advanced_testcase;
use context_module;
use mod_bookit\tests\read_contract_assertions_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../external/read_contract_assertions_trait.php');

/**
 * Guardrail checks for canonical governed read payloads.
 *
 * @covers \mod_bookit\local\manager\event_manager
 * @covers \mod_bookit\local\manager\event_access_manager
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class read_parity_test extends advanced_testcase {
    use read_contract_assertions_trait;

    /**
     * Governed reads must stay canonical without reviving forbidden legacy aliases.
     *
     * @return void
     */
    public function test_governed_calendar_read_stays_canonical(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Parity']);
        $context = context_module::instance($bookit->cmid);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Parity room',
            'shortname' => 'PR-1',
        ]);

        $firstid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Parity first',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
            'participantsamount' => 10,

            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
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
        $secondid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Parity second',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 12:00:00'),
            'endtime' => strtotime('2026-05-20 13:00:00'),
            'duration' => 60,
            'roomid' => $roomid,
            'participantsamount' => 10,

            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
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

        $governed = event_manager::get_governed_calendar_events(
            $context,
            (int)get_admin()->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            []
        );

        $this->assertCount(2, $governed);
        $this->assert_is_canonical_calendar_event($governed[0]);
        $this->assert_is_canonical_calendar_event($governed[1]);
        $this->assertSame([$firstid, $secondid], array_map(static fn(array $event): int => (int)$event['id'], $governed));
    }

    /**
     * Support calendar breadth must not leak unassigned open bookings into overview reads.
     *
     * @return void
     */
    public function test_support_calendar_includes_unassigned_open_overview_does_not(): void {
        global $DB;

        $this->resetAfterTest(true);

        $supportuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Support parity']);
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
            'name' => 'Parity support room',
            'shortname' => 'PS-1',
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Parity support new',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
            'participantsamount' => 10,

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
            'usermodified' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->setUser($supportuser);
        $calendar = event_manager::get_governed_calendar_events(
            $context,
            (int)$supportuser->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            []
        );
        $overview = event_manager::get_events_for_examiner((int)$supportuser->id);

        $this->assertSame([$eventid], array_map(static fn(array $item): int => (int)$item['id'], $calendar));
        $this->assertFalse(event_access_manager::can_user_view_event_in_overview($event, $context, (int)$supportuser->id));
        $this->assertSame([], array_values(array_filter(
            $overview,
            static fn(stdClass $item): bool => (int)$item->id === $eventid
        )));
    }

    /**
     * Booking persons see rejected requests in history projection but not in active overview.
     *
     * @return void
     */
    public function test_booking_person_rejected_in_history_not_in_active_overview(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Rejected history parity',
        ]);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $participantrole = \create_role('Bookit rejected participant', 'bookitrejectedparticipant', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \role_assign($participantrole, $bookingperson->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected history exam',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,

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
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->setUser($bookingperson);
        $this->assertTrue(event_access_manager::can_user_view_event_in_history($event, $context, (int)$bookingperson->id));

        $historyevents = event_manager::filter_overview_events(
            event_manager::get_events_for_examiner((int)$bookingperson->id),
            [],
            true
        );
        $activeevents = event_manager::filter_overview_events(
            event_manager::get_events_for_examiner((int)$bookingperson->id),
            [],
            false
        );

        $historyids = array_values(array_map(
            static fn($item): int => (int)$item->id,
            array_filter(
                $historyevents,
                static fn($item): bool => (int)$item->id === $eventid
                    && event_access_manager::can_user_view_event_in_history($item, $context, (int)$bookingperson->id)
            )
        ));
        $this->assertSame([$eventid], $historyids);
        $this->assertSame([], array_values(array_filter(
            $activeevents,
            static fn($item): bool => (int)$item->id === $eventid
        )));
    }
}
