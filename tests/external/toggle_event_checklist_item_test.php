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
use mod_bookit\local\entity\masterchecklist\bookit_checklist_category;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_item;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_master;
use mod_bookit\local\install_helper;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;

/**
 * Regression tests for checklist toggles during governed-read rollout.
 *
 * @covers \mod_bookit\external\toggle_event_checklist_item
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class toggle_event_checklist_item_test extends advanced_testcase {
    /**
     * Checklist state changes must coexist with governed overview reads.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_toggles_state_without_breaking_governed_reads(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config(install_helper::CONFIG_CHECKLIST_ENABLED, 1, 'mod_bookit');

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Checklist command']);
        $context = context_module::instance($bookit->cmid);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Checklist event',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
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

        $master = new bookit_checklist_master(null, 'Governed checklist', 'Read-rollout regression');
        $masterid = $master->save();
        $category = new bookit_checklist_category(null, $masterid, 'Preparation', 'Preparation tasks');
        $categoryid = $category->save();
        $item = new bookit_checklist_item(
            null,
            $masterid,
            $categoryid,
            null,
            [],
            [],
            'Bring cables',
            'Checklist regression',
            1,
            null,
            0,
            1,
            null,
            null,
            null,
            null,
            null,
            null
        );
        $itemid = $item->save();

        $governedbefore = event_manager::get_governed_calendar_events(
            $context,
            (int)get_admin()->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            []
        );
        $this->assertCount(1, $governedbefore);
        $this->assertSame($eventid, (int)$governedbefore[0]['eventid']);

        $response = toggle_event_checklist_item::execute($bookit->cmid, $eventid, $itemid, true);

        $this->assertTrue($response['done']);

        $state = $DB->get_record('bookit_event_checklist_state', [
            'eventid' => $eventid,
            'checklistitemid' => $itemid,
            'userid' => get_admin()->id,
        ], 'done', MUST_EXIST);
        $this->assertSame(1, (int)$state->done);

        $governedafter = event_manager::get_governed_calendar_events(
            $context,
            (int)get_admin()->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            []
        );
        $this->assertCount(1, $governedafter);
        $this->assertSame($eventid, (int)$governedafter[0]['eventid']);
    }
}
