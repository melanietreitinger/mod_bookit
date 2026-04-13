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
 * Resource settings manager class.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use dml_exception;
use mod_bookit\local\entity\resource\bookit_resource_settings;

/**
 * Resource settings manager class.
 *
 * Manages CRUD operations for resource checklist metadata and automatic
 * generation of checklist entries from the resources table.
 */
class resource_settings_manager {
    /**
     * Get all resource checklist items.
     *
     * @return array Array of checklist items with resource data joined
     * @throws dml_exception
     */
    public static function get_all_checklist_items(): array {
        global $DB;

        $sql = "SELECT rc.id, rc.resourceid, rc.duedate, rc.duedatetype,
                       rc.sortorder, rc.beforedueid, rc.whendueid,
                       rc.overdueid, rc.whendoneid,
                       r.name, r.description, r.categoryid, r.amount,
                       r.amountirrelevant, r.active as resource_active
                FROM {bookit_resource_settings} rc
                JOIN {bookit_resource} r ON r.id = rc.resourceid
                ORDER BY rc.sortorder ASC, r.name ASC";

        return $DB->get_records_sql($sql);
    }

    /**
     * Get all checklist items with resource data including roomids.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_all_checklist_items_with_rooms(): array {
        global $DB;

        $sql = "SELECT rc.id, rc.resourceid, rc.duedate, rc.duedatetype,
                       rc.sortorder, rc.beforedueid, rc.whendueid,
                       rc.overdueid, rc.whendoneid,
                       r.name, r.description, r.categoryid, r.amount,
                       r.amountirrelevant, r.active as resource_active,
                       r.roomids
                FROM {bookit_resource_settings} rc
                JOIN {bookit_resource} r ON r.id = rc.resourceid
                ORDER BY rc.sortorder ASC, r.name ASC";

        return $DB->get_records_sql($sql);
    }

    /**
     * Get checklist item by ID.
     *
     * @param int $id Checklist item ID
     * @return bookit_resource_settings|null
     * @throws dml_exception
     */
    public static function get_checklist_item(int $id): ?bookit_resource_settings {
        global $DB;

        $record = $DB->get_record('bookit_resource_settings', ['id' => $id]);
        if (!$record) {
            return null;
        }

        return self::record_to_entity($record);
    }

    /**
     * Get checklist item by resource ID.
     *
     * @param int $resourceid Resource ID
     * @return bookit_resource_settings|null
     * @throws dml_exception
     */
    public static function get_checklist_item_by_resource(int $resourceid): ?bookit_resource_settings {
        global $DB;

        $record = $DB->get_record('bookit_resource_settings', ['resourceid' => $resourceid]);
        if (!$record) {
            return null;
        }

        return self::record_to_entity($record);
    }

    /**
     * Save checklist item (insert or update).
     *
     * @param bookit_resource_settings $item Checklist item
     * @param int $userid User ID
     * @return int Item ID
     * @throws dml_exception
     */
    public static function save_checklist_item(bookit_resource_settings $item, int $userid): int {
        global $DB;

        $record = new \stdClass();
        $record->resourceid = $item->get_resourceid();
        $record->duedate = $item->get_duedate();
        $record->duedatetype = $item->get_duedatetype();
        $record->sortorder = $item->get_sortorder();
        $record->beforedueid = $item->get_beforedueid();
        $record->whendueid = $item->get_whendueid();
        $record->overdueid = $item->get_overdueid();
        $record->whendoneid = $item->get_whendoneid();
        $record->usermodified = $userid;
        $record->timemodified = time();

        if ($item->get_id()) {
            // Update existing.
            $record->id = $item->get_id();
            $DB->update_record('bookit_resource_settings', $record);
            return $item->get_id();
        } else {
            // Insert new.
            $record->timecreated = time();
            $id = $DB->insert_record('bookit_resource_settings', $record);
            return $id;
        }
    }

    /**
     * Delete checklist item.
     *
     * @param int $id Checklist item ID
     * @return bool Success
     * @throws dml_exception
     */
    public static function delete_checklist_item(int $id): bool {
        global $DB;
        return $DB->delete_records('bookit_resource_settings', ['id' => $id]);
    }

    /**
     * Delete checklist item by resource ID.
     *
     * @param int $resourceid Resource ID
     * @return bool Success
     * @throws dml_exception
     */
    public static function delete_checklist_item_by_resource(int $resourceid): bool {
        global $DB;
        return $DB->delete_records('bookit_resource_settings', ['resourceid' => $resourceid]);
    }

    /**
     * Generate checklist entries for all resources that don't have one yet.
     *
     * Creates a checklist entry for each resource in the bookit_resource table
     * that doesn't already have a corresponding entry in bookit_resource_settings.
     *
     * @param int $userid User ID for audit
     * @return int Number of entries created
     * @throws dml_exception
     */
    public static function auto_generate_checklist(int $userid): int {
        global $DB;

        // Find resources without checklist entries.
        $sql = "SELECT r.id
                FROM {bookit_resource} r
                LEFT JOIN {bookit_resource_settings} rc ON rc.resourceid = r.id
                WHERE rc.id IS NULL
                ORDER BY r.name ASC";

        $resources = $DB->get_records_sql($sql);

        if (empty($resources)) {
            return 0;
        }

        $count = 0;
        $time = time();

        // Get max sortorder to append new items.
        $maxsortorder = $DB->get_field_sql(
            "SELECT MAX(sortorder) FROM {bookit_resource_settings}"
        );
        $sortorder = $maxsortorder ? $maxsortorder + 1 : 0;

        foreach ($resources as $resource) {
            $record = new \stdClass();
            $record->resourceid = $resource->id;
            $record->duedate = null;
            $record->duedatetype = null;
            $record->sortorder = $sortorder++;
            $record->beforedueid = null;
            $record->whendueid = null;
            $record->overdueid = null;
            $record->whendoneid = null;
            $record->usermodified = $userid;
            $record->timecreated = $time;
            $record->timemodified = $time;

            $DB->insert_record('bookit_resource_settings', $record);
            $count++;
        }

        return $count;
    }

    /**
     * Create checklist entry for a specific resource.
     *
     * @param int $resourceid Resource ID
     * @param int $userid User ID
     * @return int Checklist item ID
     * @throws dml_exception
     */
    public static function create_checklist_for_resource(int $resourceid, int $userid): int {
        global $DB;

        // Return existing record ID if already exists (idempotent).
        $existing = $DB->get_field('bookit_resource_settings', 'id', ['resourceid' => $resourceid]);
        if ($existing) {
            return (int)$existing;
        }

        // Find alphabetically correct sortorder based on resource name.
        $resourcename = $DB->get_field('bookit_resource', 'name', ['id' => $resourceid]);
        if ($resourcename === false) {
            $resourcename = '';
        }

        $existingcount = $DB->count_records('bookit_resource_settings');

        if ($existingcount === 0) {
            $sortorder = 0;
        } else {
            // Count resources that come alphabetically before the new one.
            $position = (int)$DB->count_records_sql(
                "SELECT COUNT(*) FROM {bookit_resource_settings} rc
                 JOIN {bookit_resource} r ON r.id = rc.resourceid
                 WHERE UPPER(r.name) < UPPER(:name)",
                ['name' => $resourcename]
            );
            // Shift existing items at position and above to make room.
            $DB->execute(
                "UPDATE {bookit_resource_settings} SET sortorder = sortorder + 1 WHERE sortorder >= ?",
                [$position]
            );
            $sortorder = $position;
        }

        $record = new \stdClass();
        $record->resourceid = $resourceid;
        $record->duedate = null;
        $record->duedatetype = null;
        $record->sortorder = $sortorder;
        $record->beforedueid = null;
        $record->whendueid = null;
        $record->overdueid = null;
        $record->whendoneid = null;
        $record->usermodified = $userid;
        $record->timecreated = time();
        $record->timemodified = time();

        return $DB->insert_record('bookit_resource_settings', $record);
    }

    /**
     * Convert database record to entity object.
     *
     * @param \stdClass $record Database record
     * @return bookit_resource_settings
     */
    private static function record_to_entity(\stdClass $record): bookit_resource_settings {
        return new bookit_resource_settings(
            (int)$record->id,
            (int)$record->resourceid,
            isset($record->duedate) ? (int)$record->duedate : null,
            $record->duedatetype ?? null,
            (int)($record->sortorder ?? 0),
            isset($record->beforedueid) ? (int)$record->beforedueid : null,
            isset($record->whendueid) ? (int)$record->whendueid : null,
            isset($record->overdueid) ? (int)$record->overdueid : null,
            isset($record->whendoneid) ? (int)$record->whendoneid : null,
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0),
            (int)($record->usermodified ?? 0)
        );
    }
}
