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
 * Data generator for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\entity\resource\bookit_resource;
use mod_bookit\local\entity\resource\bookit_resource_category;
use mod_bookit\local\manager\resource_manager;

/**
 * Data generator for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_bookit_generator extends testing_module_generator {
    /**
     * Create a new event.
     * @param array $event
     * @return void
     * @throws dml_exception
     */
    final public function create_event(array $event) {
        global $DB;

        $userid = 2; // Default to admin.
        if (!empty($event['username'])) {
            $user = $DB->get_record('user', ['username' => $event['username']], 'id', MUST_EXIST);
            $userid = $user->id;
        }

        $e = new bookit_event(
            0,
            $event['name'],
            20241,
            $event['institution'],
            strtotime($event['startdate']),
            strtotime($event['enddate']),
            90,
            1,
            rand(20, 250),
            1,
            '',
            $event['bookingstatus'],
            2,
            '',
            0,
            'External lorem ipsum',
            'Internal Lorem Ipsum dolor...',
            'Susi Support',
            15,
            15,
            null,
            $userid,
            time(),
            time(),
            [
            ],
        );

        $e->save($userid);
    }

    /**
     * Create a room for testing.
     *
     * @param array $room Room data (name, shortname, eventcolor, active)
     * @return int Inserted room ID
     */
    final public function create_room(array $room): int {
        global $DB;

        $record = new \stdClass();
        $record->name = $room['name'];
        $record->shortname = $room['shortname'] ?? substr($room['name'], 0, 10);
        $record->description = $room['description'] ?? '';
        $record->location = $room['location'] ?? '';
        $record->eventcolor = $room['eventcolor'] ?? '#3a87ad';
        $record->active = isset($room['active']) ? (int)(bool)$room['active'] : 1;
        $record->roommode = $room['roommode'] ?? 0;
        $record->seats = $room['seats'] ?? 10;
        $record->extratimebefore = 0;
        $record->extratimeafter = 0;
        $record->overlapping = 0;
        $record->usermodified = 2;
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('bookit_room', $record);
    }

    /**
     * Create a resource category for testing.
     *
     * @param array $category Category data (name)
     * @return int Inserted category ID
     */
    final public function create_resource_category(array $category): int {
        $cat = new bookit_resource_category(null, $category['name'], $category['description'] ?? null, 0, true, 0, 0, 2);
        return resource_manager::save_category($cat, 2);
    }

    /**
     * Create a resource for testing.
     *
     * Accepts a "rooms" column with comma-separated room names (resolved to IDs).
     * Leave "rooms" empty or omit to create an "all rooms" resource (null roomids).
     *
     * @param array $resource Resource data (name, category_name, rooms, amount, active)
     * @return int Inserted resource ID
     */
    final public function create_resource(array $resource): int {
        global $DB;

        // Resolve category by name.
        $catname = $resource['category_name'] ?? ($resource['category'] ?? null);
        if ($catname) {
            $catrec = $DB->get_record_sql(
                "SELECT id FROM {bookit_resource_category} WHERE "
                . $DB->sql_compare_text('name') . " = " . $DB->sql_compare_text(':name'),
                ['name' => $catname],
                MUST_EXIST
            );
            $categoryid = $catrec->id;
        } else {
            throw new \coding_exception('Generator: resource requires category_name');
        }

        // Resolve rooms (comma-separated names) to IDs.
        $roomids = null;
        if (!empty($resource['rooms'])) {
            $names = array_filter(array_map('trim', explode(',', $resource['rooms'])));
            $roomids = [];
            foreach ($names as $rname) {
                $room = $DB->get_record_sql(
                    "SELECT id FROM {bookit_room} WHERE "
                    . $DB->sql_compare_text('name') . " = " . $DB->sql_compare_text(':name'),
                    ['name' => $rname],
                    MUST_EXIST
                );
                $roomids[] = (int)$room->id;
            }
        }

        $res = new bookit_resource(
            null,
            $resource['name'],
            $resource['description'] ?? null,
            $categoryid,
            (int)($resource['amount'] ?? 1),
            isset($resource['amountirrelevant']) ? (bool)$resource['amountirrelevant'] : false,
            0,
            isset($resource['active']) ? (bool)$resource['active'] : true,
            $roomids,
            0,
            0,
            2
        );
        return resource_manager::save_resource($res, 2);
    }
}
