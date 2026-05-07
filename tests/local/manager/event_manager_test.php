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
 * Unit tests for event_manager reporting helpers.
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
 * Unit tests for event_manager helper methods.
 *
 * @covers \mod_bookit\local\manager\event_manager
 */
final class event_manager_test extends advanced_testcase {
    /**
     * Winter terms must bridge the year boundary correctly.
     *
     * @return void
     */
    public function test_get_current_semester_handles_winter_bridge(): void {
        $this->assertSame(20252, event_manager::get_current_semester(strtotime('2026-01-15 12:00:00')));
        $this->assertSame(20261, event_manager::get_current_semester(strtotime('2026-05-15 12:00:00')));
        $this->assertSame(20262, event_manager::get_current_semester(strtotime('2026-11-15 12:00:00')));
    }

    /**
     * Reporting defaults must span the full current year.
     *
     * @return void
     */
    public function test_get_reporting_default_range_returns_full_year(): void {
        [$start, $end] = event_manager::get_reporting_default_range(strtotime('2026-05-07 10:00:00'));

        $this->assertSame(strtotime('2026-01-01 00:00:00'), $start);
        $this->assertSame(strtotime('2026-12-31 23:59:59'), $end);
    }
}
