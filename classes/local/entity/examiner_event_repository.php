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

/**
 * Repository for fetching events for examiners.
 *
 * @package   mod_bookit
 */
class examiner_event_repository {

    /**
     * Returns all events created or owned by the examiner (personinchargeid = $userid).
     *
     * @param int $userid
     * @return array
     */
    public static function get_events_for_examiner(int $userid): array {
        global $DB;

        $sql = "SELECT e.id,
                       e.name,
                       e.bookingstatus,
                       e.starttime,
                       r.name AS room
                  FROM {bookit_event} e
             LEFT JOIN {bookit_event_resources} er ON er.eventid = e.id
             LEFT JOIN {bookit_resource}        r  ON r.id       = er.resourceid
                 WHERE e.personinchargeid = ?
              GROUP BY e.id";

        return $DB->get_records_sql($sql, [$userid]);
    }
}
