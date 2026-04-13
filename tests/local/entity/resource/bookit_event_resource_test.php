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
 * Unit tests for bookit_event_resource entity.
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
 * Unit tests for bookit_event_resource entity class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\entity\resource\bookit_event_resource
 */
final class bookit_event_resource_test extends advanced_testcase {
    /**
     * Test status enum values are defined correctly.
     */
    public function test_status_enum_values(): void {
        $this->assertEquals('requested', bookit_resource_status::REQUESTED->value);
        $this->assertEquals('confirmed', bookit_resource_status::CONFIRMED->value);
        $this->assertEquals('inprogress', bookit_resource_status::INPROGRESS->value);
        $this->assertEquals('rejected', bookit_resource_status::REJECTED->value);
    }

    /**
     * Test constructor defaults.
     */
    public function test_constructor_defaults(): void {
        $entity = new bookit_event_resource();

        $this->assertNull($entity->get_id());
        $this->assertEquals(0, $entity->get_eventid());
        $this->assertEquals(0, $entity->get_resourceid());
        $this->assertEquals(1, $entity->get_amount());
        $this->assertEquals(bookit_resource_status::REQUESTED, $entity->get_status());
        $this->assertEquals(0, $entity->get_usermodified());
    }

    /**
     * Test from_record factory.
     */
    public function test_from_record(): void {
        $record = new \stdClass();
        $record->id = 10;
        $record->eventid = 100;
        $record->resourceid = 5;
        $record->amount = 2;
        $record->status = bookit_resource_status::CONFIRMED->value;
        $record->usermodified = 1;
        $record->timecreated = 1000000;
        $record->timemodified = 1000001;

        $entity = bookit_event_resource::from_record($record);

        $this->assertEquals(10, $entity->get_id());
        $this->assertEquals(100, $entity->get_eventid());
        $this->assertEquals(5, $entity->get_resourceid());
        $this->assertEquals(2, $entity->get_amount());
        $this->assertEquals(bookit_resource_status::CONFIRMED, $entity->get_status());
        $this->assertEquals(1, $entity->get_usermodified());
        $this->assertEquals(1000000, $entity->get_timecreated());
        $this->assertEquals(1000001, $entity->get_timemodified());
    }

    /**
     * Test from_record with missing optional fields uses defaults.
     */
    public function test_from_record_defaults_for_missing_fields(): void {
        $record = new \stdClass();
        $record->id = 1;
        $record->eventid = 10;
        $record->resourceid = 2;

        $entity = bookit_event_resource::from_record($record);

        $this->assertEquals(1, $entity->get_amount());
        $this->assertEquals(bookit_resource_status::REQUESTED, $entity->get_status());
        $this->assertEquals(0, $entity->get_usermodified());
    }

    /**
     * Test getters and setters.
     */
    public function test_getters_setters(): void {
        $entity = new bookit_event_resource();

        $entity->set_id(99);
        $entity->set_eventid(200);
        $entity->set_resourceid(10);
        $entity->set_amount(3);
        $entity->set_status(bookit_resource_status::INPROGRESS);
        $entity->set_usermodified(5);

        $this->assertEquals(99, $entity->get_id());
        $this->assertEquals(200, $entity->get_eventid());
        $this->assertEquals(10, $entity->get_resourceid());
        $this->assertEquals(3, $entity->get_amount());
        $this->assertEquals(bookit_resource_status::INPROGRESS, $entity->get_status());
        $this->assertEquals(5, $entity->get_usermodified());
    }
}
