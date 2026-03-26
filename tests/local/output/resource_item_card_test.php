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
 * Unit tests for resource_item_card output class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\output;

use advanced_testcase;
use mod_bookit\local\entity\resource\bookit_resource;
use mod_bookit\local\entity\resource\bookit_resource_category;
use mod_bookit\local\manager\resource_manager;
use mod_bookit\output\resource_item_card;

/**
 * Unit tests for resource_item_card output class.
 *
 * These tests guard against the null-roomids regression:
 * resources available in all rooms (null roomids) must be encoded as JSON null
 * in the data-item-roomids / data-rooms HTML attributes so the JS room filter
 * treats them as universally visible.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\output\resource_item_card
 */
final class resource_item_card_test extends advanced_testcase {
    /**
     * Test that a resource with null roomids exports JSON null to the template.
     *
     * Regression: resource_item_card previously used json_encode(null ?? []) which
     * produced "[]". The JS filter treated "[]" as "no rooms → hidden when filter active".
     * The correct value is "null" so JS treats it as "available in all rooms".
     */
    public function test_null_roomids_exported_as_json_null(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $category = new bookit_resource_category(null, 'Test Cat', null, 0, true, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        $resource = new bookit_resource(null, 'All-Rooms Resource', null, $categoryid, 1, false, 0, true, null, 0, 0, 2);
        resource_manager::save_resource($resource, 2);

        // Reload resource from DB to get the saved instance.
        $resources = resource_manager::get_all_resources($categoryid, true);
        $this->assertCount(1, $resources);
        $res = $resources[0];
        $this->assertNull($res->get_roomids(), 'Saved resource must have null roomids');

        $card = new resource_item_card($res, 3);
        $output = $this->get_renderer();
        $data = $card->export_for_template($output);

        // The roomids field in the template data must be "null" (not "[]").
        $this->assertEquals(
            'null',
            $data->roomids,
            'Null roomids must be encoded as JSON "null" for the room filter to work correctly'
        );

        // Also verify it decodes back to null.
        $decoded = json_decode($data->roomids);
        $this->assertNull($decoded, 'Decoded roomids must be null, not empty array');
    }

    /**
     * Test that a room-restricted resource exports its room IDs as JSON array.
     */
    public function test_restricted_roomids_exported_as_json_array(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $roomid = $DB->insert_record('bookit_room', (object)[
            'name' => 'Test Room',
            'shortname' => 'TR',
            'description' => '',
            'location' => '',
            'eventcolor' => '#3a87ad',
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

        $category = new bookit_resource_category(null, 'Test Cat', null, 0, true, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        $resource = new bookit_resource(
            null,
            'Room-Only Resource',
            null,
            $categoryid,
            1,
            false,
            0,
            true,
            [$roomid],
            0,
            0,
            2
        );
        resource_manager::save_resource($resource, 2);

        $resources = resource_manager::get_all_resources($categoryid, true);
        $res = $resources[0];

        $card = new resource_item_card($res, 1);
        $output = $this->get_renderer();
        $data = $card->export_for_template($output);

        // JSON array with room ID expected for room-restricted resource.
        $decoded = json_decode($data->roomids, true);
        $this->assertIsArray($decoded, 'Room-restricted resource must have array roomids');
        $this->assertContains($roomid, $decoded);
    }

    /**
     * Test isallrooms flag: null roomids → always true when rooms exist.
     *
     * A resource with null roomids is available in ALL rooms, so isallrooms must be true
     * whenever there are any rooms in the system.
     */
    public function test_null_roomids_sets_isallrooms_true(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a room so totalrooms > 0.
        $DB->insert_record('bookit_room', (object)[
            'name' => 'Room 1', 'shortname' => 'R1', 'description' => '', 'location' => '',
            'eventcolor' => '#3a87ad', 'active' => 1, 'roommode' => 0, 'seats' => 10,
            'extratimebefore' => 0, 'extratimeafter' => 0, 'overlapping' => 0,
            'usermodified' => 2, 'timecreated' => time(), 'timemodified' => time(),
        ]);

        $category = new bookit_resource_category(null, 'Cat', null, 0, true, 0, 0, 2);
        $categoryid = resource_manager::save_category($category, 2);

        $resource = new bookit_resource(null, 'Universal', null, $categoryid, 1, false, 0, true, null, 0, 0, 2);
        resource_manager::save_resource($resource, 2);
        $res = resource_manager::get_all_resources($categoryid, true)[0];

        $card = new resource_item_card($res, 1); // 1 total room in system.
        $output = $this->get_renderer();
        $data = $card->export_for_template($output);

        $this->assertTrue(
            $data->isallrooms,
            'Resource with null roomids must have isallrooms=true when rooms exist'
        );
    }

    /**
     * Get a renderer instance for testing.
     */
    private function get_renderer(): \renderer_base {
        global $PAGE;
        return $PAGE->get_renderer('core');
    }
}
