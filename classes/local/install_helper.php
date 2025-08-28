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
 * Installation helper for mod_bookit.
 *
 * @package    mod_bookit
 * @copyright  2025 ssystems GmbH <oss@ssystems.de>
 * @author     Andreas Rosenthal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

defined('MOODLE_INTERNAL') || die();

use mod_bookit\local\entity\bookit_checklist_master;
use mod_bookit\local\entity\bookit_checklist_category;
use mod_bookit\local\entity\bookit_checklist_item;

/**
 * Installation helper class.
 */
class install_helper {

    /**
     * Create default checklist data during installation.
     *
     * @param bool $force Force creation even if data exists
     * @param bool $verbose Print verbose output
     * @return bool True if data was created, false otherwise
     */
    public static function create_default_checklists(bool $force = false, bool $verbose = false): bool {
        global $DB;

        // Check if a master checklist already exists.
        $existing = $DB->count_records('bookit_checklist_master');
        if ($existing > 0 && !$force) {
            if ($verbose) {
                mtrace('Checklist data already exists. Skipping creation.');
            }
            return false;
        }

        if ($verbose) {
            mtrace('Creating default checklist data for BookIt...');
        }

        // Collection of exam-related task items for our test data.
        $taskitems = [
            'Reserve room',
            'Prepare exam papers',
            'Notify students',
            'Arrange supervision staff',
            'Prepare answer sheets',
            'Setup exam room',
            'Print attendance list',
            'Grade exams',
            'Record results'
        ];

        $descriptions = [
            'Ensure room is booked at least two weeks before the exam date',
            'Must be reviewed and approved by department chair before printing',
            'Send examination details to all enrolled students via email and LMS',
            'Ensure sufficient staff is available for exam supervision',
            'Prepare standardized answer sheets for the exam',
            'Setup room according to examination requirements',
            'Print complete list of registered students',
            'Grade all exams within the deadline',
            'Record all results in the academic system'
        ];

        // Create the master checklist.
        if ($verbose) {
            mtrace('Creating master checklist...');
        }

        $master = new bookit_checklist_master(
            null,
            'University Examination Administration Checklist',
            'A comprehensive checklist for planning, executing, and concluding university examinations',
            1, // Make it the default
            []
        );
        $masterid = $master->save();

        if ($verbose) {
            mtrace("Created master checklist with ID: $masterid");
        }

        // Define category data
        $categories = [
            [
                'name' => 'Exam Preparation',
                'description' => 'Essential tasks for preparing university examinations',
                'sortorder' => 1,
                'items_count' => 3  // We'll create 3 items for each category
            ],
            [
                'name' => 'Exam Day',
                'description' => 'Tasks to be completed on the day of the examination',
                'sortorder' => 2,
                'items_count' => 3
            ],
            [
                'name' => 'Post-Exam',
                'description' => 'Follow-up tasks after the examination is complete',
                'sortorder' => 3,
                'items_count' => 3
            ]
        ];

        $categoryids = [];
        $itemtypes = [1, 2, 3]; // 1=text, 2=number, 3=date
        $itemIndex = 0;

        // Create three categories
        foreach ($categories as $categoryData) {
            if ($verbose) {
                mtrace("Creating category: {$categoryData['name']}");
            }

            // Create category
            $category = new bookit_checklist_category(
                null,
                $masterid,
                $categoryData['name'],
                $categoryData['description'],
                '', // checklist items - will be updated later
                $categoryData['sortorder']
            );

            $categoryid = $category->save();
            $categoryids[] = $categoryid;

            if ($verbose) {
                mtrace("  Category ID: $categoryid");
            }

            $itemids = [];

            // Create items for this category
            for ($i = 0; $i < $categoryData['items_count']; $i++) {
                $currentItemIndex = $itemIndex + $i;
                $itemname = $taskitems[$currentItemIndex];
                $itemtype = $itemtypes[$i % 3]; // Cycle through item types
                $desc = $descriptions[$currentItemIndex];

                // Create options based on item type
                $options = null;

                $defaultvalue = null;
                switch ($itemtype) {
                    case 1: // text
                        $defaultvalue = 'Enter details here';
                        break;
                    case 2: // number
                        $defaultvalue = 25;
                        break;
                    case 3: // date
                        $defaultvalue = time() + (7 * 86400); // 7 days from now
                        break;
                }

                if ($verbose) {
                    mtrace("    Creating item: $itemname");
                }

                // Randomly select a room and role if available
                $roomid = null;
                $roleid = null;

                $rooms = \mod_bookit\local\manager\checklist_manager::get_bookit_rooms();
                if (!empty($rooms)) {
                    $room = $rooms[array_rand($rooms)];
                    $roomid = $room['id'];
                }

                $roles = \mod_bookit\local\manager\checklist_manager::get_bookit_roles();
                if (!empty($roles)) {
                    $role = $roles[array_rand($roles)];
                    $roleid = $role->id;
                }

                $item = new bookit_checklist_item(
                    0, // ID will be set by save_to_database
                    $masterid,
                    $categoryid,
                    null, // No parent
                    $roomid, // Room ID (may be null)
                    $roleid, // Role ID (may be null)
                    $itemname,
                    $desc,
                    $itemtype,
                    $options,
                    $i + 1, // sortorder
                    1, // is_required (all required)
                    $defaultvalue,
                    ($i * 7), // due_days_offset (0, 7, 14 days)
                    null,
                    null,
                    null
                );

                $itemid = $item->save();
                $itemids[] = $itemid;

                if ($verbose) {
                    mtrace("      Item ID: $itemid");
                }
            }

            // Update the category with the item IDs
            $category->checklistitems = implode(',', $itemids);
            $category->save();

            if ($verbose) {
                mtrace("  Updated category with item IDs: " . implode(',', $itemids));
            }

            $itemIndex += $categoryData['items_count'];
        }

        // Update the master checklist with the category IDs
        $categoryidstr = implode(',', $categoryids);
        $master = bookit_checklist_master::from_database($masterid);
        $master->mastercategoryorder = $categoryidstr;
        $master->save();

        if ($verbose) {
            mtrace("Updated master checklist with category IDs: " . $categoryidstr);
            mtrace('Default checklist data created successfully!');
        }

        return true;
    }
}
