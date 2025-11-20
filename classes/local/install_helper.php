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

use mod_bookit\local\entity\masterchecklist\bookit_checklist_master;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_category;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_item;

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

        // First, ensure we have some default rooms available.
        self::create_default_rooms($force, $verbose);

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
            'Record results',
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
            'Record all results in the academic system',
        ];

        // Create the master checklist.
        if ($verbose) {
            mtrace('Creating master checklist...');
        }

        $master = new bookit_checklist_master(
            null,
            'University Examination Administration Checklist',
            'A comprehensive checklist for planning, executing, and concluding university examinations',
            1, // Make it the default.
            []
        );

        $masterid = $master->save();

        // Update the ID to 1 for testing purposes.
        if ($masterid != 1) {
            $DB->execute("UPDATE {bookit_checklist_master} SET id = 1 WHERE id = ?", [$masterid]);
            $masterid = 1;
        }

        if ($verbose) {
            mtrace("Created master checklist with ID: $masterid");
        }

        // Define category data.
        $categories = [
            [
                'name' => 'Exam Preparation',
                'description' => 'Essential tasks for preparing university examinations',
                'sortorder' => 1,
                'items_count' => 3, // We'll create 3 items for each category.
            ],
            [
                'name' => 'Exam Day',
                'description' => 'Tasks to be completed on the day of the examination',
                'sortorder' => 2,
                'items_count' => 3,
            ],
            [
                'name' => 'Post-Exam',
                'description' => 'Follow-up tasks after the examination is complete',
                'sortorder' => 3,
                'items_count' => 3,
            ],
        ];

        $categoryids = [];
        $itemtypes = [1, 2, 3];
        $itemindex = 0;

        // Create three categories.
        foreach ($categories as $categorydata) {
            if ($verbose) {
                mtrace("Creating category: {$categorydata['name']}");
            }

            // Create category.
            $category = new bookit_checklist_category(
                null,
                $masterid,
                $categorydata['name'],
                $categorydata['description'],
                null, // Checklist items - will be updated later.
                $categorydata['sortorder']
            );

            $categoryid = $category->save();
            $categoryids[] = $categoryid;

            if ($verbose) {
                mtrace("  Category ID: $categoryid");
            }

            $itemids = [];

            // Create items for this category.
            for ($i = 0; $i < $categorydata['items_count']; $i++) {
                $currentitemindex = $itemindex + $i;
                $itemname = $taskitems[$currentitemindex];
                $itemtype = $itemtypes[$i % 3]; // Cycle through item types.
                $desc = $descriptions[$currentitemindex];

                // Create options based on item type.
                $options = null;

                $defaultvalue = null;

                if ($verbose) {
                    mtrace("    Creating item: $itemname");
                }

                // Randomly select a room and role if available.
                $roomids = [];
                $roleids = [];

                $rooms = \mod_bookit\local\manager\checklist_manager::get_bookit_rooms();
                if (!empty($rooms)) {
                    $roomids = array_map('strval', array_column($rooms, 'id'));

                    if (count($roomids) > 2) {
                        $numtoremove = rand(1, 2);
                        $keystoremove = array_rand($roomids, $numtoremove);
                        if (!is_array($keystoremove)) {
                            $keystoremove = [$keystoremove];
                        }
                        foreach ($keystoremove as $key) {
                            unset($roomids[$key]);
                        }
                        $roomids = array_values($roomids);
                    }
                }

                $roles = \mod_bookit\local\manager\checklist_manager::get_bookit_roles();
                if (!empty($roles)) {
                    $roleids = array_map('strval', array_column($roles, 'id'));

                    if (count($roleids) > 2) {
                        $numtoremove = rand(1, 2);
                        $keystoremove = array_rand($roleids, $numtoremove);
                        if (!is_array($keystoremove)) {
                            $keystoremove = [$keystoremove];
                        }
                        foreach ($keystoremove as $key) {
                            unset($roleids[$key]);
                        }
                        $roleids = array_values($roleids);
                    }
                }

                $item = new bookit_checklist_item(
                    0, // ID will be set by save_to_database.
                    $masterid,
                    $categoryid,
                    null, // No parent.
                    $roomids, // Room IDs (array, may be null).
                    $roleids, // Role IDs (array, may be null).
                    $itemname,
                    $desc,
                    $itemtype,
                    $options,
                    $i + 1, // Sortorder.
                    1, // Is_required (all required).
                    $defaultvalue,
                    ($i * 7), // Due_days_offset (0, 7, 14 days).
                    null,
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

            // Update the category with the item IDs.
            $category->checklistitems = implode(',', $itemids);
            $category->save();

            if ($verbose) {
                mtrace("  Updated category with item IDs: " . implode(',', $itemids));
            }

            $itemindex += $categorydata['items_count'];
        }

        // Update the master checklist with the category IDs.
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

    /**
     * Run complete installation setup including roles, users, rooms, and checklists.
     *
     * @param bool $force Force creation even if data exists
     * @param bool $verbose Print verbose output
     * @return bool True if installation was successful, false otherwise
     */
    public static function install_all_defaults(bool $force = false, bool $verbose = false): bool {
        if ($verbose) {
            mtrace('Starting complete BookIt installation...');
        }

        $success = true;

        // Import roles first.
        if ($verbose) {
            mtrace('Step 1: Importing default roles...');
        }
        $rolesimported = self::import_default_roles($force, $verbose);
        if (!$rolesimported && $verbose) {
            mtrace('No roles were imported or roles already exist.');
        }

        // Import users (depends on roles).
        if ($verbose) {
            mtrace('Step 2: Importing default users...');
        }
        $usersimported = self::import_default_users($force, $verbose);
        if (!$usersimported && $verbose) {
            mtrace('No users were imported or users already exist.');
        }

        // Create default checklists (includes rooms creation).
        if ($verbose) {
            mtrace('Step 3: Creating default checklists and rooms...');
        }
        $checklistscreated = self::create_default_checklists($force, $verbose);
        if (!$checklistscreated && $verbose) {
            mtrace('No checklists were created or checklists already exist.');
        }

        if ($verbose) {
            mtrace('BookIt installation completed.');
        }

        return $success;
    }

    /**
     * Import default roles from XML files in assets/roles/ directory.
     *
     * @param bool $force Force import even if role already exists
     * @param bool $verbose Print verbose output
     * @return bool True if at least one role was imported, false otherwise
     */
    public static function import_default_roles(bool $force = false, bool $verbose = false): bool {
        global $CFG, $DB;
        require_once($CFG->libdir . '/accesslib.php');
        require_once($CFG->dirroot . '/admin/roles/classes/preset.php');

        $rolesdir = $CFG->dirroot . '/mod/bookit/assets/roles/';
        $dirhandle = opendir($rolesdir);
        if (!$dirhandle) {
            if ($verbose) {
                mtrace('Could not open roles directory: ' . $rolesdir);
            }
            return false;
        }

        $rolesimported = false;

        while (false !== ($filename = readdir($dirhandle))) {
            if (substr($filename, -4) !== '.xml') {
                continue;
            }

            $fullpath = $rolesdir . $filename;
            if ($verbose) {
                mtrace('Processing role file: ' . $filename);
            }

            $xml = file_get_contents($fullpath);
            if (!$xml) {
                if ($verbose) {
                    mtrace('Could not read file: ' . $fullpath);
                }
                continue;
            }

            if (!\core_role_preset::is_valid_preset($xml)) {
                if ($verbose) {
                    mtrace('Invalid role preset XML in file: ' . $fullpath);
                }
                continue;
            }

            // Parse the XML file to get role information.
            $roleinfo = \core_role_preset::parse_preset($xml);
            if (!$roleinfo) {
                if ($verbose) {
                    mtrace('Could not parse role preset XML in file: ' . $fullpath);
                }
                continue;
            }

            // Check if role with this shortname already exists.
            if ($existingrole = $DB->get_record('role', ['shortname' => $roleinfo['shortname']])) {
                if ($verbose) {
                    mtrace('Role with shortname "' . $roleinfo['shortname'] . '" already exists (ID: ' . $existingrole->id . ')');
                }
                if (!$force) {
                    continue;
                }
                if ($verbose) {
                    mtrace('Updating existing role due to force flag.');
                }
                $roleid = $existingrole->id;
            } else {
                // Create a new role record.
                $role = new \stdClass();
                $role->name = $roleinfo['name'];
                $role->shortname = $roleinfo['shortname'];
                $role->description = $roleinfo['description'];
                $role->archetype = $roleinfo['archetype'];

                $roleid = create_role($role->name, $role->shortname, $role->description, $role->archetype);

                if ($verbose) {
                    mtrace('Created new role with ID: ' . $roleid);
                }
            }

            // Set context levels for this role.
            if (isset($roleinfo['contextlevels']) && is_array($roleinfo['contextlevels'])) {
                // First, reset current context levels.
                $DB->delete_records('role_context_levels', ['roleid' => $roleid]);

                // Then add new context levels.
                foreach ($roleinfo['contextlevels'] as $contextlevel) {
                    $DB->insert_record('role_context_levels', [
                        'roleid' => $roleid,
                        'contextlevel' => $contextlevel,
                    ]);
                }
                if ($verbose) {
                    mtrace('Set ' . count($roleinfo['contextlevels']) . ' context levels for role.');
                }
            }

            // Set role permissions.
            if (isset($roleinfo['permissions']) && is_array($roleinfo['permissions'])) {
                $systemcontext = \context_system::instance();

                foreach ($roleinfo['permissions'] as $capability => $permission) {
                    if ($permission != CAP_INHERIT) {
                        // Delete any existing capability.
                        $DB->delete_records('role_capabilities', [
                            'roleid' => $roleid,
                            'capability' => $capability,
                            'contextid' => $systemcontext->id,
                        ]);

                        // Add the new capability.
                        $DB->insert_record('role_capabilities', [
                            'roleid' => $roleid,
                            'capability' => $capability,
                            'permission' => $permission,
                            'contextid' => $systemcontext->id,
                            'timemodified' => time(),
                        ]);
                    }
                }
                if ($verbose) {
                    mtrace('Set permissions for role.');
                }
            }

            // Handle role relationships (assign, override, switch).
            foreach (['assign', 'override', 'switch', 'view'] as $type) {
                if (isset($roleinfo['allow' . $type]) && is_array($roleinfo['allow' . $type])) {
                    // First, remove existing records.
                    $DB->delete_records('role_allow_' . $type, ['roleid' => $roleid]);

                    // Add new records.
                    foreach ($roleinfo['allow' . $type] as $allowid) {
                        if ($allowid == -1) {
                            // Special case: allow assigning/overriding self.
                            $DB->insert_record('role_allow_' . $type, [
                                'roleid' => $roleid,
                                'allow' . $type => $roleid,
                            ]);
                        } else {
                            $DB->insert_record('role_allow_' . $type, [
                                'roleid' => $roleid,
                                'allow' . $type => $allowid,
                            ]);
                        }
                    }
                    if ($verbose) {
                        mtrace('Set allow' . $type . ' permissions for role.');
                    }
                }
            }

            // Mark that at least one role was imported.
            $rolesimported = true;

            if ($verbose) {
                mtrace('Successfully imported role: ' . $roleinfo['name']);
            }
        }

        closedir($dirhandle);

        if ($verbose) {
            if ($rolesimported) {
                mtrace('Completed importing roles.');
            } else {
                mtrace('No roles were imported.');
            }
        }

        return $rolesimported;
    }

    /**
     * Create default rooms for testing purposes.
     *
     * @param bool $force Force creation even if rooms exist
     * @param bool $verbose Print verbose output
     * @return bool True if rooms were created, false otherwise
     */
    public static function create_default_rooms(bool $force = false, bool $verbose = false): bool {
        global $DB, $USER;

        // Check if rooms already exist.
        $existing = $DB->count_records('bookit_room');
        if ($existing > 0 && !$force) {
            if ($verbose) {
                mtrace('Rooms already exist. Skipping creation.');
            }
            return false;
        }

        if ($verbose) {
            mtrace('Creating default rooms for BookIt...');
        }

        // Define sample rooms.
        $rooms = [
            [
                'name' => 'Lecture Hall A',
                'description' => 'Large lecture hall with 200 seats, equipped with modern AV technology',
                'eventcolor' => '#FF6B6B',
                'active' => 1,
                'roommode' => 1,
            ],
            [
                'name' => 'Seminar Room B',
                'description' => 'Medium-sized seminar room for up to 50 students',
                'eventcolor' => '#4ECDC4',
                'active' => 1,
                'roommode' => 0,
            ],
            [
                'name' => 'Computer Lab C',
                'description' => 'Computer laboratory with 30 workstations',
                'eventcolor' => '#45B7D1',
                'active' => 1,
                'roommode' => 1,
            ],
            [
                'name' => 'Conference Room D',
                'description' => 'Small conference room for meetings and group work',
                'eventcolor' => '#96CEB4',
                'active' => 1,
                'roommode' => 0,
            ],
        ];

        $roomscreated = 0;
        foreach ($rooms as $roomdata) {
            $room = new \stdClass();
            $room->name = $roomdata['name'];
            $room->description = $roomdata['description'];
            $room->eventcolor = $roomdata['eventcolor'];
            $room->active = $roomdata['active'];
            $room->roommode = $roomdata['roommode'];
            $room->usermodified = $USER->id ?? 2; // Default to admin user if no user set.
            $room->timecreated = time();
            $room->timemodified = time();

            $roomid = $DB->insert_record('bookit_room', $room);

            if ($verbose) {
                mtrace("Created room: {$room->name} (ID: $roomid)");
            }

            $roomscreated++;
        }

        if ($verbose) {
            mtrace("Successfully created $roomscreated default rooms!");
        }

        return $roomscreated > 0;
    }

    /**
     * Import default users from CSV file in assets/users/ directory.
     *
     * @param bool $force Force creation even if users exist
     * @param bool $verbose Print verbose output
     * @return bool True if at least one user was imported, false otherwise
     */
    public static function import_default_users(bool $force = false, bool $verbose = false): bool {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $csvfile = $CFG->dirroot . '/mod/bookit/assets/users/users.csv';

        if (!file_exists($csvfile)) {
            if ($verbose) {
                mtrace('Users CSV file not found: ' . $csvfile);
            }
            return false;
        }

        if ($verbose) {
            mtrace('Processing users CSV file: ' . $csvfile);
        }

        $handle = fopen($csvfile, 'r');
        if (!$handle) {
            if ($verbose) {
                mtrace('Could not open CSV file: ' . $csvfile);
            }
            return false;
        }

        $usersimported = false;
        $linenum = 0;
        $systemcontext = \context_system::instance();

        while (($data = fgetcsv($handle, 1000, ';', "\"", "\\")) !== false) {
            $linenum++;

            // Skip header row.
            if ($linenum === 1) {
                continue;
            }

            // Skip empty rows.
            if (empty($data) || count($data) < 6) {
                continue;
            }

            // Parse CSV data: username;email;firstname;lastname;auth;password.
            $username = trim($data[0]);
            $email = trim($data[1]);
            $firstname = trim($data[2]);
            $lastname = trim($data[3]);
            $auth = trim($data[4]);
            $password = trim($data[5]);

            if (empty($username) || empty($email)) {
                if ($verbose) {
                    mtrace("Skipping line $linenum: missing username or email");
                }
                continue;
            }

            // Check if user already exists.
            $existinguser = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
            if ($existinguser && !$force) {
                if ($verbose) {
                    mtrace("User '$username' already exists (ID: {$existinguser->id}). Skipping.");
                }
                // Still check role assignment for existing users.
                self::assign_bookit_role_to_user($existinguser, $verbose);
                continue;
            }

            if ($verbose) {
                mtrace("Creating user: $username ($firstname $lastname)");
            }

            // Create user object.
            $user = new \stdClass();
            $user->username = $username;
            $user->email = $email;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->auth = $auth;
            $user->confirmed = 1;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->timecreated = time();
            $user->timemodified = time();

            // Set password if provided and not 'x'.
            if ($password !== 'x' && !empty($password)) {
                $user->password = hash_internal_user_password($password);
            } else {
                // Generate a random password if none provided.
                $user->password = hash_internal_user_password(generate_password());
            }

            try {
                $userid = user_create_user($user, false, false);

                if ($verbose) {
                    mtrace("  Created user with ID: $userid");
                }

                // Get the created user record.
                $createduser = $DB->get_record('user', ['id' => $userid]);

                // Assign appropriate BookIt role.
                self::assign_bookit_role_to_user($createduser, $verbose);

                $usersimported = true;
            } catch (\Exception $e) {
                if ($verbose) {
                    mtrace("  Error creating user '$username': " . $e->getMessage());
                }
            }
        }

        fclose($handle);

        if ($verbose) {
            if ($usersimported) {
                mtrace('Completed importing users.');
            } else {
                mtrace('No users were imported.');
            }
        }

        return $usersimported;
    }

    /**
     * Assign appropriate BookIt role to a user based on their username.
     *
     * @param \stdClass $user User record
     * @param bool $verbose Print verbose output
     * @return void
     */
    private static function assign_bookit_role_to_user(\stdClass $user, bool $verbose = false): void {
        global $DB;

        $systemcontext = \context_system::instance();

        // Determine role based on username patterns.
        $roleshortname = null;
        $username = strtolower($user->username);

        if (strpos($username, 'serviceteam') !== false) {
            $roleshortname = 'bookit_serviceteam';
        } else if (strpos($username, 'examiner') !== false) {
            $roleshortname = 'bookit_examiner';
        } else if (strpos($username, 'observer') !== false) {
            $roleshortname = 'bookit_observer';
        } else if (strpos($username, 'support') !== false) {
            $roleshortname = 'bookit_supportonsite';
        } else if (strpos($username, 'booker') !== false) {
            $roleshortname = 'bookit_bookingperson';
        }

        if (!$roleshortname) {
            if ($verbose) {
                mtrace("  No specific BookIt role determined for user: {$user->username}");
            }
            return;
        }

        // Get the role.
        $role = $DB->get_record('role', ['shortname' => $roleshortname]);
        if (!$role) {
            if ($verbose) {
                mtrace("  Role '$roleshortname' not found for user: {$user->username}");
            }
            return;
        }

        // Check if user already has this role.
        $existing = $DB->get_record('role_assignments', [
            'roleid' => $role->id,
            'userid' => $user->id,
            'contextid' => $systemcontext->id,
        ]);

        if ($existing) {
            if ($verbose) {
                mtrace("  User {$user->username} already has role: {$role->shortname}");
            }
            return;
        }

        // Assign the role.
        try {
            role_assign($role->id, $user->id, $systemcontext->id);
            if ($verbose) {
                mtrace("  Assigned role '{$role->shortname}' to user: {$user->username}");
            }
        } catch (\Exception $e) {
            if ($verbose) {
                mtrace("  Error assigning role to user {$user->username}: " . $e->getMessage());
            }
        }
    }
}
