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
 * Event-resource relationship manager.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use dml_exception;
use mod_bookit\local\entity\resource\bookit_event_resource;
use mod_bookit\local\entity\resource\bookit_resource_status;

/**
 * Event-resource relationship manager.
 *
 * Manages CRUD operations for event-resource relationships (junction table).
 */
class event_resource_manager {
    /**
     * Get all resources for an event.
     *
     * @param int $eventid Event ID
     * @return array Array of event_resource entities
     * @throws dml_exception
     */
    public static function get_resources_for_event(int $eventid): array {
        global $DB;

        $records = $DB->get_records('bookit_event_resource', ['eventid' => $eventid]);

        $entities = [];
        foreach ($records as $record) {
            $entities[] = self::record_to_entity($record);
        }

        return $entities;
    }

    /**
     * Get all events using a resource.
     *
     * @param int $resourceid Resource ID
     * @return array Array of event_resource entities
     * @throws dml_exception
     */
    public static function get_events_for_resource(int $resourceid): array {
        global $DB;

        $records = $DB->get_records('bookit_event_resource', ['resourceid' => $resourceid]);

        $entities = [];
        foreach ($records as $record) {
            $entities[] = self::record_to_entity($record);
        }

        return $entities;
    }

    /**
     * Get a specific event-resource relationship.
     *
     * @param int $eventid Event ID
     * @param int $resourceid Resource ID
     * @return bookit_event_resource|null
     * @throws dml_exception
     */
    public static function get_event_resource(int $eventid, int $resourceid): ?bookit_event_resource {
        global $DB;

        $record = $DB->get_record('bookit_event_resource', [
            'eventid' => $eventid,
            'resourceid' => $resourceid,
        ]);

        if (!$record) {
            return null;
        }

        return self::record_to_entity($record);
    }

    /**
     * Add a resource to an event.
     *
     * Note: When adding multiple resources at once, callers should wrap the calls
     * in a database transaction using $DB->start_delegated_transaction().
     *
     * @param int $eventid Event ID
     * @param int $resourceid Resource ID
     * @param int $amount Amount
     * @param int $userid User ID
     * @param bookit_resource_status $status Status
     * @return int Record ID
     * @throws dml_exception
     */
    public static function add_resource_to_event(
        int $eventid,
        int $resourceid,
        int $amount,
        int $userid,
        bookit_resource_status $status = bookit_resource_status::REQUESTED
    ): int {
        global $DB;

        $time = time();
        $record = new \stdClass();
        $record->eventid = $eventid;
        $record->resourceid = $resourceid;
        $record->amount = $amount;
        $record->status = $status->value;
        $record->usermodified = $userid;
        $record->timecreated = $time;
        $record->timemodified = $time;

        return $DB->insert_record('bookit_event_resource', $record);
    }

    /**
     * Update resource amount for an event.
     *
     * @param int $eventid Event ID
     * @param int $resourceid Resource ID
     * @param int $amount New amount
     * @param int $userid User ID
     * @param bookit_resource_status|null $status Optional new status
     * @return bool Success
     * @throws dml_exception
     */
    public static function update_resource_amount(
        int $eventid,
        int $resourceid,
        int $amount,
        int $userid,
        ?bookit_resource_status $status = null
    ): bool {
        global $DB;

        $record = $DB->get_record('bookit_event_resource', [
            'eventid' => $eventid,
            'resourceid' => $resourceid,
        ]);

        if (!$record) {
            return false;
        }

        $record->amount = $amount;
        if ($status !== null) {
            $record->status = $status->value;
        }
        $record->usermodified = $userid;
        $record->timemodified = time();

        return $DB->update_record('bookit_event_resource', $record);
    }

    /**
     * Update the status of an event resource.
     *
     * @param int $eventid Event ID
     * @param int $resourceid Resource ID
     * @param bookit_resource_status $status New status
     * @return bool Success
     * @throws dml_exception
     */
    public static function update_status(int $eventid, int $resourceid, bookit_resource_status $status): bool {
        global $DB, $USER;

        $record = $DB->get_record('bookit_event_resource', [
            'eventid'    => $eventid,
            'resourceid' => $resourceid,
        ]);

        if (!$record) {
            return false;
        }

        $record->status       = $status->value;
        $record->timemodified = time();
        $record->usermodified = (int)$USER->id;

        return $DB->update_record('bookit_event_resource', $record);
    }

    /**
     * Remove a resource from an event.
     *
     * @param int $eventid Event ID
     * @param int $resourceid Resource ID
     * @return bool Success
     * @throws dml_exception
     */
    public static function remove_resource_from_event(int $eventid, int $resourceid): bool {
        global $DB;

        return $DB->delete_records('bookit_event_resource', [
            'eventid' => $eventid,
            'resourceid' => $resourceid,
        ]);
    }

    /**
     * Remove all resources from an event.
     *
     * @param int $eventid Event ID
     * @return bool Success
     * @throws dml_exception
     */
    public static function remove_all_resources_from_event(int $eventid): bool {
        global $DB;

        return $DB->delete_records('bookit_event_resource', ['eventid' => $eventid]);
    }

    /**
     * Get resource progress (confirmed / total) for multiple events in one query.
     *
     * Returns a map of eventid => ['percent' => int, 'total' => int, 'confirmed' => int].
     * Events with no resources get total = 0 (caller should hide the progress bar in that case).
     *
     * @param int[] $eventids
     * @return array Map of eventid => ['percent' => int, 'total' => int, 'confirmed' => int]
     * @throws dml_exception
     */
    public static function get_resource_progress_for_events(array $eventids): array {
        global $DB;

        if (empty($eventids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED, 'eid');
        $confirmedval = bookit_resource_status::CONFIRMED->value;
        $inparams['confirmedval'] = $confirmedval;

        $sql = "SELECT eventid,
                       COUNT(*) AS total,
                       SUM(CASE WHEN status = :confirmedval THEN 1 ELSE 0 END) AS confirmed
                  FROM {bookit_event_resource}
                 WHERE eventid $insql
              GROUP BY eventid";

        $rows = $DB->get_records_sql($sql, $inparams);

        $result = [];
        foreach ($eventids as $eid) {
            if (isset($rows[$eid])) {
                $total     = (int)$rows[$eid]->total;
                $confirmed = (int)$rows[$eid]->confirmed;
                $percent   = $total > 0 ? (int)round(($confirmed / $total) * 100) : 0;
                $result[$eid] = ['percent' => $percent, 'total' => $total, 'confirmed' => $confirmed];
            } else {
                $result[$eid] = ['percent' => 0, 'total' => 0, 'confirmed' => 0];
            }
        }
        return $result;
    }

    /**
     * Convert database record to entity object.
     *
     * @param \stdClass $record Database record
     * @return bookit_event_resource
     */
    private static function record_to_entity(\stdClass $record): bookit_event_resource {
        return new bookit_event_resource(
            (int)$record->id,
            (int)$record->eventid,
            (int)$record->resourceid,
            (int)($record->amount ?? 1),
            bookit_resource_status::from($record->status ?? 'requested'),
            (int)($record->usermodified ?? 0),
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0)
        );
    }
}
