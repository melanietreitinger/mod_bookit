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
 * Tests for event_manager::get_event resource form defaults (Spec 053).
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use context_module;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\install_helper;
use mod_bookit\local\manager\event_access_manager;

/**
 * Resource checkbox defaults when loading events for the edit form.
 *
 * @covers \mod_bookit\local\manager\event_manager::get_event
 */
final class event_manager_get_event_resource_defaults_test extends advanced_testcase {
    /** @var int */
    private int $roomid;

    /** @var int */
    private int $resourceid;

    /** @var int */
    private int $resourceid2;

    /**
     * Set up resources-enabled fixtures with a first category (often id 1 in tests).
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config(install_helper::CONFIG_RESOURCES_ENABLED, 1, 'mod_bookit');

        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $this->roomid = $generator->create_room([
            'name' => 'Defaults room',
            'shortname' => 'DEF001',
        ]);
        $generator->create_resource_category(['name' => 'PW Screenshot Cat']);
        $this->resourceid = $generator->create_resource([
            'name' => 'PW Screenshot Resource',
            'category_name' => 'PW Screenshot Cat',
            'amount' => 5,
            'amountirrelevant' => true,
        ]);
        $generator->create_resource_category(['name' => 'Equipment']);
        $this->resourceid2 = $generator->create_resource([
            'name' => 'Second resource',
            'category_name' => 'Equipment',
            'amount' => 3,
            'amountirrelevant' => true,
        ]);
    }

    /**
     * Create a persisted event with resource assignments.
     *
     * @param array $resourceids
     * @param int|null $roomid
     * @return int Event id
     */
    private function create_event_with_resources(array $resourceids, ?int $roomid = null): int {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $editor = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($editor->id, $course->id);
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Get event defaults test',
        ]);
        $context = context_module::instance($bookit->cmid);

        $examiner = $this->getDataGenerator()->create_user();
        $starttime = strtotime('+14 days 09:00:00');
        $endtime = strtotime('+14 days 11:00:00');
        $mappings = [];
        foreach ($resourceids as $rid) {
            $mappings[] = (object) ['resourceid' => (int) $rid, 'amount' => 2];
        }

        $this->setUser($editor);
        $event = new bookit_event(
            0,
            'Defaults mapping event',
            20261,
            1,
            $starttime,
            $endtime,
            120,
            $roomid ?? $this->roomid,
            10,
            0,
            '',
            event_access_manager::BOOKINGSTATUS_NEW,
            (int) $examiner->id,
            '',
            0,
            'Notes',
            '',
            '',
            0,
            0,
            null,
            (int) $editor->id,
            time(),
            time(),
            $mappings,
        );

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, (int) $editor->id, $context);
        $this->assertNotEmpty($DB->count_records('bookit_event_resource', ['eventid' => $saved->id]));

        return (int) $saved->id;
    }

    /**
     * Resource in first category maps to checkbox, not room field.
     *
     * @return void
     */
    public function test_get_event_maps_resource_in_category_id_one_to_checkbox(): void {
        global $DB;

        $eventid = $this->create_event_with_resources([$this->resourceid]);
        $categoryid = (int) $DB->get_field('bookit_resource', 'categoryid', ['id' => $this->resourceid]);
        $this->assertGreaterThan(0, $categoryid, 'Fixture resource must have a category');

        $loaded = event_manager::get_event($eventid);
        $checkboxfield = 'checkbox_' . $this->resourceid;
        $amountfield = 'resource_' . $this->resourceid;

        $this->assertObjectHasProperty($checkboxfield, $loaded);
        $this->assertEquals(1, $loaded->$checkboxfield);
        $this->assertObjectHasProperty($amountfield, $loaded);
        $this->assertEquals(2, $loaded->$amountfield);
        $this->assertObjectNotHasProperty('room', $loaded);
    }

    /**
     * roomid stays on the event record; resources do not override it.
     *
     * @return void
     */
    public function test_get_event_does_not_set_room_from_resource_assignment(): void {
        $eventid = $this->create_event_with_resources([$this->resourceid], $this->roomid);
        $loaded = event_manager::get_event($eventid);

        $this->assertEquals($this->roomid, (int) $loaded->roomid);
        $this->assertObjectNotHasProperty('room', $loaded);
    }

    /**
     * Multiple assignments each get checkbox defaults.
     *
     * @return void
     */
    public function test_get_event_maps_multiple_resources_to_checkboxes(): void {
        $eventid = $this->create_event_with_resources([$this->resourceid, $this->resourceid2]);
        $loaded = event_manager::get_event($eventid);

        foreach ([$this->resourceid, $this->resourceid2] as $rid) {
            $checkboxfield = 'checkbox_' . $rid;
            $this->assertObjectHasProperty($checkboxfield, $loaded);
            $this->assertEquals(1, $loaded->$checkboxfield);
        }
    }
}
