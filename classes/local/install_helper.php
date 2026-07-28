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

use core_text;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_master;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_category;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_item;
use mod_bookit\local\entity\resource\bookit_resource_category;
use mod_bookit\local\entity\resource\bookit_resource;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\resource_manager;
use mod_bookit\local\manager\weekplan_manager;
use mod_bookit\local\persistent\room;

/**
 * Installation helper class.
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * @SuppressWarnings(PHPMD)
 */
class install_helper {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /** Default standalone room name. */
    public const DEFAULT_ROOM_NAME = 'Default room';

    /** Default standalone room description. */
    public const DEFAULT_ROOM_DESCRIPTION = 'The default room for all events.';

    /** Default standalone institution name. */
    public const DEFAULT_INSTITUTION_NAME = 'Standard-Institution';

    /** Default standalone weekplan name. */
    public const DEFAULT_WEEKPLAN_NAME = 'Default Weekplan';

    /** Default standalone weekplan schedule. */
    public const DEFAULT_WEEKPLAN_SCHEDULE = "Mo 09:00-17:00\nDi 09:00-17:00\nMi 09:00-17:00\nDo 09:00-17:00\nFr 09:00-17:00";

    /** Config key for the optional resources module state. */
    public const CONFIG_RESOURCES_ENABLED = 'resourcesenabled';

    /** Config key for the optional checklist module state. */
    public const CONFIG_CHECKLIST_ENABLED = 'checklistenabled';

    /**
     * Check whether the optional resources module is enabled.
     *
     * @return bool
     */
    public static function is_resources_enabled(): bool {
        return (int)get_config('mod_bookit', self::CONFIG_RESOURCES_ENABLED) === 1;
    }

    /**
     * Check whether the optional checklist module is enabled.
     *
     * @return bool
     */
    public static function is_checklist_enabled(): bool {
        return (int)get_config('mod_bookit', self::CONFIG_CHECKLIST_ENABLED) === 1;
    }

    /**
     * Ensure optional plugin-part config defaults exist.
     *
     * @param bool $enabledbydefault
     * @return void
     */
    public static function ensure_optional_part_defaults(bool $enabledbydefault = false): void {
        $defaultvalue = $enabledbydefault ? 1 : 0;

        if (get_config('mod_bookit', self::CONFIG_RESOURCES_ENABLED) === false) {
            set_config(self::CONFIG_RESOURCES_ENABLED, $defaultvalue, 'mod_bookit');
        }

        if (get_config('mod_bookit', self::CONFIG_CHECKLIST_ENABLED) === false) {
            set_config(self::CONFIG_CHECKLIST_ENABLED, $defaultvalue, 'mod_bookit');
        }
    }

    /**
     * Return shared metadata for booking-status notifications keyed by expressive status names.
     *
     * @return array<string, array{id:int,key:string,enabledconfig:string,subjectconfig:string,bodyconfig:string,
     *     enabledstring:string,subjectstring:string,bodystring:string}>
     */
    public static function get_booking_status_notification_statuses(): array {
        return [
            'new' => [
                'id' => event_access_manager::BOOKINGSTATUS_NEW,
                'key' => 'new',
                'enabledconfig' => 'bookingstatus_enabled_new',
                'subjectconfig' => 'bookingstatus_subject_new',
                'bodyconfig' => 'bookingstatus_body_new',
                'enabledstring' => 'bookingstatus_enabled_new',
                'subjectstring' => 'bookingstatus_subject_new',
                'bodystring' => 'bookingstatus_body_new',
            ],
            'inprogress' => [
                'id' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                'key' => 'inprogress',
                'enabledconfig' => 'bookingstatus_enabled_inprogress',
                'subjectconfig' => 'bookingstatus_subject_inprogress',
                'bodyconfig' => 'bookingstatus_body_inprogress',
                'enabledstring' => 'bookingstatus_enabled_inprogress',
                'subjectstring' => 'bookingstatus_subject_inprogress',
                'bodystring' => 'bookingstatus_body_inprogress',
            ],
            'confirmed' => [
                'id' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'key' => 'confirmed',
                'enabledconfig' => 'bookingstatus_enabled_confirmed',
                'subjectconfig' => 'bookingstatus_subject_confirmed',
                'bodyconfig' => 'bookingstatus_body_confirmed',
                'enabledstring' => 'bookingstatus_enabled_confirmed',
                'subjectstring' => 'bookingstatus_subject_confirmed',
                'bodystring' => 'bookingstatus_body_confirmed',
            ],
            'canceled' => [
                'id' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'key' => 'canceled',
                'enabledconfig' => 'bookingstatus_enabled_canceled',
                'subjectconfig' => 'bookingstatus_subject_canceled',
                'bodyconfig' => 'bookingstatus_body_canceled',
                'enabledstring' => 'bookingstatus_enabled_canceled',
                'subjectstring' => 'bookingstatus_subject_canceled',
                'bodystring' => 'bookingstatus_body_canceled',
            ],
            'rejected' => [
                'id' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'key' => 'rejected',
                'enabledconfig' => 'bookingstatus_enabled_rejected',
                'subjectconfig' => 'bookingstatus_subject_rejected',
                'bodyconfig' => 'bookingstatus_body_rejected',
                'enabledstring' => 'bookingstatus_enabled_rejected',
                'subjectstring' => 'bookingstatus_subject_rejected',
                'bodystring' => 'bookingstatus_body_rejected',
            ],
        ];
    }

    /**
     * Resolve booking-status notification metadata from a runtime status ID.
     *
     * @param int $statusid
     * @return array{id:int,key:string,enabledconfig:string,subjectconfig:string,bodyconfig:string,
     *     enabledstring:string,subjectstring:string,bodystring:string}|null
     */
    public static function get_booking_status_notification_status_by_id(int $statusid): ?array {
        foreach (self::get_booking_status_notification_statuses() as $statusdata) {
            if ((int)$statusdata['id'] === $statusid) {
                return $statusdata;
            }
        }

        return null;
    }

    /**
     * Normalize a runtime language into the supported template language set.
     *
     * @param string|null $lang
     * @return string
     */
    public static function normalize_booking_status_notification_language(?string $lang): string {
        $lang = core_text::strtolower(trim((string)$lang));
        if ($lang !== '' && str_starts_with($lang, 'de')) {
            return 'de';
        }

        return 'en';
    }

    /**
     * Resolve the canonical shared config key for a booking-status template.
     *
     * @param string $statuskey
     * @param string $field
     * @return string
     */
    public static function get_booking_status_notification_template_config_key(string $statuskey, string $field): string {
        $statuses = self::get_booking_status_notification_statuses();
        if (!isset($statuses[$statuskey])) {
            throw new \coding_exception('Unknown booking-status notification key: ' . $statuskey);
        }

        if ($field === 'subject') {
            return (string)$statuses[$statuskey]['subjectconfig'];
        }

        if ($field === 'body') {
            return (string)$statuses[$statuskey]['bodyconfig'];
        }

        throw new \coding_exception('Unknown booking-status template field: ' . $field);
    }

    /**
     * Get the explicit default subject template for one expressive booking-status key.
     *
     * @param string $statuskey
     * @param string|null $lang
     * @return string
     */
    public static function get_booking_status_notification_default_subject(string $statuskey, ?string $lang = null): string {
        return self::get_booking_status_notification_default_text($statuskey, 'subject', $lang);
    }

    /**
     * Get the explicit default body template for one expressive booking-status key.
     *
     * @param string $statuskey
     * @param string|null $lang
     * @return string
     */
    public static function get_booking_status_notification_default_body(string $statuskey, ?string $lang = null): string {
        return self::get_booking_status_notification_default_text($statuskey, 'body', $lang);
    }

    /**
     * Get the shared localized greeting for booking-status notifications.
     *
     * @param string|null $lang
     * @return string
     */
    public static function get_booking_status_notification_greeting(?string $lang = null): string {
        return self::get_notification_shared_string('bookingstatus_notification_greeting', $lang);
    }

    /**
     * Get the shared localized closing for booking-status notifications.
     *
     * @param string|null $lang
     * @return string
     */
    public static function get_booking_status_notification_closing(?string $lang = null): string {
        return self::get_notification_shared_string('bookingstatus_notification_closing', $lang);
    }

    /**
     * Ensure expressive booking-status notification defaults exist for fresh installs.
     *
     * @return void
     */
    public static function ensure_booking_status_notification_defaults(): void {
        $recipientdefaults = [
            'bookingstatus_notify_serviceteam' => 1,
            'bookingstatus_notify_bookingperson' => 1,
            'bookingstatus_notify_personincharge' => 1,
            'bookingstatus_notify_otherexaminers' => 1,
            'bookingstatus_service_addresses' => '',
        ];
        foreach ($recipientdefaults as $configkey => $defaultvalue) {
            if (get_config('mod_bookit', $configkey) === false) {
                set_config($configkey, $defaultvalue, 'mod_bookit');
            }
        }

        foreach (self::get_booking_status_notification_statuses() as $statuskey => $statusdata) {
            if (get_config('mod_bookit', $statusdata['enabledconfig']) === false) {
                set_config($statusdata['enabledconfig'], 1, 'mod_bookit');
            }

            self::normalize_booking_status_notification_shared_template($statuskey, 'subject');
            self::normalize_booking_status_notification_shared_template($statuskey, 'body');
        }
    }

    /**
     * Ensure one shared notification template field for fresh installs.
     *
     * Missing keys receive shipped defaults. Blank overrides are cleared so shipped
     * defaults remain visible. Non-empty stored values are never rewritten.
     *
     * @param string $statuskey
     * @param string $field
     * @return void
     */
    private static function normalize_booking_status_notification_shared_template(string $statuskey, string $field): void {
        $configkey = self::get_booking_status_notification_template_config_key($statuskey, $field);
        $currentvalue = get_config('mod_bookit', $configkey);

        if ($currentvalue !== false && trim((string)$currentvalue) !== '') {
            return;
        }

        if ($currentvalue !== false) {
            unset_config($configkey, 'mod_bookit');
            return;
        }

        set_config(
            $configkey,
            self::get_booking_status_notification_default_text($statuskey, $field),
            'mod_bookit'
        );
    }

    /**
     * Resolve a localized shipped default notification text.
     *
     * @param string $statuskey
     * @param string $field
     * @param string|null $lang
     * @return string
     */
    private static function get_booking_status_notification_default_text(
        string $statuskey,
        string $field,
        ?string $lang = null
    ): string {
        $statuses = self::get_booking_status_notification_statuses();
        if (!isset($statuses[$statuskey])) {
            throw new \coding_exception('Unknown booking-status notification key: ' . $statuskey);
        }

        if (!in_array($field, ['subject', 'body'], true)) {
            throw new \coding_exception('Unknown booking-status template field: ' . $field);
        }

        $stringkey = 'bookingstatus_' . $field . '_default_' . $statuskey;
        if ($lang === null) {
            return get_string($stringkey, 'mod_bookit');
        }

        $oldlang = force_current_language(self::normalize_booking_status_notification_language($lang));
        $text = get_string($stringkey, 'mod_bookit');
        force_current_language($oldlang);

        return $text;
    }

    /**
     * Resolve one shared localized notification string.
     *
     * @param string $stringkey
     * @param string|null $lang
     * @return string
     */
    private static function get_notification_shared_string(string $stringkey, ?string $lang = null): string {
        if ($lang === null) {
            return get_string($stringkey, 'mod_bookit');
        }

        $oldlang = force_current_language(self::normalize_booking_status_notification_language($lang));
        $text = get_string($stringkey, 'mod_bookit');
        force_current_language($oldlang);

        return $text;
    }

    /**
     * Return all shipped role preset file names.
     *
     * @return string[]
     */
    public static function get_default_role_preset_filenames(): array {
        return [
            'bookit_bookingperson.xml',
            'bookit_examiner.xml',
            'bookit_observer.xml',
            'bookit_serviceteam.xml',
            'bookit_supportonsite.xml',
        ];
    }

    /**
     * Ensure the agreed fresh-install baseline exists exactly once.
     *
     * @param bool $verbose
     * @return array{status:string,operations:array<string,array>,errors:string[]}
     */
    public static function ensure_fresh_install_baseline(bool $verbose = false): array {
        $operations = [
            'institution' => self::ensure_default_institution($verbose),
            'room' => self::ensure_default_room($verbose),
        ];
        $operations['weekplan'] = self::ensure_default_weekplan((int)($operations['room']['id'] ?? 0), $verbose);

        $errors = [];
        foreach ($operations as $operation) {
            if (!empty($operation['message']) && in_array($operation['status'], ['failed', 'partial'], true)) {
                $errors[] = $operation['message'];
            }
        }

        return [
            'status' => self::resolve_report_status(array_column($operations, 'status'), $errors),
            'operations' => $operations,
            'errors' => $errors,
        ];
    }

    /**
     * Import shipped role presets with explicit per-file outcomes.
     *
     * @param bool $force
     * @param bool $verbose
     * @return array{status:string,imported:string[],skipped:string[],errors:string[]}
     */
    public static function import_default_roles_with_report(bool $force = false, bool $verbose = false): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/accesslib.php');
        require_once($CFG->dirroot . '/admin/roles/classes/preset.php');

        $rolesdir = $CFG->dirroot . '/mod/bookit/assets/roles/';
        $imported = [];
        $skipped = [];
        $errors = [];

        foreach (self::get_default_role_preset_filenames() as $filename) {
            $fullpath = $rolesdir . $filename;
            if ($verbose) {
                mtrace('Processing role file: ' . $filename);
            }

            if (!is_readable($fullpath)) {
                $errors[] = 'Role preset file missing: ' . $filename;
                continue;
            }

            $xml = file_get_contents($fullpath);
            if ($xml === false || $xml === '') {
                $errors[] = 'Role preset file could not be read: ' . $filename;
                continue;
            }

            if (!\core_role_preset::is_valid_preset($xml)) {
                $errors[] = 'Invalid role preset XML: ' . $filename;
                continue;
            }

            $roleinfo = \core_role_preset::parse_preset($xml);
            if (!$roleinfo) {
                $errors[] = 'Role preset XML could not be parsed: ' . $filename;
                continue;
            }

            $roleid = null;
            if ($existingrole = $DB->get_record('role', ['shortname' => $roleinfo['shortname']])) {
                if (!$force) {
                    $skipped[] = $roleinfo['shortname'];
                    if ($verbose) {
                        mtrace('Role already exists and stays unchanged: ' . $roleinfo['shortname']);
                    }
                    continue;
                }
                $roleid = (int)$existingrole->id;
            } else {
                $roleid = (int)create_role(
                    $roleinfo['name'],
                    $roleinfo['shortname'],
                    $roleinfo['description'],
                    $roleinfo['archetype']
                );
            }

            if (isset($roleinfo['contextlevels']) && is_array($roleinfo['contextlevels'])) {
                $DB->delete_records('role_context_levels', ['roleid' => $roleid]);
                foreach ($roleinfo['contextlevels'] as $contextlevel) {
                    $DB->insert_record('role_context_levels', [
                        'roleid' => $roleid,
                        'contextlevel' => $contextlevel,
                    ]);
                }
            }

            if (isset($roleinfo['permissions']) && is_array($roleinfo['permissions'])) {
                $systemcontext = \context_system::instance();
                foreach ($roleinfo['permissions'] as $capability => $permission) {
                    $DB->delete_records('role_capabilities', [
                        'roleid' => $roleid,
                        'capability' => $capability,
                        'contextid' => $systemcontext->id,
                    ]);
                    if ($permission != CAP_INHERIT) {
                        $DB->insert_record('role_capabilities', [
                            'roleid' => $roleid,
                            'capability' => $capability,
                            'permission' => $permission,
                            'contextid' => $systemcontext->id,
                            'timemodified' => time(),
                        ]);
                    }
                }
            }

            foreach (['assign', 'override', 'switch', 'view'] as $type) {
                if (!isset($roleinfo['allow' . $type]) || !is_array($roleinfo['allow' . $type])) {
                    continue;
                }
                $DB->delete_records('role_allow_' . $type, ['roleid' => $roleid]);
                $allowtargets = self::normalise_role_relation_targets($roleinfo['allow' . $type], $roleid);
                foreach ($allowtargets as $allowid) {
                    $DB->insert_record('role_allow_' . $type, [
                        'roleid' => $roleid,
                        'allow' . $type => $allowid,
                    ]);
                }
            }

            $imported[] = $roleinfo['shortname'];
            if ($verbose) {
                mtrace('Imported role preset: ' . $roleinfo['shortname']);
            }
        }

        return [
            'status' => self::resolve_report_status([
                empty($imported) ? 'idempotent' : 'success',
            ], $errors, !empty($imported), !empty($skipped)),
            'imported' => array_values(array_unique($imported)),
            'skipped' => array_values(array_unique($skipped)),
            'errors' => $errors,
        ];
    }

    /**
     * Normalise role-relation targets so self-links and resolved role IDs stay idempotent on re-import.
     *
     * Moodle role presets may encode "self" as -1 while also resolving the current role shortname to the
     * concrete role ID on force re-import. Collapse both representations to one unique target ID before
     * writing the role_allow_* tables.
     *
     * @param array $targets
     * @param int $roleid
     * @return int[]
     */
    private static function normalise_role_relation_targets(array $targets, int $roleid): array {
        $normalised = [];
        foreach ($targets as $target) {
            $targetid = (int)$target;
            if ($targetid === -1) {
                $targetid = $roleid;
            }
            if ($targetid <= 0) {
                continue;
            }
            $normalised[] = $targetid;
        }

        return array_values(array_unique($normalised));
    }

    /**
     * Create default checklist data during installation.
     *
     * @param bool $force Force creation even if data exists
     * @param bool $verbose Print verbose output
     * @return bool True if data was created, false otherwise
     */
    public static function create_default_checklists(bool $force = false, bool $verbose = false): bool {
        global $DB;

        // Check if complete checklist data already exists (master + categories + items).
        $existingmaster = $DB->count_records('bookit_checklist_master');
        $existingcategories = $DB->count_records('bookit_checklist_category');
        $existingitems = $DB->count_records('bookit_checklist_item');
        $existingrooms = $DB->count_records('bookit_room');

        if ($existingmaster > 0 && $existingcategories > 0 && $existingitems > 0 && $existingrooms > 0 && !$force) {
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

        // Get or create master checklist.
        $masterid = null;
        $master = null;

        // Try to find existing master checklist first.
        $existingmasters = $DB->get_records('bookit_checklist_master', ['isdefault' => 1]);
        if (!empty($existingmasters)) {
            $master = reset($existingmasters);
            $masterid = $master->id;
            if ($verbose) {
                mtrace("Using existing master checklist with ID: $masterid");
            }
        } else {
            // Create new master checklist.
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

            if ($verbose) {
                mtrace("Created master checklist with ID: $masterid");
            }
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
     * Import default roles from XML files in assets/roles/ directory.
     *
     * @param bool $force Force import even if role already exists
     * @param bool $verbose Print verbose output
     * @return bool True if at least one role was imported, false otherwise
     */
    public static function import_default_roles(bool $force = false, bool $verbose = false): bool {
        $report = self::import_default_roles_with_report($force, $verbose);
        return !empty($report['imported']);
    }

    /**
     * Create default rooms for testing purposes.
     *
     * @param bool $force Force creation even if rooms exist
     * @param bool $verbose Print verbose output
     * @return bool True if rooms were created, false otherwise
     */
    public static function create_default_rooms(bool $force = false, bool $verbose = false): bool {
        $result = self::ensure_default_room($verbose);
        return $result['status'] === 'created';
    }

    /**
     * Create default resource categories and resources during installation.
     *
     * @param bool $force Force creation even if data exists
     * @param bool $verbose Print verbose output
     * @return bool True if data was created, false otherwise
     */
    public static function create_default_resources(bool $force = false, bool $verbose = false): bool {
        global $DB;

        // Check if resources already exist (not just categories).
        $existingresources = $DB->count_records('bookit_resource');
        if ($existingresources > 0 && !$force) {
            if ($verbose) {
                mtrace('Resource data already exists. Skipping creation.');
            }
            return false;
        }

        if ($verbose) {
            mtrace('Creating default resource data for BookIt...');
        }

        // Define category data.
        $categories = [
            [
                'name' => 'Technical Equipment',
                'description' => 'Technical devices and equipment for events',
                'sortorder' => 1,
            ],
            [
                'name' => 'Furniture & Setup',
                'description' => 'Furniture and room setup items',
                'sortorder' => 2,
            ],
            [
                'name' => 'Catering & Supplies',
                'description' => 'Food, beverages, and disposable items',
                'sortorder' => 3,
            ],
        ];

        // Define resource data per category.
        $resources = [
            'Technical Equipment' => [
                ['name' => 'Projector', 'amount' => 5, 'amountirrelevant' => false],
                ['name' => 'Microphone', 'amount' => 10, 'amountirrelevant' => false],
                ['name' => 'Laptop', 'amount' => 8, 'amountirrelevant' => false],
                ['name' => 'HDMI Cable', 'amount' => 15, 'amountirrelevant' => false],
                ['name' => 'Extension Cord', 'amount' => 20, 'amountirrelevant' => false],
            ],
            'Furniture & Setup' => [
                ['name' => 'Tables', 'amount' => 50, 'amountirrelevant' => false],
                ['name' => 'Chairs', 'amount' => 200, 'amountirrelevant' => false],
                ['name' => 'Flip Chart', 'amount' => 6, 'amountirrelevant' => false],
                ['name' => 'Whiteboard', 'amount' => 4, 'amountirrelevant' => false],
                ['name' => 'Banner Stand', 'amount' => 3, 'amountirrelevant' => false],
            ],
            'Catering & Supplies' => [
                ['name' => 'Coffee Service', 'amount' => 1, 'amountirrelevant' => true],
                ['name' => 'Water Bottles', 'amount' => 100, 'amountirrelevant' => false],
                ['name' => 'Cups (disposable)', 'amount' => 200, 'amountirrelevant' => false],
                ['name' => 'Napkins', 'amount' => 500, 'amountirrelevant' => false],
                ['name' => 'Catering Setup', 'amount' => 1, 'amountirrelevant' => true],
            ],
        ];

        // Create or get existing categories.
        $categoryids = [];
        $categorymap = []; // Name => ID mapping.

        // Get all existing categories.
        $existingcats = $DB->get_records('bookit_resource_category');
        $existingcatsbyname = [];
        foreach ($existingcats as $cat) {
            $existingcatsbyname[$cat->name] = $cat;
        }

        foreach ($categories as $catdata) {
            // Check if category already exists.
            if (isset($existingcatsbyname[$catdata['name']])) {
                $existingcat = $existingcatsbyname[$catdata['name']];
                if ($verbose) {
                    mtrace("Using existing resource category: {$catdata['name']} (ID: {$existingcat->id})");
                }
                $categoryids[] = $existingcat->id;
                $categorymap[$catdata['name']] = $existingcat->id;
            } else {
                if ($verbose) {
                    mtrace("Creating resource category: {$catdata['name']}");
                }

                $category = new bookit_resource_category(
                    null, // ID.
                    $catdata['name'],
                    $catdata['description'],
                    $catdata['sortorder'],
                    true, // Active.
                    time(), // Timecreated.
                    time(), // Timemodified.
                    2 // Usermodified (admin).
                );

                $categoryid = resource_manager::save_category($category, 2);
                $categoryids[] = $categoryid;
                $categorymap[$catdata['name']] = $categoryid;

                if ($verbose) {
                    mtrace("  Category ID: $categoryid");
                }
            }
        }

        // Get available rooms for assignment.
        $rooms = resource_manager::get_rooms();
        $allroomids = [];
        if (!empty($rooms)) {
            $allroomids = array_keys($rooms);
        }

        // Create resources.
        $resourcescreated = 0;

        foreach ($resources as $categoryname => $items) {
            $categoryid = $categorymap[$categoryname];

            if ($verbose) {
                mtrace("Creating resources for category: $categoryname (ID: $categoryid)");
            }

            $sortorder = 1;
            foreach ($items as $itemdata) {
                // Assign random subset of rooms to each resource (similar to checklist logic).
                $roomids = [];
                if (!empty($allroomids)) {
                    $roomids = $allroomids;

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

                $resource = new bookit_resource(
                    null, // ID.
                    $itemdata['name'],
                    null, // No description for dummy data.
                    $categoryid,
                    $itemdata['amount'],
                    $itemdata['amountirrelevant'],
                    $sortorder,
                    true, // Active.
                    !empty($roomids) ? $roomids : null, // Roomids.
                    time(), // Timecreated.
                    time(), // Timemodified.
                    2 // Usermodified (admin).
                );

                $resourceid = resource_manager::save_resource($resource, 2);
                $resourcescreated++;

                if ($verbose) {
                    $roomsinfo = !empty($roomids) ? ' (Rooms: ' . count($roomids) . ')' : '';
                    mtrace("  Resource: {$itemdata['name']} (ID: $resourceid, Amount: {$itemdata['amount']}{$roomsinfo})");
                }

                $sortorder++;
            }
        }

        if ($verbose) {
            mtrace("Successfully created " . count($categoryids) . " categories and $resourcescreated resources!");
        }

        return true;
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

    /**
     * Create the baseline default weekplan and assign the default room to it.
     *
     * @param bool $force Force creation even if weekplan already exists
     * @param bool $verbose Print verbose output
     * @return bool True if weekplan was created, false otherwise
     */
    public static function create_default_weekplan(bool $force = false, bool $verbose = false): bool {
        global $DB;

        $roomid = (int)$DB->get_field_sql('SELECT MIN(id) FROM {bookit_room}');
        $result = self::ensure_default_weekplan($roomid, $verbose);
        return $result['status'] === 'created';
    }

    /**
     * Ensure exactly one default institution exists.
     *
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function ensure_default_institution(bool $verbose = false): array {
        global $DB;

        $institutions = $DB->get_records('bookit_institution');
        if (empty($institutions)) {
            return self::create_default_institution($verbose);
        }

        if (count($institutions) === 1) {
            $institution = reset($institutions);
            if ((string)$institution->name === self::DEFAULT_INSTITUTION_NAME) {
                return self::sync_default_institution((int)$institution->id, $verbose);
            }
        }

        return [
            'status' => 'failed',
            'id' => null,
            'message' => 'Fresh-install baseline requires exactly one active institution named "' .
                self::DEFAULT_INSTITUTION_NAME . '".',
        ];
    }

    /**
     * Ensure exactly one default room exists.
     *
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function ensure_default_room(bool $verbose = false): array {
        global $DB;

        $rooms = $DB->get_records('bookit_room');
        if (empty($rooms)) {
            return self::create_default_room($verbose);
        }

        if (count($rooms) === 1) {
            $roomrecord = reset($rooms);
            if ((string)$roomrecord->name === self::DEFAULT_ROOM_NAME) {
                return self::sync_default_room((int)$roomrecord->id, $verbose);
            }
        }

        return [
            'status' => 'failed',
            'id' => null,
            'message' => 'Fresh-install baseline requires exactly one active room named "' . self::DEFAULT_ROOM_NAME . '".',
        ];
    }

    /**
     * Ensure exactly one default weekplan exists and is assigned to the default room.
     *
     * @param int $roomid
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function ensure_default_weekplan(int $roomid, bool $verbose = false): array {
        global $DB;

        if ($roomid <= 0) {
            return ['status' => 'failed', 'id' => null, 'message' => 'Default weekplan requires an existing default room.'];
        }

        $weekplans = $DB->get_records('bookit_weekplan');
        if (empty($weekplans)) {
            return self::create_default_weekplan_baseline($roomid, $verbose);
        }

        if (count($weekplans) === 1) {
            $weekplan = reset($weekplans);
            if ((string)$weekplan->name === self::DEFAULT_WEEKPLAN_NAME) {
                return self::sync_default_weekplan((int)$weekplan->id, $roomid, $verbose);
            }
        }

        return [
            'status' => 'failed',
            'id' => null,
            'message' => 'Fresh-install baseline requires exactly one weekplan named "' .
                self::DEFAULT_WEEKPLAN_NAME . '" with Monday-Friday 09:00-17:00 slots.',
        ];
    }

    /**
     * Look up a single record by a text field using Moodle's text comparison helper.
     *
     * @param string $table
     * @param string $field
     * @param string $value
     * @return object|false
     */
    private static function find_record_by_text_field(string $table, string $field, string $value) {
        global $DB;

        $sql = "SELECT *
                  FROM {" . $table . "}
                 WHERE " . $DB->sql_compare_text($field) . " = " . $DB->sql_compare_text(':value');

        return $DB->get_record_sql($sql, ['value' => $value]);
    }

    /**
     * Create the default institution.
     *
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function create_default_institution(bool $verbose = false): array {
        global $DB;

        $record = self::default_institution_record();
        $id = (int)$DB->insert_record('bookit_institution', $record);
        if ($verbose) {
            mtrace('Created default institution: ' . self::DEFAULT_INSTITUTION_NAME . ' (ID: ' . $id . ')');
        }

        return ['status' => 'created', 'id' => $id, 'message' => null];
    }

    /**
     * Create the default room.
     *
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function create_default_room(bool $verbose = false): array {
        global $DB;

        $record = self::default_room_record();
        $id = (int)$DB->insert_record('bookit_room', $record);
        if ($verbose) {
            mtrace('Created default room: ' . self::DEFAULT_ROOM_NAME . ' (ID: ' . $id . ')');
        }

        return ['status' => 'created', 'id' => $id, 'message' => null];
    }

    /**
     * Create the default weekplan and assign it to the default room.
     *
     * @param int $roomid
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function create_default_weekplan_baseline(int $roomid, bool $verbose = false): array {
        global $DB;

        $record = self::default_weekplan_record();
        $weekplanid = (int)$DB->insert_record('bookit_weekplan', $record);
        weekplan_manager::save_string_weekplan_to_db(self::DEFAULT_WEEKPLAN_SCHEDULE, $weekplanid);
        self::ensure_weekplan_room_assignment($weekplanid, $roomid);
        if ($verbose) {
            mtrace('Created default weekplan: ' . self::DEFAULT_WEEKPLAN_NAME . ' (ID: ' . $weekplanid . ')');
        }

        return ['status' => 'created', 'id' => $weekplanid, 'message' => null];
    }

    /**
     * Ensure the default institution matches the agreed baseline profile.
     *
     * @param int $institutionid
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function sync_default_institution(int $institutionid, bool $verbose = false): array {
        global $DB;

        $institution = $DB->get_record('bookit_institution', ['id' => $institutionid], '*', MUST_EXIST);
        $record = self::default_institution_record();
        $updaterecord = (object)['id' => $institutionid];
        $haschanges = false;

        foreach (['name', 'internalnotes', 'active'] as $field) {
            if (!self::baseline_values_match($institution->$field ?? null, $record->$field ?? null)) {
                $updaterecord->$field = $record->$field;
                $haschanges = true;
            }
        }

        if ($haschanges) {
            $updaterecord->usermodified = self::get_actor_userid();
            $updaterecord->timemodified = time();
            $DB->update_record('bookit_institution', $updaterecord);
            if ($verbose) {
                mtrace('Updated default institution baseline: ' . self::DEFAULT_INSTITUTION_NAME . ' (ID: ' . $institutionid . ')');
            }
            return ['status' => 'created', 'id' => $institutionid, 'message' => null];
        }

        return ['status' => 'idempotent', 'id' => $institutionid, 'message' => null];
    }

    /**
     * Ensure the default room matches the agreed baseline profile.
     *
     * @param int $roomid
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function sync_default_room(int $roomid, bool $verbose = false): array {
        global $DB;

        $roomrecord = $DB->get_record('bookit_room', ['id' => $roomid], '*', MUST_EXIST);
        $record = self::default_room_record();
        $updaterecord = (object)['id' => $roomid];
        $haschanges = false;

        $fields = [
            'name', 'shortname', 'description', 'location', 'eventcolor', 'active', 'roommode', 'seats',
            'extratimebefore', 'extratimeafter', 'preventoverlap',
        ];
        foreach ($fields as $field) {
            if (!self::baseline_values_match($roomrecord->$field ?? null, $record->$field ?? null)) {
                $updaterecord->$field = $record->$field;
                $haschanges = true;
            }
        }

        if ($haschanges) {
            $updaterecord->usermodified = self::get_actor_userid();
            $updaterecord->timemodified = time();
            $DB->update_record('bookit_room', $updaterecord);
            if ($verbose) {
                mtrace('Updated default room baseline: ' . self::DEFAULT_ROOM_NAME . ' (ID: ' . $roomid . ')');
            }
            return ['status' => 'created', 'id' => $roomid, 'message' => null];
        }

        return ['status' => 'idempotent', 'id' => $roomid, 'message' => null];
    }

    /**
     * Ensure the default weekplan matches the agreed baseline profile.
     *
     * @param int $weekplanid
     * @param int $roomid
     * @param bool $verbose
     * @return array{status:string,id:int|null,message:string|null}
     */
    private static function sync_default_weekplan(int $weekplanid, int $roomid, bool $verbose = false): array {
        global $DB;

        $weekplan = $DB->get_record('bookit_weekplan', ['id' => $weekplanid], '*', MUST_EXIST);
        $updaterecord = (object)['id' => $weekplanid];
        $haschanges = false;

        if ((string)$weekplan->name !== self::DEFAULT_WEEKPLAN_NAME) {
            $updaterecord->name = self::DEFAULT_WEEKPLAN_NAME;
            $haschanges = true;
        }

        if (!self::has_default_weekplan_schedule($weekplanid)) {
            weekplan_manager::save_string_weekplan_to_db(self::DEFAULT_WEEKPLAN_SCHEDULE, $weekplanid);
            $haschanges = true;
        }

        $assignmentbefore = self::has_weekplan_room_assignment($weekplanid, $roomid);
        self::ensure_weekplan_room_assignment($weekplanid, $roomid);
        if (!$assignmentbefore) {
            $haschanges = true;
        }

        if ($haschanges) {
            $updaterecord->usermodified = self::get_actor_userid();
            $updaterecord->timemodified = time();
            $DB->update_record('bookit_weekplan', $updaterecord);
            if ($verbose) {
                mtrace('Updated default weekplan baseline: ' . self::DEFAULT_WEEKPLAN_NAME . ' (ID: ' . $weekplanid . ')');
            }
            return ['status' => 'created', 'id' => $weekplanid, 'message' => null];
        }

        return ['status' => 'idempotent', 'id' => $weekplanid, 'message' => null];
    }

    /**
     * Build the default institution record payload.
     *
     * @return \stdClass
     */
    private static function default_institution_record(): \stdClass {
        return (object)[
            'name' => self::DEFAULT_INSTITUTION_NAME,
            'internalnotes' => null,
            'active' => 1,
            'usermodified' => self::get_actor_userid(),
            'timecreated' => time(),
            'timemodified' => time(),
        ];
    }

    /**
     * Build the default room record payload.
     *
     * @return \stdClass
     */
    private static function default_room_record(): \stdClass {
        return (object)[
            'name' => self::DEFAULT_ROOM_NAME,
            'shortname' => '',
            'description' => self::DEFAULT_ROOM_DESCRIPTION,
            'location' => '',
            'eventcolor' => '',
            'active' => 1,
            'roommode' => room::MODE_FREE,
            'seats' => 0,
            'extratimebefore' => null,
            'extratimeafter' => null,
            'preventoverlap' => room::OVERLAPPING_ALLOW_NONE,
            'usermodified' => self::get_actor_userid(),
            'timecreated' => time(),
            'timemodified' => time(),
        ];
    }

    /**
     * Build the default weekplan record payload.
     *
     * @return \stdClass
     */
    private static function default_weekplan_record(): \stdClass {
        return (object)[
            'name' => self::DEFAULT_WEEKPLAN_NAME,
            'usermodified' => self::get_actor_userid(),
            'timecreated' => time(),
            'timemodified' => time(),
        ];
    }

    /**
     * Compare persisted baseline values while ignoring database scalar type differences.
     *
     * @param mixed $current
     * @param mixed $expected
     * @return bool
     */
    private static function baseline_values_match(mixed $current, mixed $expected): bool {
        if ($current === $expected) {
            return true;
        }

        if ($current === null || $expected === null) {
            return $current === $expected;
        }

        if (is_numeric($current) && is_numeric($expected)) {
            return (int)$current === (int)$expected;
        }

        return (string)$current === (string)$expected;
    }

    /**
     * Ensure the default room assignment exists for the weekplan.
     *
     * @param int $weekplanid
     * @param int $roomid
     * @return void
     */
    private static function ensure_weekplan_room_assignment(int $weekplanid, int $roomid): void {
        global $DB;

        if ($DB->record_exists('bookit_weekplan_room', ['weekplanid' => $weekplanid, 'roomid' => $roomid])) {
            return;
        }

        $DB->insert_record('bookit_weekplan_room', [
            'weekplanid' => $weekplanid,
            'roomid' => $roomid,
            'starttime' => time() - DAYSECS,
            'endtime' => null,
            'usermodified' => self::get_actor_userid(),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Check whether the weekplan-room assignment already exists.
     *
     * @param int $weekplanid
     * @param int $roomid
     * @return bool
     */
    private static function has_weekplan_room_assignment(int $weekplanid, int $roomid): bool {
        global $DB;

        return $DB->record_exists('bookit_weekplan_room', ['weekplanid' => $weekplanid, 'roomid' => $roomid]);
    }

    /**
     * Check whether the stored weekplan matches the agreed standalone schedule.
     *
     * @param int $weekplanid
     * @return bool
     */
    private static function has_default_weekplan_schedule(int $weekplanid): bool {
        global $DB;

        [, $expectedslots] = weekplan_manager::parse_weekplan(self::DEFAULT_WEEKPLAN_SCHEDULE);
        $actualslots = $DB->get_records('bookit_weekplanslot', ['weekplanid' => $weekplanid], 'starttime ASC', 'starttime,endtime');

        if (count($actualslots) !== count($expectedslots)) {
            return false;
        }

        $index = 0;
        foreach ($actualslots as $slot) {
            if (
                (int)$slot->starttime !== (int)$expectedslots[$index][0] ||
                    (int)$slot->endtime !== (int)$expectedslots[$index][1]
            ) {
                return false;
            }
            $index++;
        }

        return true;
    }

    /**
     * Resolve a high-level report status from operation outcomes.
     *
     * @param string[] $statuses
     * @param string[] $errors
     * @param bool $hassuccess
     * @param bool $hasidempotent
     * @return string
     */
    private static function resolve_report_status(
        array $statuses,
        array $errors = [],
        bool $hassuccess = false,
        bool $hasidempotent = false
    ): string {
        $statuses = array_values(array_unique($statuses));
        if (!empty($errors)) {
            return ($hassuccess || $hasidempotent || count($statuses) > 1) ? 'partial' : 'failed';
        }
        if (in_array('created', $statuses, true) || $hassuccess) {
            return 'success';
        }
        return 'idempotent';
    }

    /**
     * Resolve the user id that should be written into fresh-install baseline records.
     *
     * @return int
     */
    private static function get_actor_userid(): int {
        global $USER;

        if (!empty($USER->id)) {
            return (int)$USER->id;
        }

        $admin = get_admin();
        if (!empty($admin->id)) {
            return (int)$admin->id;
        }

        return 2;
    }

    /**
     * Create a demo Moodle course with a BookIt activity instance and enrol demo users.
     *
     * @param bool $force Force creation even if demo course already exists
     * @param bool $verbose Print verbose output
     * @return bool True if course and activity were created, false otherwise
     */
    public static function create_default_course_and_activity(bool $force = false, bool $verbose = false): bool {
        global $DB, $CFG, $USER;

        if ($DB->record_exists('course', ['shortname' => 'BOOKIT-DEMO']) && !$force) {
            if ($verbose) {
                mtrace('Demo course already exists. Skipping creation.');
            }
            return false;
        }

        require_once($CFG->dirroot . '/course/lib.php');

        if ($verbose) {
            mtrace('Creating demo course and BookIt activity...');
        }

        $categoryid = $DB->get_field('course_categories', 'id', ['parent' => 0]);
        if (!$categoryid) {
            $categoryid = 1;
        }

        $coursedata = new \stdClass();
        $coursedata->fullname = 'Calendar Demo Course';
        $coursedata->shortname = 'BOOKIT-DEMO';
        $coursedata->category = $categoryid;
        $coursedata->summary = 'Demo course for testing Calendar resource booking.';
        $coursedata->summaryformat = FORMAT_HTML;
        $coursedata->format = 'topics';
        $coursedata->newsitems = 0;
        $coursedata->visible = 1;
        $coursedata->startdate = time() - (30 * DAYSECS);
        $coursedata->enddate = time() + (365 * DAYSECS);
        $course = create_course($coursedata);

        if ($verbose) {
            mtrace("Created course: {$course->fullname} (ID: {$course->id})");
        }

        // Create the bookit module instance.
        $bookit = new \stdClass();
        $bookit->course = $course->id;
        $bookit->name = 'Calendar Demo';
        $bookit->intro = '<p>Calendar demo activity for testing resource booking.</p>';
        $bookit->introformat = FORMAT_HTML;
        $bookit->timecreated = time();
        $bookit->timemodified = time();
        $bookitid = $DB->insert_record('bookit', $bookit);

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'bookit']);
        if (!$moduleid) {
            if ($verbose) {
                mtrace('ERROR: bookit module not registered in modules table.');
            }
            return false;
        }

        $sectionid = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 0]);

        $cm = new \stdClass();
        $cm->course = $course->id;
        $cm->module = $moduleid;
        $cm->instance = $bookitid;
        $cm->section = $sectionid;
        $cm->added = time();
        $cm->visible = 1;
        $cm->visibleold = 1;
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->completion = 0;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->showdescription = 0;
        $cm->idnumber = '';
        $cmid = $DB->insert_record('course_modules', $cm);

        $section = $DB->get_record('course_sections', ['id' => $sectionid]);
        $section->sequence = empty($section->sequence) ? (string)$cmid : $section->sequence . ',' . $cmid;
        $DB->update_record('course_sections', $section);

        rebuild_course_cache($course->id, true);

        if ($verbose) {
            mtrace("Created Calendar activity: Calendar Demo (ID: $bookitid, cmid: $cmid)");
        }

        // Enrol demo users as students.
        if (!$DB->record_exists('enrol', ['enrol' => 'manual', 'courseid' => $course->id])) {
            $enrolplugin = enrol_get_plugin('manual');
            $enrolplugin->add_instance($course);
        }
        $enrolinstance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $course->id]);
        $enrolplugin = enrol_get_plugin('manual');
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $demonames = [
            'eva.examiner',
            'bob.booker',
            'susi.serviceteam',
            'steven.support',
            'olaf.observer',
        ];
        foreach ($demonames as $username) {
            $user = $DB->get_record('user', ['username' => $username]);
            if ($user && $enrolinstance && $studentroleid) {
                $enrolplugin->enrol_user($enrolinstance, $user->id, $studentroleid);
                if ($verbose) {
                    mtrace("  Enrolled user: $username");
                }
            }
        }

        return true;
    }

    /**
     * Create demo booking events with resource requests for next week.
     *
     * @param bool $force Force creation even if events already exist
     * @param bool $verbose Print verbose output
     * @return bool True if events were created, false otherwise
     */
    public static function create_default_events(bool $force = false, bool $verbose = false): bool {
        global $DB, $USER;

        if ($DB->count_records('bookit_event') > 0 && !$force) {
            if ($verbose) {
                mtrace('Events already exist. Skipping creation.');
            }
            return false;
        }

        if ($verbose) {
            mtrace('Creating demo booking events...');
        }

        $rooms = array_values($DB->get_records('bookit_room', ['active' => 1]));
        if (empty($rooms)) {
            if ($verbose) {
                mtrace('No rooms found. Run create_default_rooms first.');
            }
            return false;
        }

        $examiner = $DB->get_record('user', ['username' => 'eva.examiner']);
        $booker = $DB->get_record('user', ['username' => 'bob.booker']);
        $examinerid = $examiner ? $examiner->id : ($USER->id ?? 2);
        $bookerid = $booker ? $booker->id : ($USER->id ?? 2);

        $resources = array_values($DB->get_records('bookit_resource', ['active' => 1]));
        $resourcecount = count($resources);

        $nextmonday = strtotime('next Monday');
        $eventlist = [
            [
                'name' => 'Mathematics Exam - Summer Term',
                'start' => $nextmonday + (8 * HOURSECS),
                'end' => $nextmonday + (11 * HOURSECS),
                'roomidx' => 0,
                'participants' => 80,
                'status' => 1,
                'personid' => $examinerid,
                'userid' => $bookerid,
                'resources' => [
                    ['idx' => 0, 'amount' => 5, 'status' => 'requested'],
                    ['idx' => 1, 'amount' => 2, 'status' => 'confirmed'],
                ],
            ],
            [
                'name' => 'Computer Science Exam',
                'start' => $nextmonday + DAYSECS + (10 * HOURSECS),
                'end' => $nextmonday + DAYSECS + (12 * HOURSECS),
                'roomidx' => 1,
                'participants' => 30,
                'status' => 0,
                'personid' => $examinerid,
                'userid' => $bookerid,
                'resources' => [
                    ['idx' => 2, 'amount' => 3, 'status' => 'requested'],
                ],
            ],
            [
                'name' => 'Literature Seminar - Oral Exams',
                'start' => $nextmonday + (2 * DAYSECS) + (14 * HOURSECS),
                'end' => $nextmonday + (2 * DAYSECS) + (18 * HOURSECS),
                'roomidx' => 2,
                'participants' => 15,
                'status' => 1,
                'personid' => $examinerid,
                'userid' => $examinerid,
                'resources' => [],
            ],
        ];

        $eventscreated = 0;
        foreach ($eventlist as $data) {
            $room = $rooms[$data['roomidx'] % count($rooms)];
            $event = new \stdClass();
            $event->name = $data['name'];
            $event->starttime = $data['start'];
            $event->endtime = $data['end'];
            $event->duration = $data['end'] - $data['start'];
            $event->roomid = $room->id;
            $event->participantsamount = $data['participants'];
            $event->bookingstatus = $data['status'];
            $event->personinchargeid = $data['personid'];
            $event->personinchargeid = $data['personid'];
            $event->usercreated = $data['userid'];
            $event->usermodified = $data['userid'];
            $event->timecreated = time();
            $event->timemodified = time();
            $eventid = $DB->insert_record('bookit_event', $event);

            if ($verbose) {
                mtrace("Created event: {$event->name} (ID: $eventid, status: {$event->bookingstatus})");
            }

            foreach ($data['resources'] as $resdata) {
                if ($resourcecount === 0) {
                    continue;
                }
                $resource = $resources[$resdata['idx'] % $resourcecount];
                $er = new \stdClass();
                $er->eventid = $eventid;
                $er->resourceid = $resource->id;
                $er->amount = $resdata['amount'];
                $er->status = $resdata['status'];
                $er->usermodified = $USER->id ?? 2;
                $er->timecreated = time();
                $er->timemodified = time();
                $DB->insert_record('bookit_event_resource', $er);
                if ($verbose) {
                    mtrace("  Resource: {$resource->name} (amount: {$resdata['amount']}, status: {$resdata['status']})");
                }
            }

            $eventscreated++;
        }

        if ($verbose) {
            mtrace("Created $eventscreated demo events.");
        }

        return $eventscreated > 0;
    }
}
