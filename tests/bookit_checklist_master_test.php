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
 * Unit tests for bookit_checklist_master entity class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity;

use advanced_testcase;

/**
 * Unit tests for bookit_checklist_master class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\entity\bookit_checklist_master
 */
class bookit_checklist_master_test extends advanced_testcase {

    /**
     * Test the creation of a new bookit_checklist_master instance.
     */
    public function test_create_instance() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $name = 'Test Checklist';
        $description = 'This is a test description';
        $isdefault = 1;

        // Create a new instance.
        $checklist = new bookit_checklist_master(
            null,
            $name,
            $description,
            $isdefault
        );

        // Check the properties were set correctly.
        $this->assertNull($checklist->id);
        $this->assertEquals($name, $checklist->name);
        $this->assertEquals($description, $checklist->description);
        $this->assertEquals($isdefault, $checklist->isdefault);
    }

    /**
     * Test saving a new bookit_checklist_master to the database.
     */
    public function test_save_new_instance() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $name = 'Test Checklist Save';
        $description = 'This is a test description for saving';
        $isdefault = 0;

        // Create a new instance.
        $checklist = new bookit_checklist_master(
            null,
            $name,
            $description,
            $isdefault
        );

        // Save the instance.
        $id = $checklist->save();

        // Check the ID is not empty.
        $this->assertNotEmpty($id);

        // Check the record exists in the database.
        $record = $DB->get_record('bookit_checklist_master', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals($name, $record->name);
        $this->assertEquals($description, $record->description);
        $this->assertEquals($isdefault, $record->isdefault);
    }

    /**
     * Test updating an existing bookit_checklist_master in the database.
     */
    public function test_update_instance() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a new instance.
        $checklist = new bookit_checklist_master(
            null,
            'Original Name',
            'Original Description',
            0
        );

        // Save the instance.
        $id = $checklist->save();

        // Change the properties.
        $checklist->name = 'Updated Name';
        $checklist->description = 'Updated Description';
        $checklist->isdefault = 1;

        // Save the updated instance.
        $checklist->save();

        // Check the record was updated in the database.
        $record = $DB->get_record('bookit_checklist_master', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('Updated Name', $record->name);
        $this->assertEquals('Updated Description', $record->description);
        $this->assertEquals(1, $record->isdefault);
    }

    /**
     * Test loading a bookit_checklist_master from the database.
     */
    public function test_from_database() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create test data directly in the database.
        $record = new \stdClass();
        $record->name = 'Database Test';
        $record->description = 'Created directly in the database';
        $record->isdefault = 1;
        $record->checklistcategories = '';
        $record->usermodified = 2; // Admin user ID.
        $record->timecreated = time();
        $record->timemodified = time();

        $id = $DB->insert_record('bookit_checklist_master', $record);

        // Load the record using the from_database method.
        $checklist = bookit_checklist_master::from_database($id);

        // Check the properties match.
        $this->assertEquals($id, $checklist->id);
        $this->assertEquals('Database Test', $checklist->name);
        $this->assertEquals('Created directly in the database', $checklist->description);
        $this->assertEquals(1, $checklist->isdefault);
    }

    /**
     * Test exporting the checklist master for template rendering.
     */
    public function test_export_for_template() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $renderer = $this->getMockBuilder(\renderer_base::class)
            ->disableOriginalConstructor()
            ->getMock();

        $checklist = new bookit_checklist_master(
            1,
            'Export Test',
            'Description for export test',
            0,
            []
        );

        $data = $checklist->export_for_template($renderer);

        $this->assertEquals(1, $data->id);
        $this->assertEquals('Export Test', $data->name);
        $this->assertIsArray($data->tableheaders);
        $this->assertCount(count(bookit_checklist_master::DISPLAY_TABLE_COLUMNS), $data->tableheaders);
    }
}
