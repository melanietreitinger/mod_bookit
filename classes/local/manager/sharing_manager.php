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

namespace mod_bookit\local\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/pdflib.php');

use dml_exception;
use mod_bookit\local\entity\bookit_checklist_master;
use mod_bookit\local\pdf\bookit_pdf;

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
     * Get the logo file for PDF export based on configuration settings.
     *
     * @param string $source Logo source setting ('site', 'theme', 'custom')
     * @return \stored_file|null The logo file, or null if no logo available
     */
    private static function get_pdf_logo_file(string $source): ?\stored_file {
        global $CFG;

        switch ($source) {
            case 'site':
                $context = \context_system::instance();
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id, 'core_admin', 'logo', 0, 'itemid, filepath, filename', false);
                if (!empty($files)) {
                    return reset($files);
                }
                break;

            case 'theme':
                $themeboostunionpath = $CFG->dirroot . '/theme/boost_union';
                if (file_exists($themeboostunionpath) && is_dir($themeboostunionpath)) {
                    $context = \context_system::instance();
                    $fs = get_file_storage();
                    $files = $fs->get_area_files($context->id, 'theme_boost_union', 'logo', 0, 'itemid, filepath, filename', false);
                    if (!empty($files)) {
                        return reset($files);
                    }
                }
                return self::get_pdf_logo_file('site');

            case 'custom':
                $context = \context_system::instance();
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id, 'mod_bookit', 'pdf_logo_custom', 0, 'itemid, filepath, filename', false);
                if (!empty($files)) {
                    return reset($files);
                }
                return self::get_pdf_logo_file('site');
        }

        return null;
    }

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

        $master = bookit_checklist_master::from_database($masterid);

        if (empty($filename)) {
            $filename = 'checklist_master_export';
        }

        $csvwriter = new \csv_export_writer('comma', '"', 'application/download');
        $csvwriter->set_filename($filename, '.csv');

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
     * @param bool $importrooms Whether to import room assignments (default: true)
     * @return array Result array with success status and import details
     */
    public static function import_master_checklist_csv(int $masterid, string $csvdata, bool $importrooms = true): array {
        global $DB, $USER;

        if (empty($csvdata)) {
            return ['success' => false, 'message' => get_string('invalidcsvformat', 'mod_bookit')];
        }

        try {
            if (!$DB->record_exists('bookit_checklist_master', ['id' => $masterid])) {
                return ['success' => false, 'message' => "Master checklist with ID $masterid does not exist"];
            }

            $lines = str_getcsv($csvdata, "\n", '"', '\\');
            if (count($lines) < 2) {
                return ['success' => false, 'message' => get_string('invalidcsvformat', 'mod_bookit')];
            }

            $headers = str_getcsv($lines[0], ',', '"', '\\');
            $requiredheaders = ['type', 'name'];

            foreach ($requiredheaders as $required) {
                if (!in_array($required, $headers)) {
                    return ['success' => false, 'message' => get_string('invalidcsvformat', 'mod_bookit')];
                }
            }

            $maxcategorysortorder = $DB->get_field_sql(
                "SELECT COALESCE(MAX(sortorder), 0) FROM {bookit_checklist_category} WHERE masterid = ?",
                [$masterid]
            );

            $importedcount = 0;
            $roomcache = [];
            $roomidtonamemap = [];
            $categoriesdata = [];

            $currentcategoryname = null;

            for ($i = 1; $i < count($lines); $i++) {
                $data = str_getcsv($lines[$i], ',', '"', '\\');
                if (count($data) < count($headers)) {
                    continue;
                }

                $row = array_combine($headers, $data);

                if ($importrooms && $row['type'] === 'room' && !empty($row['name']) && !empty($row['room_data'])) {
                    $roomdata = json_decode($row['room_data'], true);
                    if ($roomdata) {
                        $roomname = $row['name'];
                        $roomidfromcsv = $roomdata['id'] ?? null;

                        if ($roomidfromcsv) {
                            $roomidtonamemap[$roomidfromcsv] = $roomname;
                        }

                        $existingroom = self::find_room_by_name($roomname);
                        if ($existingroom) {
                            $roomcache[$roomname] = $existingroom->id;
                        } else {
                            $newroomid = self::create_room_from_data($roomdata);
                            if ($newroomid) {
                                $roomcache[$roomname] = $newroomid;
                            }
                        }
                    }
                }

                if ($row['type'] === 'category' && !empty($row['name'])) {
                    $currentcategoryname = $row['name'];
                    if (!isset($categoriesdata[$currentcategoryname])) {
                        $categoriesdata[$currentcategoryname] = [
                            'name' => $row['name'],
                            'description' => $row['description'] ?? '',
                            'checklist_items' => $row['checklist_items'] ?? '',
                            'items' => [],
                        ];
                    }
                } else if ($row['type'] === 'item' && !empty($row['name']) && $currentcategoryname) {
                    $categoriesdata[$currentcategoryname]['items'][] = $row;
                }
            }

            $categorymapping = [];

            foreach ($categoriesdata as $categoryname => $categoryinfo) {
                // Create category.
                $category = new \mod_bookit\local\entity\bookit_checklist_category(
                    0,
                    $masterid,
                    $categoryinfo['name'],
                    $categoryinfo['description'],
                    null, // Will be updated later with item IDs.
                    ++$maxcategorysortorder,
                    $USER->id,
                    time(),
                    time()
                );

                $categoryid = $category->save();
                $categorymapping[$categoryname] = $categoryid;
                $importedcount++;

                // Create items for this category.
                $categoryitemids = [];
                foreach ($categoryinfo['items'] as $itemrow) {
                    // Parse room IDs from CSV (only if import_rooms is enabled).
                    $roomids = [];
                    if ($importrooms && !empty($itemrow['room_ids'])) {
                        $roomidsfromcsv = json_decode($itemrow['room_ids'], true);
                        if (is_array($roomidsfromcsv)) {
                            foreach ($roomidsfromcsv as $roomref) {
                                // Extract room ID from "room_X" format.
                                if (preg_match('/room_(\d+)/', $roomref, $matches)) {
                                    $csvroomid = (int)$matches[1];
                                    // Find room name by CSV room ID.
                                    if (isset($roomidtonamemap[$csvroomid])) {
                                        $roomname = $roomidtonamemap[$csvroomid];
                                        // Get actual database room ID by name.
                                        if (isset($roomcache[$roomname])) {
                                            $roomids[] = $roomcache[$roomname];
                                        } else {
                                            debugging("Room '$roomname' not found in roomCache");
                                        }
                                    } else {
                                        debugging("CSV room ID '$csvroomid' not found in roomIdToNameMap");
                                    }
                                } else {
                                    debugging("Invalid room reference format: '$roomref'");
                                }
                            }
                        }
                    }

                    // Debug logging.
                    debugging("Item '{$itemrow['name']}' - Room IDs: " . json_encode($roomids));

                    // Parse role IDs from CSV - check if roles exist.
                    $roleids = [];
                    if (!empty($itemrow['role_shortnames'])) {
                        $roleshortnames = json_decode($itemrow['role_shortnames'], true);
                        if (is_array($roleshortnames)) {
                            foreach ($roleshortnames as $shortname) {
                                $role = self::find_role_by_shortname($shortname);
                                if ($role) {
                                    $roleids[] = $role->id;
                                }
                            }
                        }
                    }

                    // If no valid roles found, use role ID 0 as fallback.
                    if (empty($roleids)) {
                        $roleids = [0];
                    }

                    // Get max sort order for items in this category.
                    $maxitemsortorder = $DB->get_field_sql(
                        "SELECT COALESCE(MAX(sortorder), 0) FROM {bookit_checklist_item} WHERE categoryid = ?",
                        [$categoryid]
                    );

                    // Properly cast CSV values to correct types.
                    $itemtype = !empty($itemrow['item_type']) ? (int)$itemrow['item_type'] : 1;
                    $sortorder = !empty($itemrow['sort_order']) ? (int)$itemrow['sort_order'] : ++$maxitemsortorder;
                    $isrequired = !empty($itemrow['is_required']) ? (int)$itemrow['is_required'] : 0;
                    $duedaysoffset = !empty($itemrow['due_days_offset']) ? (int)$itemrow['due_days_offset'] : null;
                    $duedaysrelation = !empty($itemrow['due_days_relation']) ? $itemrow['due_days_relation'] : null;
                    $defaultvalue = !empty($itemrow['default_value']) ? $itemrow['default_value'] : null;
                    $options = !empty($itemrow['options']) ? $itemrow['options'] : null;

                    // Create new item with all attributes.
                    $item = new \mod_bookit\local\entity\bookit_checklist_item(
                        0,
                        $masterid,
                        $categoryid,
                        null,
                        $roomids,
                        $roleids,
                        $itemrow['name'],
                        $itemrow['description'] ?? '',
                        $itemtype,
                        $options,
                        $sortorder,
                        $isrequired,
                        $defaultvalue,
                        $duedaysoffset,
                        $duedaysrelation,
                        $USER->id,
                        time(),
                        time()
                    );

                    $itemid = $item->save();
                    $categoryitemids[] = $itemid;
                    $importedcount++;
                }

                // Update category with item IDs.
                if (!empty($categoryitemids)) {
                    self::update_category_items($categoryid, $categoryitemids);
                }
            }

            // Update master category order.
            self::update_master_category_order($masterid);

            return [
                'success' => true,
                'imported' => $importedcount,
                'message' => get_string('importsuccessful', 'mod_bookit', $importedcount),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('importfailed', 'mod_bookit', $e->getMessage()),
            ];
        }
    }

    /**
     * Update category items list.
     *
     * @param int $categoryid
     * @param array $itemids
     */
    private static function update_category_items(int $categoryid, array $itemids): void {
        $category = \mod_bookit\local\entity\bookit_checklist_category::from_database($categoryid);
        $category->checklistitems = implode(',', $itemids);
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
            $categoryids = array_map(fn($cat) => $cat->id, $categories);

            $master = bookit_checklist_master::from_database($masterid);
            $master->mastercategoryorder = implode(',', $categoryids);
            $master->save();
        } catch (\Exception $e) {
            // Log the error but don't fail the import for this.
            debugging("Failed to update master category order for master ID $masterid: " . $e->getMessage());
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
     * @param array $roomdata
     * @return int|null
     */
    private static function create_room_from_data(array $roomdata): ?int {
        global $DB, $USER;

        try {
            $record = new \stdClass();
            $record->name = $roomdata['name'] ?? '';
            $record->description = $roomdata['description'] ?? '';
            $record->eventcolor = $roomdata['eventcolor'] ?? '#000000';
            $record->textclass = $roomdata['textclass'] ?? 'text-dark';
            $record->capacity = $roomdata['capacity'] ?? 0;
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
    }

    /**
     * Export master checklist to PDF format.
     *
     * @param int $masterid The ID of the master checklist to export
     * @param string $filename Optional custom filename for the export
     * @param string $title Optional custom title for the PDF document
     * @return void Downloads the PDF file directly
     */
    public static function export_master_checklist_pdf(int $masterid, string $filename = '', string $title = ''): void {
        global $OUTPUT, $CFG;

        // Include PDF library.
        require_once($CFG->libdir . '/pdflib.php');

        // Get the master checklist.
        $master = bookit_checklist_master::from_database($masterid);

        if (empty($filename)) {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = clean_filename('master_checklist_export_' . $timestamp);
        }

        // Prepare data structure manually to avoid renderer type issues.
        $data = new \stdClass();
        $data->id = $master->id;
        $data->name = $master->name;
        $data->checklistcategories = [];

        // Get all rooms and roles once for efficiency.
        $allrooms = checklist_manager::get_bookit_rooms();
        $allroles = checklist_manager::get_bookit_roles();

        // Create lookup arrays for faster access.
        $roomlookup = [];
        foreach ($allrooms as $room) {
            $roomlookup[$room->id] = $room;
        }

        $rolelookup = [];
        foreach ($allroles as $role) {
            $rolelookup[$role->id] = $role;
        }

        // Get categories and items directly.
        foreach ($master->checklistcategories as $category) {
            $categorydata = new \stdClass();
            $categorydata->id = $category->id;
            $categorydata->name = $category->name;
            $categorydata->checklistitems = [];

            if (!empty($category->checklistitems)) {
                $itemids = explode(',', $category->checklistitems);

                foreach ($itemids as $itemid) {
                    $item = \mod_bookit\local\entity\bookit_checklist_item::from_database((int)$itemid);

                    $itemdata = new \stdClass();
                    $itemdata->id = $item->id;
                    $itemdata->title = $item->title;
                    $itemdata->roomnames = [];
                    $itemdata->rolenames = [];

                    // Get room names.
                    if (!empty($item->roomids)) {
                        foreach ($item->roomids as $roomid) {
                            if (isset($roomlookup[$roomid])) {
                                $room = $roomlookup[$roomid];
                                $roomname = new \stdClass();
                                $roomname->roomname = $room->name;
                                $roomname->eventcolor = $room->eventcolor ?? '#007bff';
                                $itemdata->roomnames[] = $roomname;
                            }
                        }
                    }

                    // Get role names.
                    if (!empty($item->roleids)) {
                        foreach ($item->roleids as $roleid) {
                            if (isset($rolelookup[$roleid])) {
                                $role = $rolelookup[$roleid];
                                $rolename = new \stdClass();
                                $rolename->rolename = $role->name;
                                $itemdata->rolenames[] = $rolename;
                            }
                        }
                    }

                    $categorydata->checklistitems[] = $itemdata;
                }
            }

            $data->checklistcategories[] = $categorydata;
        }

        // Determine the PDF title: use custom title if provided, otherwise use master checklist name.
        $pdftitle = !empty($title) ? $title : $master->name;

        // Add the title to template data so it can be used in the Mustache template.
        $data->title = $pdftitle;

        // Render the PDF template.
        $html = $OUTPUT->render_from_template('mod_bookit/bookit_checklist_master_pdf', $data);

        // Create PDF instance with right-aligned header text.
        $pdf = new bookit_pdf();

        // Create export timestamp.
        $exportedon = get_string('exportedon', 'mod_bookit', userdate(time(), '%A, %d %B %Y, %H:%M'));

        // Get logo configuration for PDF header.
        $logoenabled = get_config('mod_bookit', 'pdf_logo_enable');
        $logopath = '';

        if ($logoenabled) {
            $logosource = get_config('mod_bookit', 'pdf_logo_source');
            $logofile = self::get_pdf_logo_file($logosource);

            if ($logofile) {
                $logopath = '@' . $logofile->get_content();
            } else {
                $logopath = 'pix/moodlelogo.png';
            }
        }

        $pdf->setHeaderData($logopath, 25, $pdftitle, 'BookIt Module - ' . $exportedon, [0, 0, 0], [0, 0, 0]);
        $pdf->setFooterData([0, 0, 0], [0, 0, 0]);

        // Set header and footer fonts.
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

        // Set margins.
        $pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->setFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks.
        $pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        $pdf->AddPage();
        $pdf->writeHTML($html);
        $pdf->Output($filename . '.pdf', 'D');
    }
}
