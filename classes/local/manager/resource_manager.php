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
 * Resource manager class.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\manager;

use dml_exception;

/**
 * Resource manager class.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resource_manager {
    /**
     * Get resources of event.
     * @param int $eventid
     * @return array
     * @throws dml_exception
     */
    public static function get_resources_of_event(int $eventid) {
        global $DB;
        $resources = $DB->get_records_sql('
            SELECT er.resourceid, er.amount, r.name, r.categoryid
            FROM {bookit_resource} r JOIN {bookit_event_resources} er ON er.resourceid = r.id
            WHERE er.eventid = :eventid', ['eventid' => $eventid]);
        return $resources;
    }

    /**
     * Get resources.
     *
     * @return array[]
     * @throws dml_exception
     */
    public static function get_resources(): array {
        global $DB;
        $records = $DB->get_records_sql(
            'SELECT r.id resource_id, r.name resource_name, r.description resource_desc, r.amount resource_amount,
                    c.id category_id, c.name category_name, c.description category_desc
                    FROM {bookit_resource} r LEFT JOIN {bookit_resource_categories} c ON c.id = r.categoryid'
        );
        $resources = [];
        foreach ($records as $record) {
            if (!isset($resources[$record->category_name])) {
                $resources[$record->category_name] = [
                        'category_id' => $record->category_id,
                        'category_desc' => $record->category_desc,
                        'resources' => [$record->resource_id => [
                            'name' => $record->resource_name,
                            'desc' => $record->resource_desc,
                            'amount' => $record->resource_amount,
                    ]],
                ];
            } else {
                $resources[$record->category_name]['resources'][$record->resource_id] = [
                    'name' => $record->resource_name,
                    'desc' => $record->resource_desc,
                    'amount' => $record->resource_amount,
                ];
            }
        }
        return $resources;
    }

        /**
     * Get list of rooms as [id => name].
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_rooms(): array {
        $rooms = [];
        $resources = self::get_resources();

        if (!empty($resources['Rooms']['resources'])) {
            foreach ($resources['Rooms']['resources'] as $rid => $r) {
                $rooms[$rid] = $r['name'];
            }
        }
        return $rooms;
    }
}
