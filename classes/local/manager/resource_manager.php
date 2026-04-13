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
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\manager;

use dml_exception;
use mod_bookit\local\entity\resource\bookit_event_resource;
use mod_bookit\local\entity\resource\bookit_resource;
use mod_bookit\local\entity\resource\bookit_resource_category;
use mod_bookit\local\manager\resource_settings_manager;

/**
 * Resource manager class.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resource_manager {
    /**
     * Get resources of event.
     *
     * @param int $eventid Event ID
     * @return bookit_event_resource[] Array keyed by resourceid
     * @throws dml_exception
     */
    public static function get_resources_of_event(int $eventid): array {
        global $DB;
        $records = $DB->get_records_sql('
            SELECT er.id, er.eventid, er.resourceid, er.amount, er.status,
                   er.usermodified, er.timecreated, er.timemodified
            FROM {bookit_event_resource} er
            WHERE er.eventid = :eventid', ['eventid' => $eventid]);

        $resources = [];
        foreach ($records as $record) {
            $resources[(int)$record->resourceid] = bookit_event_resource::from_record($record);
        }
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
                    FROM {bookit_resource} r LEFT JOIN {bookit_resource_category} c ON c.id = r.categoryid'
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
        global $DB;

        $rooms = [];
        $records = $DB->get_records('bookit_room', null, 'name ASC', 'id, name');
        foreach ($records as $r) {
            $rooms[(int)$r->id] = $r->name;
        }
        if (!empty($rooms)) {
            return $rooms;
        }

        // Fallback: legacy rooms stored as resources.
        $resources = self::get_resources();
        if (!empty($resources['Rooms']['resources'])) {
            foreach ($resources['Rooms']['resources'] as $rid => $r) {
                $rooms[$rid] = $r['name'];
            }
        }
        return $rooms;
    }

    /**
     * Get room names for a resource, resolved from its roomids.
     *
     * @param bookit_resource $resource
     * @return string[] Array of room names
     * @throws dml_exception
     */
    public static function get_room_names_for_resource(bookit_resource $resource): array {
        $allrooms = self::get_rooms();
        $roomids = $resource->get_roomids() ?? [];
        return array_values(array_filter(array_map(fn($rid) => $allrooms[$rid] ?? null, $roomids)));
    }

    // Category CRUD Methods.

    /**
     * Get all resource categories.
     *
     * @return array Array of bookit_resource_category objects
     * @throws dml_exception
     */
    public static function get_all_categories(): array {
        global $DB;

        $records = $DB->get_records('bookit_resource_category', [], 'sortorder ASC');

        $categories = [];
        foreach ($records as $record) {
            $categories[] = self::category_from_record($record);
        }

        return $categories;
    }

    /**
     * Get a single category by ID.
     *
     * @param int $id Category ID
     * @return bookit_resource_category|null Category object or null if not found
     * @throws dml_exception
     */
    public static function get_category(int $id): ?bookit_resource_category {
        global $DB;

        $record = $DB->get_record('bookit_resource_category', ['id' => $id]);

        if (!$record) {
            return null;
        }

        return self::category_from_record($record);
    }

    /**
     * Save a category (insert or update).
     *
     * @param bookit_resource_category $category Category to save
     * @param int $userid User performing the action
     * @return int Category ID
     * @throws \moodle_exception If validation fails
     * @throws dml_exception
     */
    public static function save_category(bookit_resource_category $category, int $userid): int {
        global $DB;

        self::validate_category($category);

        $record = new \stdClass();
        $record->name = $category->get_name();
        $record->description = $category->get_description();
        $record->sortorder = $category->get_sortorder();
        $record->usermodified = $userid;

        if ($category->get_id() === null) {
            // Insert new category: append at end by assigning max sortorder + 1.
            $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {bookit_resource_category}');
            $record->sortorder = ($maxsort !== null && $maxsort !== false) ? (int)$maxsort + 1 : 1;
            $record->timecreated = time();
            $record->timemodified = time();
            $id = $DB->insert_record('bookit_resource_category', $record);
        } else {
            // Update existing category.
            $record->id = $category->get_id();
            $record->timemodified = time();
            $DB->update_record('bookit_resource_category', $record);
            $id = $record->id;
        }

        return $id;
    }

    /**
     * Delete a category.
     *
     * @param int $id Category ID
     * @return void
     * @throws \moodle_exception If category has resources
     * @throws dml_exception
     */
    public static function delete_category(int $id): void {
        global $DB;

        if ($DB->record_exists('bookit_resource', ['categoryid' => $id])) {
            throw new \moodle_exception('resources:category_has_resources', 'mod_bookit');
        }

        $DB->delete_records('bookit_resource_category', ['id' => $id]);
    }

    /**
     * Update category sort order.
     *
     * @param array|int $categoryids Array of category IDs in desired order (key = sortorder, value = categoryid)
     *                               or single category ID
     * @param int|null $sortorder If $categoryids is int, this is the new sortorder value
     * @return void
     * @throws dml_exception
     */
    public static function update_category_sortorder($categoryids, ?int $sortorder = null): void {
        global $DB;

        // Handle single ID + sortorder.
        if (is_int($categoryids) && $sortorder !== null) {
            $DB->set_field('bookit_resource_category', 'sortorder', $sortorder, ['id' => $categoryids]);
            return;
        }

        // Handle array.
        if (is_array($categoryids)) {
            foreach ($categoryids as $sort => $categoryid) {
                $DB->set_field('bookit_resource_category', 'sortorder', $sort, ['id' => $categoryid]);
            }
        }
    }

    // Resource CRUD Methods.

    /**
     * Get all resources.
     *
     * @param int|null $categoryid Optional filter by category
     * @param bool $activeonly Filter only active resources
     * @return array Array of bookit_resource objects
     * @throws dml_exception
     */
    public static function get_all_resources(?int $categoryid = null, bool $activeonly = false): array {
        global $DB;

        $conditions = [];
        if ($categoryid !== null) {
            $conditions['categoryid'] = $categoryid;
        }
        if ($activeonly) {
            $conditions['active'] = 1;
        }

        $records = $DB->get_records('bookit_resource', $conditions, 'sortorder ASC, name ASC');

        $resources = [];
        foreach ($records as $record) {
            $resources[] = self::resource_from_record($record);
        }

        return $resources;
    }

    /**
     * Get a single resource by ID.
     *
     * @param int $id Resource ID
     * @return bookit_resource|null Resource object or null if not found
     * @throws dml_exception
     */
    public static function get_resource_by_id(int $id): ?bookit_resource {
        global $DB;

        $record = $DB->get_record('bookit_resource', ['id' => $id]);

        if (!$record) {
            return null;
        }

        return self::resource_from_record($record);
    }

    /**
     * Save a resource (insert or update).
     *
     * @param bookit_resource $resource Resource to save
     * @param int $userid User performing the action
     * @return int Resource ID
     * @throws \moodle_exception If validation fails
     * @throws dml_exception
     */
    public static function save_resource(bookit_resource $resource, int $userid): int {
        global $DB;

        self::validate_resource($resource);

        $record = new \stdClass();
        $record->name = $resource->get_name();
        $record->description = $resource->get_description();
        $record->categoryid = $resource->get_categoryid();
        $record->amount = $resource->get_amount();
        $record->amountirrelevant = $resource->is_amountirrelevant() ? 1 : 0;
        $record->sortorder = $resource->get_sortorder();
        $record->active = $resource->is_active() ? 1 : 0;
        $record->roomids = ($resource->get_roomids() !== null) ? json_encode($resource->get_roomids()) : null;
        $record->internalinfo = $resource->get_internalinfo();
        $record->usermodified = $userid;

        if ($resource->get_id() === null) {
            // Insert new resource: append at end within the category.
            $maxsort = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {bookit_resource} WHERE categoryid = ?',
                [$resource->get_categoryid()]
            );
            $record->sortorder = ($maxsort !== null && $maxsort !== false) ? (int)$maxsort + 1 : 1;
            $record->timecreated = time();
            $record->timemodified = time();
            $id = $DB->insert_record('bookit_resource', $record);
            // Auto-generate checklist entry for new resource.
            resource_settings_manager::create_checklist_for_resource($id, $userid);
        } else {
            // Update existing resource.
            $record->id = $resource->get_id();
            $record->timemodified = time();
            $DB->update_record('bookit_resource', $record);
            $id = $record->id;
        }

        return $id;
    }

    /**
     * Delete a resource.
     *
     * @param int $id Resource ID
     * @return void
     * @throws dml_exception
     */
    public static function delete_resource(int $id): void {
        global $DB;

        resource_settings_manager::delete_checklist_item_by_resource($id);
        $DB->delete_records('bookit_event_resource', ['resourceid' => $id]);
        $DB->delete_records('bookit_resource', ['id' => $id]);
    }

    /**
     * Update resource sort order.
     *
     * @param array|int $resourceids Array of resource IDs in desired order (key = sortorder, value = resourceid)
     *                               or single resource ID
     * @param int $categoryid Category ID for validation (required if array) or sortorder value (if single ID)
     * @param int|null $sortorder If $resourceids is int, this is the new sortorder value
     * @return void
     * @throws dml_exception
     */
    public static function update_resource_sortorder($resourceids, int $categoryid, ?int $sortorder = null): void {
        global $DB;

        // Handle single ID + sortorder.
        if (is_int($resourceids) && $sortorder !== null) {
            $DB->set_field('bookit_resource', 'sortorder', $sortorder, ['id' => $resourceids]);
            return;
        }

        // Handle array.
        if (is_array($resourceids)) {
            foreach ($resourceids as $sort => $resourceid) {
                $DB->set_field('bookit_resource', 'sortorder', $sort, ['id' => $resourceid, 'categoryid' => $categoryid]);
            }
        }
    }

    // Helper Methods.

    /**
     * Create category entity from database record.
     *
     * @param \stdClass $record Database record
     * @return bookit_resource_category Category entity
     */
    private static function category_from_record(\stdClass $record): bookit_resource_category {
        return new bookit_resource_category(
            isset($record->id) ? (int)$record->id : null,
            $record->name ?? '',
            $record->description ?? null,
            (int)($record->sortorder ?? 0),
            (bool)($record->active ?? 1),
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0),
            (int)($record->usermodified ?? 0)
        );
    }

    /**
     * Create resource entity from database record.
     *
     * @param \stdClass $record Database record
     * @return bookit_resource Resource entity
     */
    private static function resource_from_record(\stdClass $record): bookit_resource {
        $roomids = null;
        if (isset($record->roomids) && $record->roomids !== null) {
            $decoded = json_decode($record->roomids, true);
            $roomids = is_array($decoded) ? $decoded : null;
        }

        return new bookit_resource(
            isset($record->id) ? (int)$record->id : null,
            $record->name ?? '',
            $record->description ?? null,
            (int)($record->categoryid ?? 0),
            (int)($record->amount ?? 0),
            (bool)($record->amountirrelevant ?? 0),
            (int)($record->sortorder ?? 0),
            (bool)($record->active ?? 1),
            $roomids,
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0),
            (int)($record->usermodified ?? 0)
        );
    }

    // Validation Methods.

    /**
     * Validate category data.
     *
     * @param bookit_resource_category $category Category to validate
     * @return void
     * @throws \moodle_exception If validation fails
     */
    private static function validate_category(bookit_resource_category $category): void {
        if (empty(trim($category->get_name()))) {
            throw new \moodle_exception('category_name_required', 'mod_bookit');
        }

        if ($category->get_sortorder() < 0) {
            throw new \moodle_exception('sortorder_must_be_positive', 'mod_bookit');
        }

        // Check for duplicate category name.
        global $DB;
        $params = ['name' => $category->get_name()];
        if ($category->get_id() !== null) {
            // Exclude current category when editing.
            $sql = "SELECT id FROM {bookit_resource_category} WHERE "
                . $DB->sql_compare_text('name') . " = " . $DB->sql_compare_text(':name')
                . " AND id != :id";
            $params['id'] = $category->get_id();
        } else {
            $sql = "SELECT id FROM {bookit_resource_category} WHERE "
                . $DB->sql_compare_text('name') . " = " . $DB->sql_compare_text(':name');
        }

        if ($DB->record_exists_sql($sql, $params)) {
            throw new \moodle_exception('error_category_name_exists', 'mod_bookit');
        }
    }

    /**
     * Validate resource data.
     *
     * @param bookit_resource $resource Resource to validate
     * @return void
     * @throws \moodle_exception If validation fails
     * @throws dml_exception
     */
    private static function validate_resource(bookit_resource $resource): void {
        global $DB;

        if (empty(trim($resource->get_name()))) {
            throw new \moodle_exception('resources:name_required', 'mod_bookit');
        }

        if ($resource->get_categoryid() <= 0) {
            throw new \moodle_exception('resources:category_required', 'mod_bookit');
        }

        // Check if category exists.
        if (!$DB->record_exists('bookit_resource_category', ['id' => $resource->get_categoryid()])) {
            throw new \moodle_exception('resources:category_not_found', 'mod_bookit');
        }

        if (!$resource->is_amountirrelevant() && $resource->get_amount() <= 0) {
            throw new \moodle_exception('resources:amount_must_be_positive', 'mod_bookit');
        }

        if ($resource->get_sortorder() < 0) {
            throw new \moodle_exception('sortorder_must_be_positive', 'mod_bookit');
        }
    }

    // Booking Form Helper Methods.

    /**
     * Get active resources grouped by category for booking form.
     *
     * @return array Structured data for template [
     *     [
     *         'category' => ['id' => int, 'name' => string, 'sortorder' => int],
     *         'resources' => [
     *             ['id' => int, 'name' => string, 'description' => string, 'amount' => int,
     *              'amountirrelevant' => bool, 'sortorder' => int, 'roomids' => string|null],
     *             ...
     *         ]
     *     ],
     *     ...
     * ]
     * roomids is a JSON-encoded array of room IDs, or null/empty if available in all rooms.
     * @throws dml_exception
     */
    public static function get_active_resources_grouped(): array {
        global $DB;

        $sql = "
            SELECT
                r.id as resource_id,
                r.name as resource_name,
                r.description as resource_description,
                r.amount as resource_amount,
                r.amountirrelevant as resource_amountirrelevant,
                r.sortorder as resource_sortorder,
                r.roomids as resource_roomids,
                c.id as category_id,
                c.name as category_name,
                c.sortorder as category_sortorder
            FROM {bookit_resource} r
            JOIN {bookit_resource_category} c ON c.id = r.categoryid
            WHERE r.active = 1
            ORDER BY c.sortorder ASC, r.sortorder ASC
        ";

        $records = $DB->get_records_sql($sql);

        $grouped = [];
        foreach ($records as $record) {
            $catid = $record->category_id;

            if (!isset($grouped[$catid])) {
                $grouped[$catid] = [
                    'category' => [
                        'id' => $catid,
                        'name' => $record->category_name,
                        'sortorder' => $record->category_sortorder,
                    ],
                    'resources' => [],
                ];
            }

            $grouped[$catid]['resources'][] = [
                'id' => $record->resource_id,
                'name' => $record->resource_name,
                'description' => $record->resource_description,
                'amount' => $record->resource_amount,
                'amountirrelevant' => (bool)$record->resource_amountirrelevant,
                'sortorder' => $record->resource_sortorder,
                'roomids' => $record->resource_roomids,
            ];
        }

        return array_values($grouped);
    }

    /**
     * Get room details for each resource (for room icon display).
     *
     * Returns mapping from resource ID to array of room objects with id, name, shortname, color.
     * Uses the roomids JSON field from bookit_resource table.
     *
     * @return array [resource_id => [['id' => int, 'name' => string, 'shortname' => string, 'color' => string], ...], ...]
     * @throws dml_exception
     */
    public static function get_resource_rooms(): array {
        global $DB;

        $resources = $DB->get_records('bookit_resource', ['active' => 1], '', 'id, roomids');

        // Get all active rooms.
        $rooms = $DB->get_records('bookit_room', ['active' => 1], '', 'id, name, shortname, eventcolor');
        $roomsbyid = [];
        foreach ($rooms as $room) {
            $roomsbyid[$room->id] = [
                'id' => (int)$room->id,
                'name' => $room->name,
                'shortname' => $room->shortname,
                'color' => $room->eventcolor,
            ];
        }

        $map = [];
        foreach ($resources as $resource) {
            $map[$resource->id] = [];

            if (empty($resource->roomids)) {
                continue;
            }

            $roomids = json_decode($resource->roomids, true);
            if (!is_array($roomids)) {
                continue;
            }

            foreach ($roomids as $roomid) {
                if (isset($roomsbyid[$roomid])) {
                    $map[$resource->id][] = $roomsbyid[$roomid];
                }
            }
        }

        return $map;
    }
}
