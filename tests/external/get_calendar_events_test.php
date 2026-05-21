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
        $this->assertSame('reserved_projection', $response['events'][0]['extendedProps']['visibilitymode']);
        $this->assertSame('Reserved', $response['events'][0]['title']);
    }
}
