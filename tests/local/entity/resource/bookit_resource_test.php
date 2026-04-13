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
 * Unit tests for bookit_resource entity.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity\resource;

use advanced_testcase;

/**
 * Unit tests for bookit_resource entity class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\entity\resource\bookit_resource
 */
final class bookit_resource_test extends advanced_testcase {
    /**
     * Test constructor defaults.
     */
    public function test_constructor_defaults(): void {
        $resource = new bookit_resource();

        $this->assertNull($resource->get_id());
        $this->assertEquals('', $resource->get_name());
        $this->assertEquals('', $resource->get_description());
        $this->assertEquals(0, $resource->get_categoryid());
        $this->assertEquals(0, $resource->get_amount());
        $this->assertFalse($resource->is_amountirrelevant());
        $this->assertTrue($resource->is_active());
        $this->assertNull($resource->get_roomids());
    }

    /**
     * Test from_record factory with basic fields.
     */
    public function test_from_record_basic(): void {
        $record = new \stdClass();
        $record->id = 42;
        $record->name = 'Projector';
        $record->description = 'A projector device';
        $record->categoryid = 5;
        $record->amount = 3;
        $record->amountirrelevant = 0;
        $record->active = 1;
        $record->roomids = null;
        $record->usermodified = 2;
        $record->timecreated = 1000000;
        $record->timemodified = 1000001;

        $resource = bookit_resource::from_record($record);

        $this->assertEquals(42, $resource->get_id());
        $this->assertEquals('Projector', $resource->get_name());
        $this->assertEquals('A projector device', $resource->get_description());
        $this->assertEquals(5, $resource->get_categoryid());
        $this->assertEquals(3, $resource->get_amount());
        $this->assertFalse($resource->is_amountirrelevant());
        $this->assertTrue($resource->is_active());
        $this->assertNull($resource->get_roomids());
    }

    /**
     * Test from_record with JSON-encoded roomids.
     */
    public function test_from_record_with_roomids(): void {
        $record = new \stdClass();
        $record->id = 1;
        $record->name = 'Whiteboard';
        $record->description = '';
        $record->categoryid = 1;
        $record->amount = 1;
        $record->amountirrelevant = 1;
        $record->active = 1;
        $record->roomids = json_encode([10, 20, 30]);
        $record->usermodified = 2;
        $record->timecreated = 0;
        $record->timemodified = 0;

        $resource = bookit_resource::from_record($record);

        $this->assertTrue($resource->is_amountirrelevant());
        $roomids = $resource->get_roomids();
        $this->assertIsArray($roomids);
        $this->assertEquals([10, 20, 30], $roomids);
    }

    /**
     * Test from_record with inactive resource.
     */
    public function test_from_record_inactive(): void {
        $record = new \stdClass();
        $record->id = 2;
        $record->name = 'Old Resource';
        $record->description = '';
        $record->categoryid = 1;
        $record->amount = 0;
        $record->amountirrelevant = 0;
        $record->active = 0;
        $record->roomids = null;
        $record->usermodified = 2;
        $record->timecreated = 0;
        $record->timemodified = 0;

        $resource = bookit_resource::from_record($record);

        $this->assertFalse($resource->is_active());
    }

    /**
     * Test getters and setters.
     */
    public function test_getters_setters(): void {
        $resource = new bookit_resource();

        $resource->set_name('Test Resource');
        $resource->set_description('A test description');
        $resource->set_categoryid(7);
        $resource->set_amount(5);
        $resource->set_amountirrelevant(true);
        $resource->set_active(false);
        $resource->set_roomids([1, 2, 3]);

        $this->assertEquals('Test Resource', $resource->get_name());
        $this->assertEquals('A test description', $resource->get_description());
        $this->assertEquals(7, $resource->get_categoryid());
        $this->assertEquals(5, $resource->get_amount());
        $this->assertTrue($resource->is_amountirrelevant());
        $this->assertFalse($resource->is_active());
        $this->assertEquals([1, 2, 3], $resource->get_roomids());
    }
}
