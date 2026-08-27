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

namespace mod_bookit\local\read;

use advanced_testcase;
use mod_bookit\local\manager\event_access_manager;

/**
 * Unit tests for the canonical calendar event read mapper.
 *
 * @package     mod_bookit
 * @covers      \mod_bookit\local\read\calendar_event_read_mapper
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class calendar_event_read_mapper_test extends advanced_testcase {
    /**
     * Canonical production-shaped payloads map without alias fallbacks.
     *
     * @return void
     */
    public function test_map_accepts_canonical_payload(): void {
        $mapped = calendar_event_read_mapper::map([
            'id' => 42,
            'title' => 'Exam',
            'start' => '2026-05-08 09:00',
            'end' => '2026-05-08 11:00',
            'backgroundColor' => '#3a87ad',
            'textColor' => '#ffffff',
            'classNames' => ['hide-event-time'],
            'extendedProps' => [
                'titlehtml' => '<h6>09:00-11:00</h6>Exam',
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'semesterid' => 20261,
                'visibilitymode' => 'full',
                'modalfootermode' => 'edit',
                'room' => [
                    'roomid' => 7,
                    'roomname' => 'Hall',
                    'location' => 'Main',
                    'shortname' => 'H1',
                ],
                'faculty' => [
                    'facultyid' => 3,
                    'label' => 'Faculty A',
                ],
            ],
        ]);

        $this->assertSame(42, $mapped['id']);
        $this->assertSame('Exam', $mapped['title']);
        $this->assertSame('2026-05-08 09:00:00', $mapped['start']);
        $this->assertSame('2026-05-08 11:00:00', $mapped['end']);
        $this->assertSame(2, $mapped['extendedProps']['bookingstatus']);
        $this->assertSame(20261, $mapped['extendedProps']['semesterid']);
        $this->assertSame('Hall', $mapped['extendedProps']['room']['roomname']);
        $this->assertSame(3, $mapped['extendedProps']['faculty']['facultyid']);
        $this->assertSame('Faculty A', $mapped['extendedProps']['faculty']['label']);
    }

    /**
     * Alias-only payloads must not be treated as a supported contract.
     *
     * @return void
     */
    public function test_map_ignores_legacy_alias_keys(): void {
        $mapped = calendar_event_read_mapper::map([
            'eventid' => 99,
            'title' => 'Alias event',
            'start' => '2026-05-08 09:00',
            'end' => '2026-05-08 11:00',
            'extendedprops' => [
                'titleHTML' => 'ignored',
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            ],
            'institutionid' => 5,
            'department' => 'Old dept',
            'semester' => 20261,
        ]);

        $this->assertSame(0, $mapped['id']);
        $this->assertSame(-1, $mapped['extendedProps']['bookingstatus']);
        $this->assertSame(0, $mapped['extendedProps']['semesterid']);
        $this->assertSame('', $mapped['extendedProps']['titlehtml']);
        $this->assertArrayNotHasKey('faculty', $mapped['extendedProps']);
    }
}
