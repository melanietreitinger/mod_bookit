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
 * Unit tests for booking_notification_manager.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;

/**
 * Unit tests for booking notifications.
 *
 * @covers \mod_bookit\local\manager\booking_notification_manager
 */
final class booking_notification_manager_test extends advanced_testcase {
    /**
     * Set up message providers.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        message_update_providers('mod_bookit');
        set_config('bookingstatus_notify_bookingperson', 1, 'mod_bookit');
        set_config('bookingstatus_notify_personincharge', 1, 'mod_bookit');
        set_config('bookingstatus_notify_otherexaminers', 1, 'mod_bookit');
        set_config('bookingstatus_service_addresses', '', 'mod_bookit');
    }

    /**
     * Test that all configured user recipients receive the booking notification.
     *
     * @return void
     */
    public function test_notify_status_changed_sends_to_booking_roles(): void {
        $booker = $this->getDataGenerator()->create_user();
        $personincharge = $this->getDataGenerator()->create_user();
        $otherexaminer = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id, $personincharge->id, (string)$otherexaminer->id);

        $sink = $this->redirectMessages();
        $sent = booking_notification_manager::notify_status_changed(
            42,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_ACCEPTED
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertSame(3, $sent);
        $this->assertCount(3, $messages);
        $this->assertSame('mod_bookit', $messages[0]->component);
        $this->assertSame('bookit_booking_status_changed', $messages[0]->eventtype);
    }

    /**
     * Test that disabled recipient toggles reduce the sent messages.
     *
     * @return void
     */
    public function test_notify_status_changed_respects_recipient_settings(): void {
        $booker = $this->getDataGenerator()->create_user();
        $personincharge = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id, $personincharge->id);

        set_config('bookingstatus_notify_personincharge', 0, 'mod_bookit');

        $sink = $this->redirectMessages();
        $sent = booking_notification_manager::notify_status_changed(
            42,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertSame(1, $sent);
        $this->assertCount(1, $messages);
        $this->assertSame((string)$booker->id, $messages[0]->useridto);
    }

    /**
     * Insert a minimal test event.
     *
     * @param int $usermodified
     * @param int|null $personinchargeid
     * @param string|null $otherexaminers
     * @return int
     */
    private function create_test_event(
        int $usermodified,
        ?int $personinchargeid = null,
        ?string $otherexaminers = null
    ): int {
        global $DB;

        return $DB->insert_record('bookit_event', (object)[
            'name' => 'Booking Notification Event',
            'semester' => 20261,
            'institutionid' => 0,
            'starttime' => time() + 3600,
            'endtime' => time() + 7200,
            'duration' => 60,
            'roomid' => 0,
            'participantsamount' => null,
            'timecompensation' => null,
            'compensationfordisadvantages' => null,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $personinchargeid,
            'otherexaminers' => $otherexaminers,
            'coursetemplate' => null,
            'notes' => null,
            'internalnotes' => null,
            'supportpersons' => null,
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $usermodified,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }
}
