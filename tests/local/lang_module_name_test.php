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

namespace mod_bookit\local;

use advanced_testcase;

/**
 * Module display name language strings (Spec 073).
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class lang_module_name_test extends advanced_testcase {
    /**
     * Load plugin language strings from the on-disk lang file.
     *
     * @param string $lang Language code (en, de, …).
     * @return array<string, string>
     */
    private function get_lang_strings(string $lang): array {
        global $CFG;

        $string = [];
        require $CFG->dirroot . '/mod/bookit/lang/' . $lang . '/bookit.php';

        return $string;
    }

    /**
     * English module type label is Calendar.
     *
     * @return void
     */
    public function test_modulename_english_is_calendar(): void {
        $this->resetAfterTest();

        $strings = $this->get_lang_strings('en');
        $this->assertSame('Calendar', $strings['modulename']);
        $this->assertSame('Calendar activities', $strings['modulenameplural']);
        $this->assertStringNotContainsString('BookIt', $strings['modulename_help']);
    }

    /**
     * German module type label is Kalender.
     *
     * @return void
     */
    public function test_modulename_german_is_kalender(): void {
        $this->resetAfterTest();

        $strings = $this->get_lang_strings('de');
        $this->assertSame('Kalender', $strings['modulename']);
        $this->assertSame('Kalender-Aktivitäten', $strings['modulenameplural']);
        $this->assertStringNotContainsString('BookIt', $strings['modulename_help']);
    }
}
