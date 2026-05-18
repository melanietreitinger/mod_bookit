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
 * Unit tests for the third-pass install helper baseline.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

use advanced_testcase;
use mod_bookit\local\manager\weekplan_manager;

/**
 * Unit tests for install_helper.
 *
 * @covers \mod_bookit\local\install_helper
 */
final class install_helper_test extends advanced_testcase {
    /**
     * Fresh-install setup must create the agreed one-room/one-weekplan/one-institution baseline.
     *
     * @return void
     */
    public function test_ensure_fresh_install_baseline_creates_expected_records(): void {
        global $DB;

        $this->resetAfterTest(true);

        $report = install_helper::ensure_fresh_install_baseline();

        $this->assertSame('success', $report['status']);
        $this->assertSame(1, $DB->count_records('bookit_room'));
        $this->assertSame(1, $DB->count_records('bookit_weekplan'));
        $this->assertSame(1, $DB->count_records('bookit_institution'));

        $room = $DB->get_record('bookit_room', [], '*', MUST_EXIST);
        $weekplan = $DB->get_record('bookit_weekplan', [], '*', MUST_EXIST);
        $institution = $DB->get_record('bookit_institution', [], '*', MUST_EXIST);

        $this->assertSame(install_helper::DEFAULT_ROOM_NAME, $room->name);
        $this->assertLessThanOrEqual(6, strlen((string)$room->shortname));
        $this->assertSame(install_helper::DEFAULT_WEEKPLAN_NAME, $weekplan->name);
        $this->assertSame(install_helper::DEFAULT_INSTITUTION_NAME, $institution->name);
        [, $expectedslots] = weekplan_manager::parse_weekplan(install_helper::DEFAULT_WEEKPLAN_SCHEDULE);
        $actualslots = array_values($DB->get_records('bookit_weekplanslot', ['weekplanid' => (int)$weekplan->id], 'starttime ASC'));
        $this->assertCount(count($expectedslots), $actualslots);
        foreach ($expectedslots as $index => [$expectedstart, $expectedend]) {
            $this->assertSame($expectedstart, (int)$actualslots[$index]->starttime);
            $this->assertSame($expectedend, (int)$actualslots[$index]->endtime);
        }
        $this->assertTrue($DB->record_exists('bookit_weekplan_room', [
            'weekplanid' => $weekplan->id,
            'roomid' => $room->id,
        ]));
    }

    /**
     * Fresh-install setup must become idempotent after the baseline exists.
     *
     * @return void
     */
    public function test_ensure_fresh_install_baseline_is_idempotent(): void {
        $this->resetAfterTest(true);

        install_helper::ensure_fresh_install_baseline();
        $second = install_helper::ensure_fresh_install_baseline();

        $this->assertSame('idempotent', $second['status']);
        $this->assertSame('idempotent', $second['operations']['institution']['status']);
        $this->assertSame('idempotent', $second['operations']['room']['status']);
        $this->assertSame('idempotent', $second['operations']['weekplan']['status']);
    }

    /**
     * Role preset import must report success on first run and idempotence afterwards.
     *
     * @return void
     */
    public function test_import_default_roles_with_report_tracks_outcomes(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = install_helper::import_default_roles_with_report();
        $this->assertSame('success', $first['status']);
        $this->assertCount(5, $first['imported']);

        foreach ($first['imported'] as $shortname) {
            $this->assertTrue($DB->record_exists('role', ['shortname' => $shortname]));
        }

        $second = install_helper::import_default_roles_with_report();
        $this->assertSame('idempotent', $second['status']);
        $this->assertSame([], $second['imported']);
        $this->assertCount(5, $second['skipped']);
    }
}
