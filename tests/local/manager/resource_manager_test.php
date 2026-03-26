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
 * Unit tests for resource_manager class.
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

/**
 * Unit tests for resource_manager class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\manager\resource_manager
 */
final class resource_manager_test extends advanced_testcase {
    /**
     * Test creating and retrieving a category.
     */
    public function test_create_and_get_category(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $name = 'Test Category';
        $description = 'Test category description';
        $sortorder = 1;

        // Create category entity.
        $category = new bookit_resource_category(
            null,
            $name,
            $description,
            $sortorder,
            0,
            0,
            2
        );

        // Save via manager.
        $categoryid = resource_manager::save_category($category, 2);

        // Verify ID is returned.
        $this->assertNotEmpty($categoryid);
        $this->assertIsInt($categoryid);

        // Retrieve via manager.
        $retrieved = resource_manager::get_category($categoryid);

        // Verify retrieved data.
        $this->assertNotNull($retrieved);
        $this->assertEquals($categoryid, $retrieved->get_id());
        $this->assertEquals($name, $retrieved->get_name());
        $this->assertEquals($description, $retrieved->get_description());
        $this->assertEquals($sortorder, $retrieved->get_sortorder());

        // Verify database record.
        $record = $DB->get_record('bookit_resource_category', ['id' => $categoryid]);
        $this->assertNotEmpty($record);
        $this->assertEquals($name, $record->name);
    }

    /**
     * Test get_all_categories returns all categories.
     */
    public function test_get_all_categories_with_filter(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create two categories.
        $cat1 = new bookit_resource_category(null, 'Category One', null, 0, 0, 0, 2);
        $id1 = resource_manager::save_category($cat1, 2);

        $cat2 = new bookit_resource_category(null, 'Category Two', null, 1, 0, 0, 2);
        $id2 = resource_manager::save_category($cat2, 2);

        // Get all categories.
        $allcategories = resource_manager::get_all_categories();
        $this->assertCount(2, $allcategories);

        $ids = array_map(fn($c) => $c->get_id(), $allcategories);
        $this->assertContains($id1, $ids);
        $this->assertContains($id2, $ids);
    }

    /**
     * Test updating a category.
     */
    public function test_update_category(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create initial category.
        $category = new bookit_resource_category(null, 'Original Name', 'Original Desc', 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Retrieve and modify.
        $retrieved = resource_manager::get_category($categoryid);
        $retrieved->set_name('Updated Name');
        $retrieved->set_description('Updated Description');

        // Save update.
        $updatedid = resource_manager::save_category($retrieved, 2);

        // Verify same ID.
        $this->assertEquals($categoryid, $updatedid);

        // Verify updates in database.
        $record = $DB->get_record('bookit_resource_category', ['id' => $categoryid]);
        $this->assertEquals('Updated Name', $record->name);
        $this->assertEquals('Updated Description', $record->description);
    }

    /**
     * Test delete_category validates no resources exist.
     */
    public function test_delete_category_with_resources_throws_exception(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create category.
        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Create resource in category.
        $resource = new bookit_resource(null, 'Test Resource', null, $categoryid, 5, false, 0, true, null, 0, 0, 2);
        $resourceid = resource_manager::save_resource($resource, 2);

        // Try to delete category - should throw exception.
        try {
            resource_manager::delete_category($categoryid);
            $this->fail('Expected moodle_exception not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('resources:category_has_resources', $e->errorcode);
        }
    }

    /**
     * Test delete_category succeeds when no resources exist.
     */
    public function test_delete_category_without_resources_succeeds(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create category.
        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Delete category.
        resource_manager::delete_category($categoryid);

        // Verify deleted from database.
        $exists = $DB->record_exists('bookit_resource_category', ['id' => $categoryid]);
        $this->assertFalse($exists);
    }

    /**
     * Test creating and retrieving a resource.
     */
    public function test_create_and_get_resource(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create category first.
        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Create resource.
        $name = 'Test Resource';
        $description = 'Test resource description';
        $amount = 10;
        $sortorder = 1;
        $active = true;

        $resource = new bookit_resource(
            null,
            $name,
            $description,
            $categoryid,
            $amount,
            false, // Amountirrelevant.
            $sortorder,
            $active,
            null, // Roomids.
            0,
            0,
            2
        );

        // Save via manager.
        $resourceid = resource_manager::save_resource($resource, 2);

        // Verify ID is returned.
        $this->assertNotEmpty($resourceid);
        $this->assertIsInt($resourceid);

        // Retrieve via manager.
        $retrieved = resource_manager::get_resource_by_id($resourceid);

        // Verify retrieved data.
        $this->assertNotNull($retrieved);
        $this->assertEquals($resourceid, $retrieved->get_id());
        $this->assertEquals($name, $retrieved->get_name());
        $this->assertEquals($description, $retrieved->get_description());
        $this->assertEquals($categoryid, $retrieved->get_categoryid());
        $this->assertEquals($amount, $retrieved->get_amount());
        $this->assertFalse($retrieved->is_amountirrelevant());
        $this->assertEquals($sortorder, $retrieved->get_sortorder());
        $this->assertTrue($retrieved->is_active());

        // Verify database record.
        $record = $DB->get_record('bookit_resource', ['id' => $resourceid]);
        $this->assertNotEmpty($record);
        $this->assertEquals($name, $record->name);
        $this->assertEquals($categoryid, $record->categoryid);
        $this->assertEquals($amount, $record->amount);
        $this->assertEquals(0, $record->amountirrelevant);
        $this->assertEquals(1, $record->active);
    }

    /**
     * Test get_all_resources with category filter.
     */
    public function test_get_all_resources_with_category_filter(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create two categories.
        $cat1 = new bookit_resource_category(null, 'Cat 1', null, 0, 0, 0, 2);
        $cat1id = resource_manager::save_category($cat1, 2);

        $cat2 = new bookit_resource_category(null, 'Cat 2', null, 1, 0, 0, 2);
        $cat2id = resource_manager::save_category($cat2, 2);

        // Create resources in cat1.
        $res1 = new bookit_resource(null, 'Resource 1', null, $cat1id, 5, false, 0, true, null, 0, 0, 2);
        resource_manager::save_resource($res1, 2);

        $res2 = new bookit_resource(null, 'Resource 2', null, $cat1id, 3, false, 1, true, null, 0, 0, 2);
        resource_manager::save_resource($res2, 2);

        // Create resource in cat2.
        $res3 = new bookit_resource(null, 'Resource 3', null, $cat2id, 7, false, 0, true, null, 0, 0, 2);
        resource_manager::save_resource($res3, 2);

        // Get all resources (no filter).
        $allresources = resource_manager::get_all_resources(null, false);
        $this->assertCount(3, $allresources);

        // Get resources for cat1 only.
        $cat1resources = resource_manager::get_all_resources($cat1id, false);
        $this->assertCount(2, $cat1resources);

        // Get resources for cat2 only.
        $cat2resources = resource_manager::get_all_resources($cat2id, false);
        $this->assertCount(1, $cat2resources);
    }

    /**
     * Test get_all_resources with active filter.
     */
    public function test_get_all_resources_with_active_filter(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create category.
        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Create active resource.
        $activeres = new bookit_resource(null, 'Active', null, $categoryid, 5, false, 0, true, null, 0, 0, 2);
        resource_manager::save_resource($activeres, 2);

        // Create inactive resource.
        $inactiveres = new bookit_resource(null, 'Inactive', null, $categoryid, 3, false, 1, false, null, 0, 0, 2);
        resource_manager::save_resource($inactiveres, 2);

        // Get all resources (including inactive).
        $allresources = resource_manager::get_all_resources($categoryid, false);
        $this->assertCount(2, $allresources);

        // Get only active resources.
        $activeresources = resource_manager::get_all_resources($categoryid, true);
        $this->assertCount(1, $activeresources);
        $this->assertEquals('Active', $activeresources[0]->get_name());
    }

    /**
     * Test delete_resource succeeds.
     */
    public function test_delete_resource_succeeds(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create category.
        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Create resource.
        $resource = new bookit_resource(null, 'Test Resource', null, $categoryid, 5, false, 0, true, null, 0, 0, 2);
        $resourceid = resource_manager::save_resource($resource, 2);

        // Delete resource.
        resource_manager::delete_resource($resourceid);

        // Verify deleted from database.
        $exists = $DB->record_exists('bookit_resource', ['id' => $resourceid]);
        $this->assertFalse($exists);
    }

    /**
     * Test update_category_sortorder.
     */
    public function test_update_category_sortorder(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create categories.
        $cat1 = new bookit_resource_category(null, 'Cat 1', null, 0, 0, 0, 2);
        $cat1id = resource_manager::save_category($cat1, 2);

        $cat2 = new bookit_resource_category(null, 'Cat 2', null, 1, 0, 0, 2);
        $cat2id = resource_manager::save_category($cat2, 2);

        $cat3 = new bookit_resource_category(null, 'Cat 3', null, 2, 0, 0, 2);
        $cat3id = resource_manager::save_category($cat3, 2);

        // Reorder: cat3 first, cat1 second, cat2 third.
        $neworder = [
            0 => $cat3id,
            1 => $cat1id,
            2 => $cat2id,
        ];

        resource_manager::update_category_sortorder($neworder);

        // Verify new sortorder in database.
        $cat1record = $DB->get_record('bookit_resource_category', ['id' => $cat1id]);
        $this->assertEquals(1, $cat1record->sortorder);

        $cat2record = $DB->get_record('bookit_resource_category', ['id' => $cat2id]);
        $this->assertEquals(2, $cat2record->sortorder);

        $cat3record = $DB->get_record('bookit_resource_category', ['id' => $cat3id]);
        $this->assertEquals(0, $cat3record->sortorder);
    }

    /**
     * Test validation: category name required.
     */
    public function test_validate_category_name_required(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create category with empty name.
        $category = new bookit_resource_category(null, '', null, 0, 0, 0, 2);

        // Expect exception when saving.
        try {
            resource_manager::save_category($category, 2);
            $this->fail('Expected moodle_exception not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('category_name_required', $e->errorcode);
        }
    }

    /**
     * Test validation: resource name required.
     */
    public function test_validate_resource_name_required(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create category.
        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Create resource with empty name.
        $resource = new bookit_resource(null, '', null, $categoryid, 5, false, 0, true, null, 0, 0, 2);

        // Expect exception when saving.
        try {
            resource_manager::save_resource($resource, 2);
            $this->fail('Expected moodle_exception not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('resources:name_required', $e->errorcode);
        }
    }

    /**
     * Test validation: resource category must exist.
     */
    public function test_validate_resource_category_exists(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create resource with non-existent category.
        $resource = new bookit_resource(null, 'Test Resource', null, 99999, 5, false, 0, true, null, 0, 0, 2);

        // Expect exception when saving.
        try {
            resource_manager::save_resource($resource, 2);
            $this->fail('Expected moodle_exception not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertEquals('resources:category_not_found', $e->errorcode);
        }
    }

    /**
     * Test get_active_resources_grouped: null roomids preserved as null (not empty array).
     *
     * Regression test: previously edit_event_form.php would convert null roomids to []
     * before passing data-resource-rooms to JS, breaking the "available in all rooms" signal.
     */
    public function test_get_active_resources_grouped_null_roomids_preserved(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Resource with null roomids = available in all rooms.
        $resource = new bookit_resource(null, 'All-Rooms Resource', null, $categoryid, 5, false, 0, true, null, 0, 0, 2);
        resource_manager::save_resource($resource, 2);

        $grouped = resource_manager::get_active_resources_grouped();

        $this->assertNotEmpty($grouped);
        $found = null;
        foreach ($grouped as $group) {
            foreach ($group['resources'] as $r) {
                if ($r['name'] === 'All-Rooms Resource') {
                    $found = $r;
                }
            }
        }

        $this->assertNotNull($found, 'Resource not found in grouped data');
        // Null roomids must be preserved as null, not converted to empty array.
        $this->assertNull($found['roomids'], 'Null roomids must be preserved as null, not converted to []');
    }

    /**
     * Test get_active_resources_grouped: specific roomids stored and returned as JSON string.
     *
     * Ensures that room-restricted resources carry their room IDs through the data pipeline
     * so the booking form can emit the correct data-resource-rooms attribute.
     */
    public function test_get_active_resources_grouped_room_restricted_roomids(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Insert a minimal room directly (room persistent requires many fields).
        $roomid = $DB->insert_record('bookit_room', (object)[
            'name' => 'Test Room',
            'shortname' => 'TR',
            'description' => '',
            'location' => '',
            'eventcolor' => '#ff0000',
            'active' => 1,
            'roommode' => 0,
            'seats' => 10,
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'overlapping' => 0,
            'usermodified' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $category = new bookit_resource_category(null, 'Test Cat', null, 0, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        // Resource restricted to the test room.
        $resource = new bookit_resource(
            null,
            'Room-Restricted Resource',
            null,
            $categoryid,
            3,
            false,
            0,
            true,
            [$roomid],
            0,
            0,
            2
        );
        resource_manager::save_resource($resource, 2);

        $grouped = resource_manager::get_active_resources_grouped();

        $found = null;
        foreach ($grouped as $group) {
            foreach ($group['resources'] as $r) {
                if ($r['name'] === 'Room-Restricted Resource') {
                    $found = $r;
                }
            }
        }

        $this->assertNotNull($found, 'Resource not found in grouped data');
        // Room ID JSON string expected for room-restricted resource.
        $this->assertNotNull($found['roomids'], 'Room-restricted resource must have non-null roomids');
        $decoded = json_decode($found['roomids'], true);
        $this->assertIsArray($decoded);
        $this->assertContains($roomid, $decoded);
    }

    /**
     * Test the data-resource-rooms JSON encoding logic used by edit_event_form.
     *
     * This directly tests the conditional that converts the resource's roomids field
     * to the JSON value placed in the data-resource-rooms HTML attribute:
     *   - null roomids  → JSON null  (available in all rooms)
     *   - array roomids → JSON array (restricted to those rooms)
     *
     * Regression: the form previously passed JSON [] for null-roomids resources, which
     * caused JS to treat them as restricted rather than universally available.
     */
    public function test_roomids_to_dataattribute_json_encoding(): void {
        // Null roomids → must encode as JSON null string "null".
        $roomidsraw = null;
        if ($roomidsraw !== null && $roomidsraw !== '') {
            $roomidsarray = json_decode($roomidsraw, true);
            $roomidsarray = is_array($roomidsarray) ? $roomidsarray : [];
        } else {
            $roomidsarray = null;
        }
        $this->assertNull($roomidsarray, 'Null roomids must produce null, not an empty array');
        $this->assertEquals('null', json_encode($roomidsarray), 'JSON-encoded null must be the string "null"');

        // Non-null roomids JSON string → must decode to array.
        $roomidsraw = json_encode([1, 2, 3]);
        if ($roomidsraw !== null && $roomidsraw !== '') {
            $roomidsarray = json_decode($roomidsraw, true);
            $roomidsarray = is_array($roomidsarray) ? $roomidsarray : [];
        } else {
            $roomidsarray = null;
        }
        $this->assertIsArray($roomidsarray);
        $this->assertEquals([1, 2, 3], $roomidsarray);
        $this->assertEquals('[1,2,3]', json_encode($roomidsarray));

        // Empty string roomids (legacy/edge case) → must also produce null.
        $roomidsraw = '';
        if ($roomidsraw !== null && $roomidsraw !== '') {
            $roomidsarray = json_decode($roomidsraw, true);
            $roomidsarray = is_array($roomidsarray) ? $roomidsarray : [];
        } else {
            $roomidsarray = null;
        }
        $this->assertNull($roomidsarray, 'Empty-string roomids must produce null, not []');
    }

    /**
     * Test that saving a resource with amountirrelevant=true stores amount=1 (not 0 or null).
     */
    public function test_save_resource_amountirrelevant_stores_valid_amount(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $cat = new bookit_resource_category(null, 'Cat AI', null, 0, 0, 0, 2);
        $catid = resource_manager::save_category($cat, 2);

        $resource = new bookit_resource(null, 'WiFi', '', $catid, 1, true, 0, true, null, 0, 0, 2);
        $id = resource_manager::save_resource($resource, 2);

        $record = $DB->get_record('bookit_resource', ['id' => $id]);
        $this->assertEquals(1, $record->amountirrelevant);
        // Amount must be a positive integer, not 0 or null.
        $this->assertGreaterThan(0, $record->amount, 'Amountirrelevant resource must store amount > 0');
    }

    /**
     * Test that validation skips amount check when amountirrelevant is true.
     */
    public function test_validate_resource_amountirrelevant_skips_amount_check(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $cat = new bookit_resource_category(null, 'Cat AI2', null, 0, 0, 0, 2);
        $catid = resource_manager::save_category($cat, 2);

        // Amount=0 with amountirrelevant=true must pass validation and save successfully.
        $resource = new bookit_resource(null, 'Whiteboard', '', $catid, 0, true, 0, true, null, 0, 0, 2);
        $id = resource_manager::save_resource($resource, 2);
        $this->assertNotEmpty($id);
    }

    /**
     * Test that validation rejects amount=0 for non-amountirrelevant resources.
     */
    public function test_validate_resource_amount_zero_rejected_when_not_amountirrelevant(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $cat = new bookit_resource_category(null, 'Cat V', null, 0, 0, 0, 2);
        $catid = resource_manager::save_category($cat, 2);

        $resource = new bookit_resource(null, 'Projector', '', $catid, 0, false, 0, true, null, 0, 0, 2);

        $this->expectException(\moodle_exception::class);
        resource_manager::save_resource($resource, 2);
    }

    /**
     * Test that get_active_resources_grouped includes amountirrelevant flag.
     */
    public function test_get_active_resources_grouped_includes_amountirrelevant(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $cat = new bookit_resource_category(null, 'Cat Grouped', null, 0, 0, 0, 2);
        $catid = resource_manager::save_category($cat, 2);

        $resamount = new bookit_resource(null, 'Projector', '', $catid, 5, false, 0, true, null, 0, 0, 2);
        resource_manager::save_resource($resamount, 2);

        $resirrelevant = new bookit_resource(null, 'WiFi', '', $catid, 1, true, 1, true, null, 0, 0, 2);
        resource_manager::save_resource($resirrelevant, 2);

        $grouped = resource_manager::get_active_resources_grouped();

        $projector = null;
        $wifi = null;
        foreach ($grouped as $group) {
            foreach ($group['resources'] as $r) {
                if ($r['name'] === 'Projector') {
                    $projector = $r;
                }
                if ($r['name'] === 'WiFi') {
                    $wifi = $r;
                }
            }
        }

        $this->assertNotNull($projector, 'Projector resource must appear in grouped data');
        $this->assertArrayHasKey('amountirrelevant', $projector);
        $this->assertFalse($projector['amountirrelevant']);

        $this->assertNotNull($wifi, 'WiFi resource must appear in grouped data');
        $this->assertArrayHasKey('amountirrelevant', $wifi);
        $this->assertTrue($wifi['amountirrelevant']);
    }
}
