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
 * Unit tests for resource_settings_manager class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use mod_bookit\local\entity\resource\bookit_resource;
use mod_bookit\local\entity\resource\bookit_resource_category;
use mod_bookit\local\entity\resource\bookit_resource_settings;

/**
 * Unit tests for resource_settings_manager class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\manager\resource_settings_manager
 */
final class resource_settings_manager_test extends advanced_testcase {
    /** @var int Test category ID */
    private int $categoryid;

    /** @var int Test resource 1 ID */
    private int $resourceid1;

    /** @var int Test resource 2 ID */
    private int $resourceid2;

    /**
     * Set up test data.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a category.
        $category = new bookit_resource_category(null, 'Test Category', '', 0, true, 0, 0, 2);
        $this->categoryid = resource_manager::save_category($category, 2);

        // Create two resources (names chosen so Apple < Beamer alphabetically).
        $resource1 = new bookit_resource(
            null,
            'Apple MacBook',
            'Laptop',
            $this->categoryid,
            1,
            false,
            0,
            true,
            null,
            0,
            0,
            2
        );
        $this->resourceid1 = resource_manager::save_resource($resource1, 2);

        $resource2 = new bookit_resource(
            null,
            'Beamer',
            'Projector',
            $this->categoryid,
            1,
            false,
            0,
            true,
            null,
            0,
            0,
            2
        );
        $this->resourceid2 = resource_manager::save_resource($resource2, 2);

        // Save_resource auto-creates checklist items; remove them so tests start clean.
        resource_settings_manager::delete_checklist_item_by_resource($this->resourceid1);
        resource_settings_manager::delete_checklist_item_by_resource($this->resourceid2);
    }

    /**
     * Test getting all checklist items when empty.
     */
    public function test_get_all_checklist_items_empty(): void {
        $items = resource_settings_manager::get_all_checklist_items();
        $this->assertEmpty($items);
    }

    /**
     * Test saving and retrieving a checklist item.
     */
    public function test_save_and_get_checklist_item(): void {
        $item = new bookit_resource_settings(
            null,
            $this->resourceid1,
            86400,
            'before_event',
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            2
        );

        $id = resource_settings_manager::save_checklist_item($item, 2);

        $this->assertNotEmpty($id);
        $this->assertIsInt($id);

        $retrieved = resource_settings_manager::get_checklist_item($id);

        $this->assertNotNull($retrieved);
        $this->assertInstanceOf(bookit_resource_settings::class, $retrieved);
        $this->assertEquals($this->resourceid1, $retrieved->get_resourceid());
        $this->assertEquals(86400, $retrieved->get_duedate());
        $this->assertEquals('before_event', $retrieved->get_duedatetype());
    }

    /**
     * Test updating an existing checklist item.
     */
    public function test_update_checklist_item(): void {
        $item = new bookit_resource_settings(
            null,
            $this->resourceid1,
            null,
            null,
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            2
        );
        $id = resource_settings_manager::save_checklist_item($item, 2);

        // Retrieve, modify, re-save.
        $retrieved = resource_settings_manager::get_checklist_item($id);
        $this->assertNotNull($retrieved);

        $updated = new bookit_resource_settings(
            $retrieved->get_id(),
            $this->resourceid1,
            3600,
            'after_event',
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            2
        );

        resource_settings_manager::save_checklist_item($updated, 2);

        $final = resource_settings_manager::get_checklist_item($id);
        $this->assertEquals(3600, $final->get_duedate());
        $this->assertEquals('after_event', $final->get_duedatetype());
    }

    /**
     * Test get_checklist_item_by_resource.
     */
    public function test_get_checklist_item_by_resource(): void {
        // Returns null when not found.
        $item = resource_settings_manager::get_checklist_item_by_resource($this->resourceid1);
        $this->assertNull($item);

        // Create and retrieve.
        $checklist = new bookit_resource_settings(
            null,
            $this->resourceid1,
            null,
            null,
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            2
        );
        resource_settings_manager::save_checklist_item($checklist, 2);

        $item = resource_settings_manager::get_checklist_item_by_resource($this->resourceid1);
        $this->assertNotNull($item);
        $this->assertEquals($this->resourceid1, $item->get_resourceid());
    }

    /**
     * Test deleting a checklist item.
     */
    public function test_delete_checklist_item(): void {
        $item = new bookit_resource_settings(
            null,
            $this->resourceid1,
            null,
            null,
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            0,
            2
        );
        $id = resource_settings_manager::save_checklist_item($item, 2);

        $result = resource_settings_manager::delete_checklist_item($id);

        $this->assertTrue($result);
        $this->assertNull(resource_settings_manager::get_checklist_item($id));
    }

    /**
     * Test deleting a checklist item by resource ID.
     */
    public function test_delete_checklist_item_by_resource(): void {
        $item = new bookit_resource_settings(
            null,
            $this->resourceid1,
            null,
            null,
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            0,
            2
        );
        resource_settings_manager::save_checklist_item($item, 2);

        $result = resource_settings_manager::delete_checklist_item_by_resource($this->resourceid1);

        $this->assertTrue($result);
        $this->assertNull(resource_settings_manager::get_checklist_item_by_resource($this->resourceid1));
    }

    /**
     * Test get_all_checklist_items returns all items.
     */
    public function test_get_all_checklist_items(): void {
        $item1 = new bookit_resource_settings(
            null,
            $this->resourceid1,
            null,
            null,
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            0,
            2
        );
        $item2 = new bookit_resource_settings(
            null,
            $this->resourceid2,
            null,
            null,
            1,
            null,
            null,
            null,
            null,
            0,
            0,
            2
        );
        resource_settings_manager::save_checklist_item($item1, 2);
        resource_settings_manager::save_checklist_item($item2, 2);

        $items = resource_settings_manager::get_all_checklist_items();
        $this->assertCount(2, $items);
    }

    /**
     * Test auto_generate_checklist creates entries for all resources.
     */
    public function test_auto_generate_checklist(): void {
        $count = resource_settings_manager::auto_generate_checklist(2);

        $this->assertEquals(2, $count);

        $items = resource_settings_manager::get_all_checklist_items();
        $this->assertCount(2, $items);
    }

    /**
     * Test auto_generate_checklist skips existing entries.
     */
    public function test_auto_generate_checklist_skips_existing(): void {
        // Pre-create checklist for resource1.
        $item = new bookit_resource_settings(
            null,
            $this->resourceid1,
            null,
            null,
            0,
            null,
            null,
            null,
            null,
            0,
            0,
            0,
            2
        );
        resource_settings_manager::save_checklist_item($item, 2);

        // Should only generate for resource2 (resource1 already has an entry).
        $count = resource_settings_manager::auto_generate_checklist(2);

        $this->assertEquals(1, $count);

        $items = resource_settings_manager::get_all_checklist_items();
        $this->assertCount(2, $items);
    }

    /**
     * Test auto_generate_checklist returns 0 when all have entries.
     */
    public function test_auto_generate_checklist_all_exists(): void {
        resource_settings_manager::auto_generate_checklist(2);

        // Second call returns 0 (all already have entries).
        $count = resource_settings_manager::auto_generate_checklist(2);
        $this->assertEquals(0, $count);
    }

    /**
     * Test create_checklist_for_resource creates a new entry.
     */
    public function test_create_checklist_for_resource(): void {
        $id = resource_settings_manager::create_checklist_for_resource($this->resourceid1, 2);

        $this->assertNotEmpty($id);

        $item = resource_settings_manager::get_checklist_item($id);
        $this->assertNotNull($item);
        $this->assertEquals($this->resourceid1, $item->get_resourceid());
    }

    /**
     * Test create_checklist_for_resource is idempotent.
     */
    public function test_create_checklist_for_resource_idempotent(): void {
        $id1 = resource_settings_manager::create_checklist_for_resource($this->resourceid1, 2);
        $id2 = resource_settings_manager::create_checklist_for_resource($this->resourceid1, 2);

        $this->assertEquals($id1, $id2);

        $items = resource_settings_manager::get_all_checklist_items();
        $this->assertCount(1, $items);
    }

    /**
     * Test create_checklist_for_resource assigns alphabetical sortorder.
     */
    public function test_create_checklist_for_resource_sortorder(): void {
        global $DB;

        // Create Beamer first (should get sortorder 0 initially).
        resource_settings_manager::create_checklist_for_resource($this->resourceid2, 2);

        // Create Apple MacBook second — alphabetically before Beamer, so should shift Beamer.
        resource_settings_manager::create_checklist_for_resource($this->resourceid1, 2);

        $apple = resource_settings_manager::get_checklist_item_by_resource($this->resourceid1);
        $beamer = resource_settings_manager::get_checklist_item_by_resource($this->resourceid2);

        $this->assertNotNull($apple);
        $this->assertNotNull($beamer);

        // Apple (0) should sort before Beamer (1).
        $this->assertLessThan($beamer->get_sortorder(), $apple->get_sortorder());
    }
}
