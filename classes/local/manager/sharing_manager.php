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
 * Sharing manager class for checklist import/export functionality.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

// global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');

use dml_exception;
use mod_bookit\local\entity\bookit_checklist_master;

/**
 * Sharing manager class for checklist import/export functionality.
 *
 * Provides functionality to export and import master checklists with their categories and items
 * in various formats (CSV, PDF).
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sharing_manager {

    /**
     * Export master checklist to CSV format.
     *
     * Exports the master checklist with all its categories and items to a CSV file.
     * The CSV structure includes hierarchical information with categories and their items.
     *
     * @param int $masterid The ID of the master checklist to export
     * @param string $filename Optional custom filename for the export
     * @return void Downloads the CSV file directly
     * @throws dml_exception
     */
    public static function export_master_checklist_csv(int $masterid, string $filename = ''): void {
        // Get the master checklist
        $master = bookit_checklist_master::from_database($masterid);

        if (empty($filename)) {
            // $filename = clean_filename($master->name . '_checklist_export');
            $filename = 'checklist_master_export';
        }

        // Initialize CSV writer.
        $csvwriter = new \csv_export_writer('comma', '"', 'application/download');
        $csvwriter->set_filename($filename, '.csv');

        // Add comprehensive CSV headers for all data.
        $headers = [
            'type',
            'level',
            'id',
            'parent_id',
            'name',
            'description',
            'sort_order',
            'is_default',
            'is_required',
            'item_type',
            'options',
            'default_value',
            'due_days_offset',
            'due_days_relation',
            'room_ids',
            'role_shortnames',
            'master_category_order',
            'checklist_items',
            'room_data',
        ];
        $csvwriter->add_data($headers);

        // First, export all rooms used in this checklist.
        $allrooms = checklist_manager::get_bookit_rooms();
        foreach ($allrooms as $room) {
            $roomdata = [
                'room',
                '3',
                'room_' . $room->id,
                '',
                $room->name,
                $room->description ?? '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                json_encode([
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description ?? '',
                    'eventcolor' => $room->eventcolor ?? '',
                    'textclass' => $room->textclass ?? '',
                    'capacity' => $room->capacity ?? 0,
                ]),
            ];
            $csvwriter->add_data($roomdata);
        }

        // Add master checklist information.
        $masterdata = [
            'master',
            '0',
            $master->id,
            '',
            $master->name,
            $master->description ?? '',
            '0',
            $master->isdefault ?? '0',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $master->mastercategoryorder ?? '',
            '',
            '',
        ];
        $csvwriter->add_data($masterdata);

        // Get and export categories with their items.
        $categories = checklist_manager::get_categories_by_master_id($masterid);

        foreach ($categories as $category) {

            // Add category row.
            $categorydata = [
                'category',
                '1',
                $category->id,
                $master->id,
                $category->name,
                $category->description ?? '',
                $category->sortorder,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $category->checklistitems ?? '',
                '',
            ];
            $csvwriter->add_data($categorydata);

            // Get items for this category.
            $items = checklist_manager::get_items_by_category_id($category->id);

            foreach ($items as $item) {

                // Convert room IDs to prefixed room IDs for CSV reference.
                $roomidscsv = [];
                if (!empty($item->roomids)) {
                    foreach ($item->roomids as $roomid) {
                        $roomidscsv[] = 'room_' . $roomid;
                    }
                }

                // Convert role IDs to role shortnames for portability.
                $roleshortnames = [];
                if (!empty($item->roleids)) {
                    foreach ($item->roleids as $roleid) {
                        $role = checklist_manager::get_role_by_id((int)$roleid);
                        if ($role && !empty($role->shortname)) {
                            $roleshortnames[] = $role->shortname;
                        }
                    }
                }

                // Add item row with all detailed information.
                $itemdata = [
                    'item',
                    '2',
                    $item->id,
                    $category->id,
                    $item->title,
                    $item->description ?? '',
                    $item->sortorder,
                    '',
                    $item->isrequired ?? '0',
                    $item->itemtype ?? '',
                    $item->options ?? '',
                    $item->defaultvalue ?? '',
                    $item->duedaysoffset ?? '',
                    $item->duedaysrelation ?? '',
                    json_encode($roomidscsv),
                    json_encode($roleshortnames),
                    '',
                    '',
                    '',
                ];
                $csvwriter->add_data($itemdata);
            }
        }

        $csvwriter->download_file();
    }

    /**
     * Import master checklist from CSV format.
     *
     * @param int $masterid The ID of the master checklist to import into
     * @param string $csvdata CSV data content
     * @return array Result array with success status and import details
     */
    public static function import_master_checklist_csv(int $masterid, string $csvdata, bool $importrooms = true): array {
        global $DB, $USER;

        if (empty($csvdata)) {
            return ['success' => false, 'message' => get_string('invalidcsvformat', 'mod_bookit')];
        }

        try {
            // Verify that the master checklist exists
            if (!$DB->record_exists('bookit_checklist_master', ['id' => $masterid])) {
                return ['success' => false, 'message' => "Master checklist with ID $masterid does not exist"];
            }

            // Parse CSV data
            $lines = str_getcsv($csvdata, "\n");
            if (count($lines) < 2) {
                return ['success' => false, 'message' => get_string('invalidcsvformat', 'mod_bookit')];
            }

            // Get headers
            $headers = str_getcsv($lines[0]);
            $requiredHeaders = ['type', 'name'];

            // Validate headers
            foreach ($requiredHeaders as $required) {
                if (!in_array($required, $headers)) {
                    return ['success' => false, 'message' => get_string('invalidcsvformat', 'mod_bookit')];
                }
            }

            // Get current max sort orders for proper positioning
            $maxCategorySortOrder = $DB->get_field_sql(
                "SELECT COALESCE(MAX(sortorder), 0) FROM {bookit_checklist_category} WHERE masterid = ?",
                [$masterid]
            );

            $importedCount = 0;
            $roomCache = [];
            $roomIdToNameMap = [];
            $categoriesData = [];

            // First pass: Process rooms and organize data by category
            $currentCategoryName = null;

            for ($i = 1; $i < count($lines); $i++) {
                $data = str_getcsv($lines[$i]);
                if (count($data) < count($headers)) {
                    continue;
                }

                $row = array_combine($headers, $data);

                // Process rooms first to build room cache (only if import_rooms is enabled)
                if ($importrooms && $row['type'] === 'room' && !empty($row['name']) && !empty($row['room_data'])) {
                    $roomData = json_decode($row['room_data'], true);
                    if ($roomData) {
                        $roomName = $row['name'];
                        $roomIdFromCsv = $roomData['id'] ?? null;

                        // Build mapping from CSV room ID to room name
                        if ($roomIdFromCsv) {
                            $roomIdToNameMap[$roomIdFromCsv] = $roomName;
                        }

                        $existingRoom = self::find_room_by_name($roomName);
                        if ($existingRoom) {
                            $roomCache[$roomName] = $existingRoom->id;
                        } else {
                            // Create new room if it doesn't exist
                            $newRoomId = self::create_room_from_data($roomData);
                            if ($newRoomId) {
                                $roomCache[$roomName] = $newRoomId;
                            }
                        }
                    }
                }

                // Track current category and collect items under it
                if ($row['type'] === 'category' && !empty($row['name'])) {
                    $currentCategoryName = $row['name'];
                    if (!isset($categoriesData[$currentCategoryName])) {
                        $categoriesData[$currentCategoryName] = [
                            'name' => $row['name'],
                            'description' => $row['description'] ?? '',
                            'checklist_items' => $row['checklist_items'] ?? '',
                            'items' => []
                        ];
                    }
                } elseif ($row['type'] === 'item' && !empty($row['name']) && $currentCategoryName) {
                    // Add item to current category
                    $categoriesData[$currentCategoryName]['items'][] = $row;
                }
            }

            // Second pass: Create categories and their items
            $categoryMapping = [];

            foreach ($categoriesData as $categoryName => $categoryInfo) {
                // Create category
                $category = new \mod_bookit\local\entity\bookit_checklist_category(
                    0,
                    $masterid,
                    $categoryInfo['name'],
                    $categoryInfo['description'],
                    null, // Will be updated later with item IDs
                    ++$maxCategorySortOrder,
                    $USER->id,
                    time(),
                    time()
                );

                $categoryId = $category->save();
                $categoryMapping[$categoryName] = $categoryId;
                $importedCount++;

                // Create items for this category
                $categoryItemIds = [];
                foreach ($categoryInfo['items'] as $itemRow) {
                    // Parse room IDs from CSV (only if import_rooms is enabled)
                    $roomIds = [];
                    if ($importrooms && !empty($itemRow['room_ids'])) {
                        $roomIdsFromCsv = json_decode($itemRow['room_ids'], true);
                        if (is_array($roomIdsFromCsv)) {
                            foreach ($roomIdsFromCsv as $roomRef) {
                                // Extract room ID from "room_X" format
                                if (preg_match('/room_(\d+)/', $roomRef, $matches)) {
                                    $csvRoomId = (int)$matches[1];
                                    // Find room name by CSV room ID
                                    if (isset($roomIdToNameMap[$csvRoomId])) {
                                        $roomName = $roomIdToNameMap[$csvRoomId];
                                        // Get actual database room ID by name
                                        if (isset($roomCache[$roomName])) {
                                            $roomIds[] = $roomCache[$roomName];
                                        } else {
                                            error_log("Room '$roomName' not found in roomCache");
                                        }
                                    } else {
                                        error_log("CSV room ID '$csvRoomId' not found in roomIdToNameMap");
                                    }
                                } else {
                                    error_log("Invalid room reference format: '$roomRef'");
                                }
                            }
                        }
                    }

                    // Debug logging
                    error_log("Item '{$itemRow['name']}' - Room IDs: " . json_encode($roomIds));

                    // Parse role IDs from CSV - check if roles exist
                    $roleIds = [];
                    if (!empty($itemRow['role_shortnames'])) {
                        $roleShortnames = json_decode($itemRow['role_shortnames'], true);
                        if (is_array($roleShortnames)) {
                            foreach ($roleShortnames as $shortname) {
                                $role = self::find_role_by_shortname($shortname);
                                if ($role) {
                                    $roleIds[] = $role->id;
                                }
                            }
                        }
                    }

                    // If no valid roles found, use role ID 0 as fallback
                    if (empty($roleIds)) {
                        $roleIds = [0];
                    }

                    // Get max sort order for items in this category
                    $maxItemSortOrder = $DB->get_field_sql(
                        "SELECT COALESCE(MAX(sortorder), 0) FROM {bookit_checklist_item} WHERE categoryid = ?",
                        [$categoryId]
                    );

                    // Properly cast CSV values to correct types
                    $itemType = !empty($itemRow['item_type']) ? (int)$itemRow['item_type'] : 1;
                    $sortOrder = !empty($itemRow['sort_order']) ? (int)$itemRow['sort_order'] : ++$maxItemSortOrder;
                    $isRequired = !empty($itemRow['is_required']) ? (int)$itemRow['is_required'] : 0;
                    $dueDaysOffset = !empty($itemRow['due_days_offset']) ? (int)$itemRow['due_days_offset'] : null;
                    $dueDaysRelation = !empty($itemRow['due_days_relation']) ? $itemRow['due_days_relation'] : null;
                    $defaultValue = !empty($itemRow['default_value']) ? $itemRow['default_value'] : null;
                    $options = !empty($itemRow['options']) ? $itemRow['options'] : null;

                    // Create new item with all attributes
                    $item = new \mod_bookit\local\entity\bookit_checklist_item(
                        0,                              // id
                        $masterid,                      // masterid
                        $categoryId,                    // categoryid
                        null,                           // parentid
                        $roomIds,                       // roomids
                        $roleIds,                       // roleids
                        $itemRow['name'],               // title
                        $itemRow['description'] ?? '',  // description
                        $itemType,                      // itemtype
                        $options,                       // options
                        $sortOrder,                     // sortorder
                        $isRequired,                    // isrequired
                        $defaultValue,                  // defaultvalue
                        $dueDaysOffset,                 // duedaysoffset
                        $dueDaysRelation,               // duedaysrelation
                        $USER->id,                      // usermodified
                        time(),                         // timecreated
                        time()                          // timemodified
                    );

                    $itemId = $item->save();
                    $categoryItemIds[] = $itemId;
                    $importedCount++;
                }

                // Update category with item IDs
                if (!empty($categoryItemIds)) {
                    self::update_category_items($categoryId, $categoryItemIds);
                }
            }

            // Update master category order
            self::update_master_category_order($masterid);

            return [
                'success' => true,
                'imported' => $importedCount,
                'message' => get_string('importsuccessful', 'mod_bookit', $importedCount)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('importfailed', 'mod_bookit', $e->getMessage())
            ];
        }
    }    /**
     * Update category items list.
     *
     * @param int $categoryId
     * @param array $itemIds
     */
    private static function update_category_items(int $categoryId, array $itemIds): void {
        $category = \mod_bookit\local\entity\bookit_checklist_category::from_database($categoryId);
        $category->checklistitems = implode(',', $itemIds);
        $category->save();
    }

    /**
     * Update master category order.
     *
     * @param int $masterid
     */
    private static function update_master_category_order(int $masterid): void {
        try {
            $categories = checklist_manager::get_categories_by_master_id($masterid);
            $categoryIds = array_map(fn($cat) => $cat->id, $categories);

            $master = \mod_bookit\local\entity\bookit_checklist_master::from_database($masterid);
            $master->mastercategoryorder = implode(',', $categoryIds);
            $master->save();
        } catch (\Exception $e) {
            // Log the error but don't fail the import for this
            error_log("Failed to update master category order for master ID $masterid: " . $e->getMessage());
        }
    }

    /**
     * Find room by name.
     *
     * @param string $name
     * @return object|null
     */
    private static function find_room_by_name(string $name): ?object {
        global $DB;

        $sql = "SELECT * FROM {bookit_room} WHERE " . $DB->sql_compare_text('name') . " = " . $DB->sql_compare_text(':name');
        return $DB->get_record_sql($sql, ['name' => $name]);
    }

    /**
     * Create room from CSV data.
     *
     * @param array $roomData
     * @return int|null
     */
    private static function create_room_from_data(array $roomData): ?int {
        global $DB, $USER;

        try {
            $record = new \stdClass();
            $record->name = $roomData['name'] ?? '';
            $record->description = $roomData['description'] ?? '';
            $record->eventcolor = $roomData['eventcolor'] ?? '#000000';
            $record->textclass = $roomData['textclass'] ?? 'text-dark';
            $record->capacity = $roomData['capacity'] ?? 0;
            $record->usercreated = $USER->id;
            $record->usermodified = $USER->id;
            $record->timecreated = time();
            $record->timemodified = time();

            return $DB->insert_record('bookit_room', $record);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Find role by shortname.
     *
     * @param string $shortname
     * @return object|null
     */
    private static function find_role_by_shortname(string $shortname): ?object {
        global $DB;

        $sql = "SELECT * FROM {role} WHERE " . $DB->sql_compare_text('shortname') . " = " . $DB->sql_compare_text(':shortname');
        return $DB->get_record_sql($sql, ['shortname' => $shortname]);
    }    /**
     * Export master checklist to PDF format.
     *
     * @param int $masterid The ID of the master checklist to export
     * @param string $filename Optional custom filename for the export
     * @return void Downloads the PDF file directly
     */
    public static function export_master_checklist_pdf(int $masterid, string $filename = ''): void {
        // TODO: Implement PDF export functionality.
    }

}
