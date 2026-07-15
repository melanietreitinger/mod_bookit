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
 * Tests for Request Workspace tab normalisation.
 *
 * @package    mod_bookit
 * @covers     \mod_bookit\local\tabs::normalize_workspace_tab
 */
final class tabs_test extends advanced_testcase {
    /**
     * Service team legacy and canonical slugs resolve to workspace tabs.
     *
     * @return void
     */
    public function test_normalize_workspace_tab_service_team(): void {
        $this->resetAfterTest();

        $this->assertSame('allrequests', tabs::normalize_workspace_tab('myevents', true));
        $this->assertSame('rejectedcancelled', tabs::normalize_workspace_tab('rejectedrequests', true));
        $this->assertSame('openrequests', tabs::normalize_workspace_tab('openrequests', true));
        $this->assertSame('allrequests', tabs::normalize_workspace_tab('unknown-tab', true));
    }

    /**
     * Participant tabs stay on participant slugs.
     *
     * @return void
     */
    public function test_normalize_workspace_tab_participant(): void {
        $this->resetAfterTest();

        $this->assertSame('myevents', tabs::normalize_workspace_tab('myevents', false));
        $this->assertSame('history', tabs::normalize_workspace_tab('history', false));
        $this->assertSame('myevents', tabs::normalize_workspace_tab('allrequests', false));
    }
}
