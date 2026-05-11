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
 * Unit tests for lifecycle audit events.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use context_module;
use mod_bookit\event\booking_status_changed;
use mod_bookit\local\entity\bookit_event;

/**
 * Unit tests for lifecycle audit events.
 *
 * @covers \mod_bookit\local\manager\event_manager
 */
final class lifecycle_events_test extends advanced_testcase {
    /**
     * Create a Bookit module context for lifecycle tests.
     *
     * @return context_module
     */
    private function create_bookit_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit audit test']);
        return context_module::instance($bookit->cmid);
    }

    /**
     * Create a room for event persistence tests.
     *
     * @return int
     */
    private function create_room(): int {
        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        return $generator->create_room([
            'name' => 'Audit room',
            'shortname' => 'AUD001',
        ]);
    }

    /**
     * Build a test event entity.
     *
     * @param int $roomid
     * @param int $userid
     * @param array $overrides
     * @return bookit_event
     */
    private function build_event(int $roomid, int $userid, array $overrides = []): bookit_event {
        return new bookit_event(
            0,
            $overrides['name'] ?? 'Audit event',
            $overrides['semester'] ?? 20261,
            $overrides['institutionid'] ?? 1,
            $overrides['starttime'] ?? strtotime('2026-05-08 09:00:00'),
            $overrides['endtime'] ?? strtotime('2026-05-08 11:00:00'),
            $overrides['duration'] ?? 120,
            $overrides['roomid'] ?? $roomid,
            $overrides['participantsamount'] ?? 10,
            $overrides['timecompensation'] ?? 0,
            $overrides['compensationfordisadvantages'] ?? '',
            $overrides['bookingstatus'] ?? event_access_manager::BOOKINGSTATUS_NEW,
            $overrides['personinchargeid'] ?? null,
            $overrides['otherexaminers'] ?? '',
            $overrides['coursetemplate'] ?? 0,
            $overrides['notes'] ?? 'External note',
            $overrides['internalnotes'] ?? 'Internal note',
            $overrides['supportpersons'] ?? '',
            $overrides['extratimebefore'] ?? 0,
            $overrides['extratimeafter'] ?? 0,
            $overrides['refcourseid'] ?? null,
            $userid,
            time(),
            time(),
            $overrides['resources'] ?? [],
        );
    }

    /**
     * Creating a booking through the tracked save path must trigger the generic lifecycle audit event.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_triggers_created_audit_event(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_event($roomid, (int)$user->id, ['name' => 'Created audit event']);

        $sink = $this->redirectEvents();
        event_manager::save_event_with_lifecycle_tracking($event, null, (int)$user->id, $context);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(booking_status_changed::class, $events[0]);
        $this->assertSame('created', $events[0]->other['action']);
    }

    /**
     * Changing the booking status via the tracked save path must trigger the generic lifecycle audit event.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_triggers_canceled_audit_event(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_event($roomid, (int)$user->id, [
            'name' => 'Cancelable audit event',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
        ]);
        $event->save((int)$user->id);

        $before = bookit_event::from_database($event->id);
        $updated = bookit_event::from_database($event->id);
        $updated->bookingstatus = event_access_manager::BOOKINGSTATUS_CANCELED;

        $sink = $this->redirectEvents();
        event_manager::save_event_with_lifecycle_tracking($updated, $before, (int)$user->id, $context);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(booking_status_changed::class, $events[0]);
        $this->assertSame('canceled', $events[0]->other['action']);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_ACCEPTED, (int)$events[0]->other['oldstatus']);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CANCELED, (int)$events[0]->other['newstatus']);
    }
}
