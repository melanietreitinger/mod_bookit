<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_bookit\local;

use advanced_testcase;

/**
 * Tests for overview tab validation helpers.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_bookit\local\tabs
 */
final class tabs_test extends advanced_testcase {
    /**
     * Request Workspace viewers accept canonical workspace slugs only.
     *
     * @return void
     */
    public function test_validate_overview_tab_workspace_viewer(): void {
        $this->resetAfterTest();

        foreach (tabs::WORKSPACE_TABS as $slug) {
            $this->assertSame($slug, tabs::validate_overview_tab($slug, true));
        }
    }

    /**
     * Participants accept canonical participant slugs only.
     *
     * @return void
     */
    public function test_validate_overview_tab_participant(): void {
        $this->resetAfterTest();

        $this->assertSame('myevents', tabs::validate_overview_tab('myevents', false));
        $this->assertSame('history', tabs::validate_overview_tab('history', false));
    }

    /**
     * Role-aware defaults apply when the tab parameter is omitted.
     *
     * @return void
     */
    public function test_get_default_overview_tab(): void {
        $this->resetAfterTest();

        $this->assertSame('allrequests', tabs::get_default_overview_tab(true));
        $this->assertSame('myevents', tabs::get_default_overview_tab(false));
    }

    /**
     * External queue reads accept canonical workspace slugs only.
     *
     * @return void
     */
    public function test_validate_workspace_tab(): void {
        $this->resetAfterTest();

        foreach (tabs::WORKSPACE_TABS as $slug) {
            $this->assertSame($slug, tabs::validate_workspace_tab($slug));
        }
    }
}
