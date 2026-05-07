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
 * Unit tests for get_possible_starttimes past-date helpers.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

use advanced_testcase;

/**
 * Unit tests for get_possible_starttimes.
 *
 * @covers \mod_bookit\external\get_possible_starttimes
 */
final class get_possible_starttimes_test extends advanced_testcase {
    /**
     * Past timestamps must be rejected while present and future ones stay valid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_is_starttime_in_past_uses_reference_time(): void {
        $referencetime = 1715000000;

        $this->assertTrue(get_possible_starttimes::is_starttime_in_past($referencetime - 1, $referencetime));
        $this->assertFalse(get_possible_starttimes::is_starttime_in_past($referencetime, $referencetime));
        $this->assertFalse(get_possible_starttimes::is_starttime_in_past($referencetime + 3600, $referencetime));
    }
}
