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
 * Database class for bookit_events.
 *
 * @package     mod_bookit
 * @copyright   2025 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\entity;

defined('MOODLE_INTERNAL') || die();

class bookit_event_collection {

    /**
     * Returns distinct departments (faculties) from bookit_event.
     * @return array
     */
    public static function get_faculties(): array {
        global $DB;
        return $DB->get_fieldset_sql("
            SELECT DISTINCT department
              FROM {bookit_event}
             WHERE department <> ''
          ORDER BY department
        ");
    }

    /**
     * Returns room list from resource manager.
     * @return array [id => name]
     */
    public static function get_rooms(): array {
        $rooms = [];
        $resources = \mod_bookit\local\manager\resource_manager::get_resources();
        foreach ($resources['Rooms']['resources'] ?? [] as $rid => $r) {
            $rooms[$rid] = $r['name'];
        }
        return $rooms;
    }
}
