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
 * Unit tests for bookit_checklist_item entity class.
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
 * Unit tests for bookit_checklist_item class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\entity\bookit_checklist_item
 */
final class bookit_checklist_item_test extends advanced_testcase {
    /**
     * Test the creation of a new bookit_checklist_item instance.
     */
    public function test_create_instance(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $masterid = 1;
        $categoryid = 2;
        $parentid = null;
        $roomids = [3, 4];
        $roleid = 5;
        $title = 'Test Item';
        $description = 'This is a test item description';
        $itemtype = 1;
        $options = null;
        $sortorder = 3;
        $isrequired = 1;
        $defaultvalue = null;
        $duedaysoffset = 7;
        $duedaysrelation = 'after';

        // Create a new instance.
        $item = new bookit_checklist_item(
            null,
            $masterid,
            $categoryid,
            $parentid,
            $roomids,
            $roleid,
            $title,
            $description,
            $itemtype,
            $options,
            $sortorder,
            $isrequired,
            $defaultvalue,
            $duedaysoffset,
            $duedaysrelation,
            null,
            null,
            null
        );

        // Check the properties were set correctly.
        $this->assertNull($item->id);
        $this->assertEquals($masterid, $item->masterid);
        $this->assertEquals($categoryid, $item->categoryid);
        $this->assertEquals($parentid, $item->parentid);
        $this->assertEquals($roomids, $item->roomids);
        $this->assertEquals($roleid, $item->roleid);
        $this->assertEquals($title, $item->title);
        $this->assertEquals($description, $item->description);
        $this->assertEquals($itemtype, $item->itemtype);
        $this->assertEquals($options, $item->options);
        $this->assertEquals($sortorder, $item->sortorder);
        $this->assertEquals($isrequired, $item->isrequired);
        $this->assertEquals($defaultvalue, $item->defaultvalue);
        $this->assertEquals($duedaysoffset, $item->duedaysoffset);
        $this->assertEquals($duedaysrelation, $item->duedaysrelation);
    }

    /**
     * Test saving a new bookit_checklist_item to the database.
     */
    public function test_save_new_instance(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // First create a master checklist to be the parent
        $master = new bookit_checklist_master(
            null,
            'Test Master',
            'Test Master Description'
        );
        $masterid = $master->save();

        // Create a category for this item
        $category = new bookit_checklist_category(
            null,
            $masterid,
            'Test Category',
            'Test Category Description'
        );
        $categoryid = $category->save();

        $roomids = [1, 2]; // Room IDs
        $roleid = 3; // Role ID
        $title = 'Test Item Save';
        $description = 'This is a test item for saving';
        $itemtype = 1;
        $sortorder = 2;
        $isrequired = 1;

        // Create a new instance.
        $item = new bookit_checklist_item(
            null,
            $masterid,
            $categoryid,
            null,
            $roomids,
            $roleid,
            $title,
            $description,
            $itemtype,
            null,
            $sortorder,
            $isrequired,
            null,
            null,
            null,
            null,
            null,
            null
        );

        // Save the instance.
        $id = $item->save();

        // Check the ID is not empty.
        $this->assertNotEmpty($id);

        // Check the record exists in the database.
        $record = $DB->get_record('bookit_checklist_item', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals($masterid, $record->masterid);
        $this->assertEquals($categoryid, $record->categoryid);
        $this->assertEquals($title, $record->title);
        $this->assertEquals($description, $record->description);
        $this->assertEquals($itemtype, $record->itemtype);
        $this->assertEquals($sortorder, $record->sortorder);
        $this->assertEquals($isrequired, $record->isrequired);
        $this->assertEquals(json_encode($roomids), $record->roomids);
        $this->assertEquals($roleid, $record->roleid);
    }

    /**
     * Test updating an existing bookit_checklist_item in the database.
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

        $category = new bookit_checklist_category(
            null,
            $masterid,
            'Test Category',
            'Test Category Description'
        );
        $categoryid = $category->save();

        // Create a new item instance.
        $item = new bookit_checklist_item(
            null,
            $masterid,
            $categoryid,
            null,
            [1],
            2,
            'Original Item Name',
            'Original Item Description',
            1,
            null,
            0,
            0,
            null,
            null,
            null,
            null,
            null,
            null
        );

        // Save the instance.
        $id = $item->save();

        // Change the properties.
        $item->title = 'Updated Item Name';
        $item->description = 'Updated Item Description';
        $item->sortorder = 5;
        $item->isrequired = 1;
        $item->roomids = [1, 2, 3];
        $item->roleid = 3;

        // Save the updated instance.
        $item->save();

        // Check the record was updated in the database.
        $record = $DB->get_record('bookit_checklist_item', ['id' => $id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('Updated Item Name', $record->title);
        $this->assertEquals('Updated Item Description', $record->description);
        $this->assertEquals(5, $record->sortorder);
        $this->assertEquals(1, $record->isrequired);
        $this->assertEquals(json_encode([1, 2, 3]), $record->roomids);
        $this->assertEquals(3, $record->roleid);
    }

    /**
     * Test deleting a bookit_checklist_item from the database.
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

        $category = new bookit_checklist_category(
            null,
            $masterid,
            'Test Category',
            'Test Category Description'
        );
        $categoryid = $category->save();

        // Create a new item instance.
        $item = new bookit_checklist_item(
            null,
            $masterid,
            $categoryid,
            null,
            [1],
            2,
            'Item to Delete',
            'This item will be deleted',
            1,
            null,
            0,
            0,
            null,
            null,
            null,
            null,
            null,
            null
        );

        // Save the instance.
        $id = $item->save();

        // Verify it exists in the database.
        $this->assertTrue($DB->record_exists('bookit_checklist_item', ['id' => $id]));

        // Delete the instance.
        $result = $item->delete();

        // Check the deletion was successful.
        $this->assertTrue($result);
        $this->assertFalse($DB->record_exists('bookit_checklist_item', ['id' => $id]));
    }

    /**
     * Test loading a bookit_checklist_item from the database.
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

        $category = new bookit_checklist_category(
            null,
            $masterid,
            'Test Category',
            'Test Category Description'
        );
        $categoryid = $category->save();

        // Create test data directly in the database.
        $record = new \stdClass();
        $record->masterid = $masterid;
        $record->categoryid = $categoryid;
        $record->parentid = null;
        $record->roomids = json_encode([3, 4]);
        $record->roleid = 5;
        $record->title = 'Database Item Test';
        $record->description = 'Created directly in the database';
        $record->itemtype = 1;
        $record->options = null;
        $record->sortorder = 3;
        $record->isrequired = 1;
        $record->defaultvalue = null;
        $record->duedaysoffset = null;
        $record->duedaysrelation = null;
        $record->usermodified = 2; // Admin user ID.
        $record->timecreated = time();
        $record->timemodified = time();

        $id = $DB->insert_record('bookit_checklist_item', $record);

        // Load the record using the from_database method.
        $item = bookit_checklist_item::from_database($id);

        // Check the properties match.
        $this->assertEquals($id, $item->id);
        $this->assertEquals($masterid, $item->masterid);
        $this->assertEquals($categoryid, $item->categoryid);
        $this->assertEquals('Database Item Test', $item->title);
        $this->assertEquals('Created directly in the database', $item->description);
        $this->assertEquals(3, $item->sortorder);
        $this->assertEquals(1, $item->isrequired);
        $this->assertEquals([3, 4], $item->roomids);
        $this->assertEquals(5, $item->roleid);
    }

    /**
     * Test from_record method.
     */
    public function test_from_record(): void {
        $this->resetAfterTest(true);

        $record = new \stdClass();
        $record->id = 10;
        $record->masterid = 5;
        $record->categoryid = 6;
        $record->parentid = null;
        $record->roomids = json_encode([7, 8]);
        $record->roleid = 9;
        $record->title = 'Record Test Item';
        $record->description = 'Created from record';
        $record->itemtype = 1;
        $record->options = null;
        $record->sortorder = 4;
        $record->isrequired = 1;
        $record->defaultvalue = null;
        $record->duedaysoffset = 5;
        $record->duedaysrelation = 'before';
        $record->usermodified = 2;
        $record->timecreated = time();
        $record->timemodified = time();

        // Create from record.
        $item = bookit_checklist_item::from_record($record);

        // Check the properties match.
        $this->assertEquals(10, $item->id);
        $this->assertEquals(5, $item->masterid);
        $this->assertEquals(6, $item->categoryid);
        $this->assertEquals([7, 8], $item->roomids);
        $this->assertEquals(9, $item->roleid);
        $this->assertEquals('Record Test Item', $item->title);
        $this->assertEquals('Created from record', $item->description);
        $this->assertEquals(4, $item->sortorder);
        $this->assertEquals(1, $item->isrequired);
        $this->assertEquals(5, $item->duedaysoffset);
        $this->assertEquals('before', $item->duedaysrelation);
    }

    /**
     * Test exporting the checklist item for template rendering.
     */
    public function test_export_for_template(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $renderer = $this->getMockBuilder(\renderer_base::class)
            ->disableOriginalConstructor()
            ->getMock();

        $item = new bookit_checklist_item(
            99,
            5,
            6,
            null,
            [7, 8],
            9,
            'Export Item Test',
            'Description for export test',
            1,
            null,
            4,
            1,
            null,
            5,
            'before',
            10,
            time(),
            time()
        );

        $data = $item->export_for_template($renderer);

        $this->assertEquals(99, $data->id);
        $this->assertEquals('Export Item Test', $data->title);
        $this->assertEquals(4, $data->order);
        $this->assertEquals(6, $data->categoryid);
        $this->assertEquals(json_encode([7, 8]), $data->roomids);
        $this->assertEquals(9, $data->roleid);
        $this->assertEquals('item', $data->type);
    }
}
