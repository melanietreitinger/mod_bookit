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
 * Checklist manager class.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\manager;

use dml_exception;
use mod_bookit\local\entity\bookit_checklist_master;
use mod_bookit\local\entity\bookit_checklist_category;
use mod_bookit\local\entity\bookit_checklist_item;
use mod_bookit\local\entity\bookit_notification_slot;

/**
 * Checklist manager class.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checklist_manager {

    /**
     * Get the default checklist master.
     *
     * @return bookit_checklist_master|null
     * @throws dml_exception
     */
    public static function get_default_master(): ?bookit_checklist_master {
        global $DB;
        $record = $DB->get_record("bookit_checklist_master", ["isdefault" => 1], '*', IGNORE_MULTIPLE);
        if (!$record) {
            return null;
        }
        return bookit_checklist_master::from_database($record->id);
    }

    /**
     * Get checklist categories from database.
     *
     * @param array $categories Array of category IDs to fetch.
     * @return array Array of bookit_checklist_category objects.
     * @throws dml_exception
     */
    public static function get_categories_by_ids(array $categories = []): array {
        global $DB;

        list($in_sql, $params) = $DB->get_in_or_equal($categories);
        $sql = "SELECT * FROM {bookit_checklist_category} WHERE id $in_sql";
        $records = $DB->get_records_sql($sql, $params);

        return array_map(fn($record) => bookit_checklist_category::from_record($record), $records);
    }


    public static function get_categories_by_master_id(int $masterid): array {
        global $DB;

        $sql = "SELECT * FROM {bookit_checklist_category} WHERE masterid = :masterid";
        $params = ['masterid' => $masterid];
        $records = $DB->get_records_sql($sql, $params);

        return array_map(fn($record) => bookit_checklist_category::from_record($record), $records);
    }

    /**
     * Get checklist items by category ID.
     *
     * @param int $categoryid ID of the checklist category.
     * @return array Array of bookit_checklist_item objects.
     * @throws dml_exception
     */
    public static function get_items_by_category_id(int $categoryid): array {
        global $DB;

        $sql = "SELECT * FROM {bookit_checklist_item} WHERE categoryid = :categoryid";
        $params = ['categoryid' => $categoryid];
        $records = $DB->get_records_sql($sql, $params);

        return array_map(fn($record) => bookit_checklist_item::from_record($record), $records);
    }

    public static function get_bookit_roles() {

        $target_shortnames = ['bookit_bookingperson', 'bookit_examiner', 'bookit_observer', 'bookit_serviceteam', 'bookit_supportonside'];
        $roles = get_all_roles();

        $bookitroles = [];
        foreach ($roles as $role) {
            if (in_array($role->shortname, $target_shortnames)) {
                $bookitroles[] = $role;
            }
        }
        return $bookitroles;
    }

    public static function get_bookit_rooms() {

        $categories = categories_manager::get_categories();

        $roomsArray = array_filter($categories, fn($cat) => $cat['name'] === 'Rooms');
        $rooms = reset($roomsArray)['resources'];

        return $rooms;

    }

    public static function get_checklistitem_statename(int $state): string {

        $reflection = new \ReflectionClass(bookit_checklist_item::class);
        $constants = $reflection->getConstants();

        $constantname = array_search($state, $constants, true);

        if (!$constantname) {
            $constantname = array_search(0, $constants, true);
        }

        return get_string(strtolower($constantname), 'mod_bookit');

    }

    public static function get_notification_slot_type(int $type): string {

        $reflection = new \ReflectionClass(bookit_notification_slot::class);
        $constants = $reflection->getConstants();

        $constantname = array_search($type, $constants, true);

        if (!$constantname) {
            $constantname = array_search(0, $constants, true);
        }

        return get_string(strtolower($constantname), 'mod_bookit');

    }

    public static function get_roomname_by_id(int $roomid): string {
        $rooms = self::get_bookit_rooms();
        $roommatch = array_filter($rooms, fn($item) => $item['id'] == $roomid);
        if (!empty($roommatch)) {
            return reset($roommatch)['name'];
        }
        return '';
    }

    public static function get_rolename_by_id(int $roleid): string {
        $roles = self::get_bookit_roles();
        $rolematch = array_filter($roles, fn($item) => $item->id == $roleid);
        if (!empty($rolematch)) {
            return reset($rolematch)->name;
        }
        return '';
    }

}
