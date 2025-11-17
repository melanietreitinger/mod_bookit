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
 * Unit tests for bookit_checklist_category entity class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity;

use advanced_testcase;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_category;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_master;

/**
 * Unit tests for bookit_checklist_category class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\entity\masterchecklist\bookit_checklist_category
 */
final class bookit_checklist_category_test extends advanced_testcase {
    /**
     * Test the creation of a new bookit_checklist_category instance.
     */
    public function test_create_instance(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $masterid = 1;
        $name = 'Test Category';
        $description = 'This is a test category description';
        $sortorder = 2;

        // Create a new instance.
        $category = new bookit_checklist_category(
            null,
            $masterid,
            $name,
            $description,
            null,
            $sortorder
        );

        // Check the properties were set correctly.
        $this->assertNull($category->id);
        $this->assertEquals($masterid, $category->masterid);
        $this->assertEquals($name, $category->name);
        $this->assertEquals($description, $category->description);
        $this->assertEquals($sortorder, $category->sortorder);
        $this->assertEmpty($category->checklistitems);
    }

    /**
     * Test saving a new bookit_checklist_category to the database.
     */
    public function test_save_new_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $master = new bookit_checklist_master(
            null,
            'Test Master',
            'Test Master Description'
        );
        $masterid = $master->save();

        $name = 'Test Category Save';
        $description = 'This is a test category for saving';
        $sortorder = 1;

        // Create a new instance.
        $category = new bookit_checklist_category(
            null,
            $masterid,
            $name,
            $description,
            null,
            $sortorder
        );

        // Save the instance.
        $id = $category->save();

        // Check the ID is not empty.
        $this->assertNotEmpty($id);

        // Check the record exists in the database.
        $record = $DB->get_record('bookit_checklist_category', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals($masterid, $record->masterid);
        $this->assertEquals($name, $record->name);
        $this->assertEquals($description, $record->description);
        $this->assertEquals($sortorder, $record->sortorder);
    }

    /**
     * Test updating an existing bookit_checklist_category in the database.
     */
    public function test_update_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $master = new bookit_checklist_master(
            null,
            'Test Master',
            'Test Master Description'
        );
        $masterid = $master->save();

        // Create a new category instance.
        $category = new bookit_checklist_category(
            null,
            $masterid,
            'Original Category Name',
            'Original Category Description',
            null,
            0
        );

        // Save the instance.
        $id = $category->save();

        // Change the properties.
        $category->name = 'Updated Category Name';
        $category->description = 'Updated Category Description';
        $category->sortorder = 5;

        // Save the updated instance.
        $category->save();

        // Check the record was updated in the database.
        $record = $DB->get_record('bookit_checklist_category', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('Updated Category Name', $record->name);
        $this->assertEquals('Updated Category Description', $record->description);
        $this->assertEquals(5, $record->sortorder);
    }

    /**
     * Test deleting a bookit_checklist_category from the database.
     */
    public function test_delete_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $master = new bookit_checklist_master(
            null,
            'Test Master',
            'Test Master Description'
        );
        $masterid = $master->save();

        // Create a new category instance.
        $category = new bookit_checklist_category(
            null,
            $masterid,
            'Category to Delete',
            'This category will be deleted',
            null,
            0
        );

        // Save the instance.
        $id = $category->save();

        // Verify it exists in the database.
        $this->assertTrue($DB->record_exists('bookit_checklist_category', ['id' => $id]));

        // Delete the instance.
        $result = $category->delete();

        // Check the deletion was successful.
        $this->assertTrue($result);
        $this->assertFalse($DB->record_exists('bookit_checklist_category', ['id' => $id]));
    }

    /**
     * Test loading a bookit_checklist_category from the database.
     */
    public function test_from_database(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $master = new bookit_checklist_master(
            null,
            'Test Master',
            'Test Master Description'
        );
        $masterid = $master->save();

        // Create test data directly in the database.
        $record = new \stdClass();
        $record->masterid = $masterid;
        $record->name = 'Database Category Test';
        $record->description = 'Created directly in the database';
        $record->checklistitems = '';
        $record->sortorder = 3;
        $record->usermodified = 2;
        $record->timecreated = time();
        $record->timemodified = time();

        $id = $DB->insert_record('bookit_checklist_category', $record);

        // Load the record using the from_database method.
        $category = bookit_checklist_category::from_database($id);

        // Check the properties match.
        $this->assertEquals($id, $category->id);
        $this->assertEquals($masterid, $category->masterid);
        $this->assertEquals('Database Category Test', $category->name);
        $this->assertEquals('Created directly in the database', $category->description);
        $this->assertEquals(3, $category->sortorder);
    }

    /**
     * Test from_record method.
     */
    public function test_from_record(): void {
        $this->resetAfterTest(true);

        $record = new \stdClass();
        $record->id = 10;
        $record->masterid = 5;
        $record->name = 'Record Test';
        $record->description = 'Created from record';
        $record->checklistitems = '';
        $record->sortorder = 7;
        $record->usermodified = 2;
        $record->timecreated = time();
        $record->timemodified = time();

        // Create from record.
        $category = bookit_checklist_category::from_record($record);

        // Check the properties match.
        $this->assertEquals(10, $category->id);
        $this->assertEquals(5, $category->masterid);
        $this->assertEquals('Record Test', $category->name);
        $this->assertEquals('Created from record', $category->description);
        $this->assertEquals(7, $category->sortorder);
    }

    /**
     * Test exporting the checklist category for template rendering.
     */
    public function test_export_for_template(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $renderer = $this->getMockBuilder(\renderer_base::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a category instance.
        $category = new bookit_checklist_category(
            99,
            5,
            'Export Category Test',
            'Description for export test',
            null,
            4
        );

        // Export for template.
        $data = $category->export_for_template($renderer);

        // Check the exported data.
        $this->assertEquals(99, $data->id);
        $this->assertEquals(5, $data->masterid);
        $this->assertEquals('Export Category Test', $data->name);
        $this->assertEquals(4, $data->order);
        $this->assertEquals('category', $data->type);
        $this->assertIsArray($data->checklistitems);
    }
}
