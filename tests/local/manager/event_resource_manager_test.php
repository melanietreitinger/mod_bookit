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
 * Unit tests for event_resource_manager class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use mod_bookit\local\entity\resource\bookit_event_resource;
use mod_bookit\local\entity\resource\bookit_resource;
use mod_bookit\local\entity\resource\bookit_resource_category;
use mod_bookit\local\entity\resource\bookit_resource_status;

/**
 * Unit tests for event_resource_manager class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\manager\event_resource_manager
 */
final class event_resource_manager_test extends advanced_testcase {
    /** @var int Test category ID */
    private int $categoryid;

    /** @var int Test resource ID */
    private int $resourceid;

    /** @var int Test event ID */
    private int $eventid;

    /**
     * Set up test data.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a category via manager.
        $category = new bookit_resource_category(null, 'Test Category', '', 0, true, 0, 0, 2);
        $this->categoryid = resource_manager::save_category($category, 2);

        // Create a resource via manager.
        $resource = new bookit_resource(
            null,
            'Test Projector',
            'A projector',
            $this->categoryid,
            2,
            false,
            0,
            true,
            null,
            0,
            0,
            2
        );
        $this->resourceid = resource_manager::save_resource($resource, 2);

        // Insert a minimal test event directly into DB.
        $this->eventid = $this->create_test_event();
    }

    /**
     * Insert a minimal bookit_event record for testing.
     *
     * @return int Event ID
     */
    private function create_test_event(): int {
        global $DB;

        $record = new \stdClass();
        $record->name = 'Test Event';
        $record->semester = 0;
        $record->institutionid = 0;
        $record->starttime = time();
        $record->endtime = time() + 3600;
        $record->duration = null;
        $record->roomid = 0;
        $record->participantsamount = null;
        $record->timecompensation = null;
        $record->compensationfordisadvantages = null;
        $record->bookingstatus = 0;
        $record->personinchargeid = null;
        $record->otherexaminers = null;
        $record->coursetemplate = null;
        $record->notes = null;
        $record->internalnotes = null;
        $record->supportpersons = null;
        $record->extratimebefore = 0;
        $record->extratimeafter = 0;
        $record->refcourseid = null;
        $record->usermodified = 2;
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('bookit_event', $record);
    }

    /**
     * Test adding a resource to an event.
     */
    public function test_add_resource_to_event(): void {
        $id = event_resource_manager::add_resource_to_event(
            $this->eventid,
            $this->resourceid,
            1,
            2
        );

        $this->assertNotEmpty($id);
        $this->assertIsInt($id);
    }

    /**
     * Test getting resources for an event.
     */
    public function test_get_resources_for_event(): void {
        // Initially empty.
        $resources = event_resource_manager::get_resources_for_event($this->eventid);
        $this->assertEmpty($resources);

        // Add a resource.
        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 2, 2);

        $resources = event_resource_manager::get_resources_for_event($this->eventid);
        $this->assertCount(1, $resources);

        $entity = reset($resources);
        $this->assertInstanceOf(bookit_event_resource::class, $entity);
        $this->assertEquals($this->eventid, $entity->get_eventid());
        $this->assertEquals($this->resourceid, $entity->get_resourceid());
        $this->assertEquals(2, $entity->get_amount());
        $this->assertEquals(bookit_resource_status::REQUESTED, $entity->get_status());
    }

    /**
     * Test getting a specific event-resource relationship.
     */
    public function test_get_event_resource(): void {
        // Returns null when not found.
        $entity = event_resource_manager::get_event_resource($this->eventid, $this->resourceid);
        $this->assertNull($entity);

        // Add and retrieve.
        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 1, 2);

        $entity = event_resource_manager::get_event_resource($this->eventid, $this->resourceid);
        $this->assertNotNull($entity);
        $this->assertInstanceOf(bookit_event_resource::class, $entity);
        $this->assertEquals($this->eventid, $entity->get_eventid());
        $this->assertEquals($this->resourceid, $entity->get_resourceid());
    }

    /**
     * Test updating resource amount for an event.
     */
    public function test_update_resource_amount(): void {
        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 1, 2);

        $result = event_resource_manager::update_resource_amount(
            $this->eventid,
            $this->resourceid,
            5,
            2
        );

        $this->assertTrue($result);

        $entity = event_resource_manager::get_event_resource($this->eventid, $this->resourceid);
        $this->assertEquals(5, $entity->get_amount());
    }

    /**
     * Test update_resource_amount returns false for non-existent record.
     */
    public function test_update_resource_amount_not_found(): void {
        $result = event_resource_manager::update_resource_amount(
            999,
            999,
            5,
            2
        );

        $this->assertFalse($result);
    }

    /**
     * Test updating status sets usermodified from current user.
     */
    public function test_update_status_sets_usermodified(): void {
        global $DB;

        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 1, 2);

        $result = event_resource_manager::update_status(
            $this->eventid,
            $this->resourceid,
            bookit_resource_status::CONFIRMED
        );

        $this->assertTrue($result);

        $record = $DB->get_record('bookit_event_resource', [
            'eventid'    => $this->eventid,
            'resourceid' => $this->resourceid,
        ]);

        $this->assertEquals(bookit_resource_status::CONFIRMED->value, $record->status);
        // Usermodified should be the admin user ID (2).
        $this->assertEquals(2, (int)$record->usermodified);
    }

    /**
     * Test updating status for non-existent record returns false.
     */
    public function test_update_status_not_found(): void {
        $result = event_resource_manager::update_status(999, 999, bookit_resource_status::CONFIRMED);
        $this->assertFalse($result);
    }

    /**
     * Test all status values can be set.
     */
    public function test_update_status_all_values(): void {
        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 1, 2);

        $statuses = [
            bookit_resource_status::REQUESTED,
            bookit_resource_status::CONFIRMED,
            bookit_resource_status::INPROGRESS,
            bookit_resource_status::REJECTED,
        ];

        foreach ($statuses as $status) {
            $result = event_resource_manager::update_status(
                $this->eventid,
                $this->resourceid,
                $status
            );
            $this->assertTrue($result);

            $entity = event_resource_manager::get_event_resource($this->eventid, $this->resourceid);
            $this->assertEquals($status, $entity->get_status());
        }
    }

    /**
     * Test removing a resource from an event.
     */
    public function test_remove_resource_from_event(): void {
        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 1, 2);

        $result = event_resource_manager::remove_resource_from_event(
            $this->eventid,
            $this->resourceid
        );

        $this->assertTrue($result);

        $resources = event_resource_manager::get_resources_for_event($this->eventid);
        $this->assertEmpty($resources);
    }

    /**
     * Test removing all resources from an event.
     */
    public function test_remove_all_resources_from_event(): void {
        // Add second resource.
        $resource2 = new bookit_resource(
            null,
            'Second Resource',
            '',
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
        $resourceid2 = resource_manager::save_resource($resource2, 2);

        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 1, 2);
        event_resource_manager::add_resource_to_event($this->eventid, $resourceid2, 1, 2);

        $resources = event_resource_manager::get_resources_for_event($this->eventid);
        $this->assertCount(2, $resources);

        event_resource_manager::remove_all_resources_from_event($this->eventid);

        $resources = event_resource_manager::get_resources_for_event($this->eventid);
        $this->assertEmpty($resources);
    }

    /**
     * Test getting events for a resource.
     */
    public function test_get_events_for_resource(): void {
        // Initially empty.
        $events = event_resource_manager::get_events_for_resource($this->resourceid);
        $this->assertEmpty($events);

        event_resource_manager::add_resource_to_event($this->eventid, $this->resourceid, 1, 2);

        $events = event_resource_manager::get_events_for_resource($this->resourceid);
        $this->assertCount(1, $events);
    }

    /**
     * Test add with explicit status.
     */
    public function test_add_resource_with_explicit_status(): void {
        event_resource_manager::add_resource_to_event(
            $this->eventid,
            $this->resourceid,
            1,
            2,
            bookit_resource_status::CONFIRMED
        );

        $entity = event_resource_manager::get_event_resource($this->eventid, $this->resourceid);
        $this->assertEquals(bookit_resource_status::CONFIRMED, $entity->get_status());
    }
}
