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

/**
 * Tab row to jump to other pages within this plugin.
 * @package    mod_bookit
 * @copyright  2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 *             based on tool_lifecycle by Thomas Niedermaier Universität Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

use coding_exception;
use core\exception\moodle_exception;
use dml_exception;
use moodle_url;
use tabobject;

/**
 * Class to generate a tab row for navigation within this plugin
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tabs {
    /**
     * Generates a Moodle tabrow i.e. an array of tabs
     *
     * @return array of tabobjects
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_tabrow($context): array {
        // Calendar Settings.
        $targeturl = new moodle_url('/mod/bookit/admin/calendar.php', ['id' => 'calendar']);
        $tabrow[] = new tabobject('calendar', $targeturl,
                get_string('calendar', 'mod_bookit'));

        // Tab to the rooms page.
        $targeturl = new moodle_url('/mod/bookit/admin/rooms.php', ['id' => 'rooms']);
        $tabrow[] = new tabobject('rooms', $targeturl,
                get_string('rooms', 'mod_bookit'));

        // Tab to the weekplan page.
        $targeturl = new moodle_url('/mod/bookit/admin/weekplans.php', ['id' => 'weekplans']);
        $tabrow[] = new tabobject('weekplans', $targeturl,
                get_string('weekplans', 'mod_bookit'));

        // Tab to the institutions page.
        $targeturl = new moodle_url('/mod/bookit/admin/institutions.php', ['id' => 'institutions']);
        $tabrow[] = new tabobject('institutions', $targeturl,
                get_string('institutions', 'mod_bookit'));

        // Tab to the master checklist page.
        $targeturl = new moodle_url('/mod/bookit/admin/master_checklist.php', ['id' => 'master_checklist']);
        $tabrow[] = new tabobject('master_checklist', $targeturl,
                get_string('master_checklist', 'mod_bookit'));

        // Tab to the checklist settings page.
        $targeturl = new moodle_url('/mod/bookit/admin/checklist.php', ['id' => 'checklist']);
        $tabrow[] = new tabobject('checklist', $targeturl,
                get_string('checklist', 'mod_bookit'));

        // Real admin settings.
        if (has_capability('moodle/site:config', $context)) {
            $targeturl = new moodle_url('/admin/settings.php', ['section' => 'modsettingbookit']);
            $tabrow[] = new tabobject('modsettingbookit', $targeturl,
                    get_string('settings_general', 'mod_bookit'));
        }

        return $tabrow;
    }
}
