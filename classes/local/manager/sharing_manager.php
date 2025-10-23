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
     * @param resource $filehandle File handle for the CSV file
     * @param string $encoding File encoding
     * @param string $delimiter CSV delimiter
     * @return bool Success status
     */
    public static function import_master_checklist_csv($filehandle, string $encoding = 'utf-8', string $delimiter = 'comma'): bool {
        // TODO: Implement CSV import functionality.
        return false;
    }

    /**
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
