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
use context_module;
use mod_bookit\local\entity\resource\bookit_resource_status;
use mod_bookit\local\install_helper;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\event_resource_manager;

/**
 * Regression tests for resource-status updates during governed-read rollout.
 *
 * @covers \mod_bookit\external\update_event_resource_status
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_event_resource_status_test extends advanced_testcase {
    /**
     * Resource-status updates must coexist with governed overview reads.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_updates_resource_status_without_breaking_governed_reads(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config(install_helper::CONFIG_RESOURCES_ENABLED, 1, 'mod_bookit');

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Resource command']);
        $context = context_module::instance($bookit->cmid);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Resource room',
            'shortname' => 'RR-1',
        ]);
        $categoryid = $generator->create_resource_category([
            'name' => 'Equipment',
        ]);
        $resourceid = $generator->create_resource([
            'name' => 'Projector',
            'category_name' => 'Equipment',
            'rooms' => 'Resource room',
            'amount' => 1,
        ]);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Resource workflow event',
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
        $this->assertGreaterThan(0, $categoryid);
        event_resource_manager::add_resource_to_event($eventid, $resourceid, 1, get_admin()->id);

        $governedbefore = event_manager::get_governed_calendar_events(
            $context,
            (int)get_admin()->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            []
        );
        $this->assertCount(1, $governedbefore);
        $this->assertSame($eventid, (int)$governedbefore[0]['id']);

        $response = update_event_resource_status::execute(
            $bookit->cmid,
            $eventid,
            $resourceid,
            bookit_resource_status::CONFIRMED->value
        );

        $this->assertSame(bookit_resource_status::CONFIRMED->value, $response['status']);

        $record = $DB->get_record('bookit_event_resource', [
            'eventid' => $eventid,
            'resourceid' => $resourceid,
        ], 'status', MUST_EXIST);
        $this->assertSame(bookit_resource_status::CONFIRMED->value, $record->status);

        $governedafter = event_manager::get_governed_calendar_events(
            $context,
            (int)get_admin()->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            []
        );
        $this->assertCount(1, $governedafter);
        $this->assertSame($eventid, (int)$governedafter[0]['id']);
    }
}
