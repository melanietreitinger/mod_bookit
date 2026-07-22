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
 * Unit tests for lifecycle history tracking.
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
use stdClass;
use mod_bookit\local\entity\bookit_event;

/**
 * Unit tests for lifecycle history tracking.
 *
 * @covers \mod_bookit\local\manager\event_manager
 */
final class lifecycle_history_test extends advanced_testcase {
    /**
     * Create a Bookit module context for lifecycle tests.
     *
     * @return context_module
     */
    private function create_bookit_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit lifecycle test']);
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
            'name' => 'Lifecycle room',
            'shortname' => 'LIFE01',
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
            $overrides['name'] ?? 'Lifecycle event',
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
            $userid,
            time(),
            time(),
            $overrides['resources'] ?? [],
        );
    }

    /**
     * Creating a booking through the tracked save path must write a visible history entry.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_records_created_history_entry(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_event($roomid, (int)$user->id, ['name' => 'Created lifecycle event']);

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, (int)$user->id, $context);

        $history = array_values(event_manager::get_booking_history($saved->id));
        $this->assertCount(1, $history);
        $this->assertSame('created', $history[0]->action);
        $this->assertNull($history[0]->oldstatus);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$history[0]->newstatus);
        $this->assertNull($history[0]->changedfields);
        $this->assertGreaterThan(0, (int)$history[0]->timecreated);
        $this->assertSame($user->firstname, $history[0]->firstname);
        $this->assertSame($user->lastname, $history[0]->lastname);
    }

    /**
     * Updating business fields without a status change must still write one history entry with changed fields.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_records_updated_fields_without_status_change(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_event($roomid, (int)$user->id);
        $event->save((int)$user->id);

        $before = bookit_event::from_database($event->id);
        $updated = bookit_event::from_database($event->id);
        $updated->name = 'Updated lifecycle event';
        $updated->internalnotes = 'Updated internal note';

        event_manager::save_event_with_lifecycle_tracking($updated, $before, (int)$user->id, $context);

        $history = array_values(event_manager::get_booking_history($updated->id));
        $this->assertCount(1, $history);
        $this->assertSame('updated', $history[0]->action);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$history[0]->oldstatus);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$history[0]->newstatus);

        $changedfields = json_decode($history[0]->changedfields, true);
        $this->assertSame('Lifecycle event', $changedfields['name']['from']);
        $this->assertSame('Updated lifecycle event', $changedfields['name']['to']);
        $this->assertSame('Internal note', $changedfields['internalnotes']['from']);
        $this->assertSame('Updated internal note', $changedfields['internalnotes']['to']);
        $this->assertGreaterThan(0, (int)$history[0]->timecreated);
        $this->assertSame($user->firstname, $history[0]->firstname);
        $this->assertSame($user->lastname, $history[0]->lastname);
    }

    /**
     * Recovery transitions must keep a visible marker in the workflow history.
     *
     * @return void
     */
    public function test_transition_booking_status_marks_recovery_entries(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_event($roomid, (int)$user->id, [
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'name' => 'Recovery lifecycle event',
        ]);
        $event->save((int)$user->id);

        global $DB;

        /** @var stdClass $persisted */
        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        event_manager::transition_booking_status(
            $persisted,
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$user->id,
            $context
        );

        $history = array_values(event_manager::get_booking_history($event->id));
        $this->assertCount(1, $history);
        $this->assertSame('restored', $history[0]->action);
        $this->assertSame(1, (int)$history[0]->recoverymarker);

        $changedfields = json_decode($history[0]->changedfields, true);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CANCELED, $changedfields['bookingstatus']['from']);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, $changedfields['bookingstatus']['to']);
    }
}
