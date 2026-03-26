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
 * Unit tests for resource_notification_manager class.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use mod_bookit\local\entity\resource\bookit_resource_status;

/**
 * Unit tests for resource_notification_manager.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\manager\resource_notification_manager
 */
final class resource_notification_manager_test extends advanced_testcase {
    /** @var int $cmid Fake course-module ID used in all tests */
    private int $cmid = 42;

    /**
     * Set up common fixtures.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        // Register the message provider so message_send() can deliver in test context.
        message_update_providers('mod_bookit');
    }

    /**
     * Test that the booker (usermodified) receives a notification.
     */
    public function test_notify_sends_to_booker(): void {
        $booker = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id);
        $resourceid = $this->create_test_resource();

        $sink = $this->redirectMessages();

        resource_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            $resourceid,
            bookit_resource_status::CONFIRMED
        );

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertEquals($booker->id, $messages[0]->useridto);
        $this->assertEquals('mod_bookit', $messages[0]->component);
        $this->assertEquals('bookit_resource_status_changed', $messages[0]->eventtype);
    }

    /**
     * Test that personinchargeid also receives a notification.
     */
    public function test_notify_sends_to_person_in_charge(): void {
        $booker = $this->getDataGenerator()->create_user();
        $examiner = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id, $examiner->id);
        $resourceid = $this->create_test_resource();

        $sink = $this->redirectMessages();

        resource_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            $resourceid,
            bookit_resource_status::CONFIRMED
        );

        $messages = $sink->get_messages();
        $sink->close();

        $recipientids = array_column($messages, 'useridto');
        $this->assertCount(2, $messages);
        $this->assertContains((string)$booker->id, $recipientids);
        $this->assertContains((string)$examiner->id, $recipientids);
    }

    /**
     * Test that other examiners (comma-separated) also receive notifications.
     */
    public function test_notify_sends_to_other_examiners(): void {
        $booker = $this->getDataGenerator()->create_user();
        $examiner1 = $this->getDataGenerator()->create_user();
        $examiner2 = $this->getDataGenerator()->create_user();
        $otherexaminers = $examiner1->id . ',' . $examiner2->id;

        $eventid = $this->create_test_event($booker->id, null, $otherexaminers);
        $resourceid = $this->create_test_resource();

        $sink = $this->redirectMessages();

        resource_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            $resourceid,
            bookit_resource_status::REJECTED
        );

        $messages = $sink->get_messages();
        $sink->close();

        // Booker + 2 other examiners = 3 messages.
        $this->assertCount(3, $messages);
    }

    /**
     * Test that duplicate recipient IDs are deduplicated.
     */
    public function test_notify_deduplicates_recipients(): void {
        $user = $this->getDataGenerator()->create_user();
        // Same user in both usermodified and personinchargeid.
        $eventid = $this->create_test_event($user->id, $user->id);
        $resourceid = $this->create_test_resource();

        $sink = $this->redirectMessages();

        resource_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            $resourceid,
            bookit_resource_status::INPROGRESS
        );

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
    }

    /**
     * Test that no messages are sent when the event does not exist.
     */
    public function test_notify_returns_zero_for_missing_event(): void {
        $resourceid = $this->create_test_resource();

        $sink = $this->redirectMessages();

        $sent = resource_notification_manager::notify_status_changed(
            $this->cmid,
            99999,
            $resourceid,
            bookit_resource_status::CONFIRMED
        );

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertEquals(0, $sent);
        $this->assertCount(0, $messages);
    }

    /**
     * Test that notification subject contains the event name.
     */
    public function test_notify_subject_contains_event_name(): void {
        $booker = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id, null, null, 'My Special Exam');
        $resourceid = $this->create_test_resource('Projector');

        $sink = $this->redirectMessages();

        resource_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            $resourceid,
            bookit_resource_status::CONFIRMED
        );

        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('My Special Exam', $messages[0]->subject);
    }

    /**
     * Insert a minimal bookit_event record for testing.
     *
     * @param int $usermodified ID of the booker
     * @param int|null $personinchargeid ID of the examiner
     * @param string|null $otherexaminers Comma-separated user IDs
     * @param string $name Event name
     * @return int Event ID
     */
    private function create_test_event(
        int $usermodified,
        ?int $personinchargeid = null,
        ?string $otherexaminers = null,
        string $name = 'Test Event'
    ): int {
        global $DB;

        $record = new \stdClass();
        $record->name = $name;
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
        $record->personinchargeid = $personinchargeid;
        $record->otherexaminers = $otherexaminers;
        $record->coursetemplate = null;
        $record->notes = null;
        $record->internalnotes = null;
        $record->supportpersons = null;
        $record->extratimebefore = 0;
        $record->extratimeafter = 0;
        $record->refcourseid = null;
        $record->usermodified = $usermodified;
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('bookit_event', $record);
    }

    /**
     * Insert a minimal bookit_resource record for testing.
     *
     * @param string $name Resource name
     * @return int Resource ID
     */
    private function create_test_resource(string $name = 'Test Resource'): int {
        global $DB;

        $catid = $DB->insert_record('bookit_resource_category', (object)[
            'name'         => 'Test Category',
            'description'  => '',
            'sortorder'    => 0,
            'usermodified' => 2,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        return $DB->insert_record('bookit_resource', (object)[
            'name'             => $name,
            'description'      => '',
            'amount'           => 5,
            'amountirrelevant' => 0,
            'categoryid'       => $catid,
            'roomids'          => '[]',
            'sortorder'        => 0,
            'active'           => 1,
            'usermodified'     => 2,
            'timecreated'      => time(),
            'timemodified'     => time(),
        ]);
    }
}
