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
 * Unit tests for overview sort preference parsing.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\overview;

use advanced_testcase;
use mod_bookit\local\manager\event_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for overview sort preference helpers.
 *
 * @covers \mod_bookit\local\manager\event_manager::parse_overview_sort_preference
 */
final class overview_sort_preference_test extends advanced_testcase {

    /**
     * Empty preference must fall back to starttime desc.
     *
     * @return void
     */
    public function test_parse_overview_sort_preference_defaults_when_empty(): void {
        $this->resetAfterTest();

        $this->assertSame(
            ['column' => 'starttime', 'direction' => 'desc'],
            event_manager::parse_overview_sort_preference(null)
        );
        $this->assertSame(
            ['column' => 'starttime', 'direction' => 'desc'],
            event_manager::parse_overview_sort_preference('')
        );
    }

    /**
     * Malformed or unknown columns must fall back to default.
     *
     * @return void
     */
    public function test_parse_overview_sort_preference_rejects_invalid_column(): void {
        $this->resetAfterTest();

        $this->assertSame(
            ['column' => 'starttime', 'direction' => 'desc'],
            event_manager::parse_overview_sort_preference('not-json')
        );
        $this->assertSame(
            ['column' => 'starttime', 'direction' => 'desc'],
            event_manager::parse_overview_sort_preference('{"column":"progress","direction":"asc"}')
        );
        $this->assertSame(
            ['column' => 'starttime', 'direction' => 'desc'],
            event_manager::parse_overview_sort_preference('{"column":"title","direction":"sideways"}')
        );
    }

    /**
     * Valid preference JSON must be accepted.
     *
     * @return void
     */
    public function test_parse_overview_sort_preference_accepts_title_asc(): void {
        $this->resetAfterTest();

        $this->assertSame(
            ['column' => 'title', 'direction' => 'asc'],
            event_manager::parse_overview_sort_preference('{"column":"title","direction":"asc"}')
        );
    }
}
