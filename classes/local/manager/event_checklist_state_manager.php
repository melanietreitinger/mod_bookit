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
 * Manager for event checklist state (per-event check-off state for master checklist items).
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use stdClass;

/**
 * Manages per-event check-off state for master checklist items.
 */
class event_checklist_state_manager {
    /** @var string DB table name */
    const TABLE = 'bookit_event_checklist_state';

    /**
     * Get all state records for an event.
     *
     * Returns a map of checklistitemid => done (bool).
     *
     * @param int $eventid
     * @param int $userid
     * @return array Map of checklistitemid => bool
     */
    public static function get_state_for_event(int $eventid, int $userid): array {
        global $DB;
        $records = $DB->get_records(self::TABLE, ['eventid' => $eventid, 'userid' => $userid]);
        $state = [];
        foreach ($records as $record) {
            $state[(int)$record->checklistitemid] = (bool)$record->done;
        }
        return $state;
    }

    /**
     * Get the global done state for an event (any user marks an item done counts it as done).
     *
     * Returns a map of checklistitemid => bool (true if any user marked it done).
     *
     * @param int $eventid
     * @return array checklistitemid => bool
     */
    public static function get_global_state_for_event(int $eventid): array {
        global $DB;
        $records = $DB->get_records_select(self::TABLE, 'eventid = :eventid AND done = 1', ['eventid' => $eventid]);
        $state = [];
        foreach ($records as $record) {
            $state[(int)$record->checklistitemid] = true;
        }
        return $state;
    }

    /**
     * Set the done state for one checklist item in an event.
     *
     * Creates or updates the state record.
     *
     * @param int $eventid
     * @param int $checklistitemid
     * @param int $userid
     * @param bool $done
     * @return void
     */
    public static function set_item_state(int $eventid, int $checklistitemid, int $userid, bool $done): void {
        global $DB, $USER;
        $now = time();

        $existing = $DB->get_record(self::TABLE, [
            'eventid'         => $eventid,
            'checklistitemid' => $checklistitemid,
            'userid'          => $userid,
        ]);

        if ($existing) {
            $existing->done         = (int)$done;
            $existing->usermodified = $USER->id;
            $existing->timemodified = $now;
            $DB->update_record(self::TABLE, $existing);
        } else {
            $record = new stdClass();
            $record->eventid         = $eventid;
            $record->checklistitemid = $checklistitemid;
            $record->userid          = $userid;
            $record->done            = (int)$done;
            $record->usermodified    = $USER->id;
            $record->timecreated     = $now;
            $record->timemodified    = $now;
            $DB->insert_record(self::TABLE, $record);
        }
    }

    /**
     * Get progress for an event: how many distinct items are done vs total.
     *
     * State is global (not per-user): an item counts as done if any user marked it done.
     * Only items belonging to the given master checklist are counted.
     *
     * @param int $eventid
     * @param int $masterid Master checklist ID
     * @return array ['done' => int, 'total' => int, 'percent' => int]
     */
    public static function get_progress_for_event(int $eventid, int $masterid): array {
        global $DB;

        $total = $DB->count_records('bookit_checklist_item', ['masterid' => $masterid]);
        if ($total === 0) {
            return ['done' => 0, 'total' => 0, 'percent' => 0];
        }

        $sql = 'SELECT COUNT(DISTINCT s.checklistitemid)
                  FROM {bookit_event_checklist_state} s
                  JOIN {bookit_checklist_item} i ON i.id = s.checklistitemid
                 WHERE s.eventid = :eventid AND s.done = 1 AND i.masterid = :masterid';
        $done = (int)$DB->count_records_sql($sql, ['eventid' => $eventid, 'masterid' => $masterid]);

        return [
            'done'    => $done,
            'total'   => $total,
            'percent' => (int)round(($done / $total) * 100),
        ];
    }

    /**
     * Get progress percent for multiple events in a single query.
     *
     * Returns a map of eventid => percent (0-100).
     * State is global: an item counts as done if any user marked it done.
     *
     * @param int[] $eventids List of event IDs
     * @param int $masterid Master checklist ID
     * @return array Map of eventid => percent
     */
    public static function get_progress_percent_for_events(array $eventids, int $masterid): array {
        global $DB;

        if (empty($eventids) || $masterid <= 0) {
            return [];
        }

        $total = $DB->count_records('bookit_checklist_item', ['masterid' => $masterid]);
        if ($total === 0) {
            return array_fill_keys($eventids, 0);
        }

        [$insql, $inparams] = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED, 'eid');
        $sql = "SELECT s.eventid, COUNT(DISTINCT s.checklistitemid) AS donecnt
                  FROM {bookit_event_checklist_state} s
                  JOIN {bookit_checklist_item} i ON i.id = s.checklistitemid
                 WHERE s.eventid $insql AND s.done = 1 AND i.masterid = :masterid
              GROUP BY s.eventid";
        $inparams['masterid'] = $masterid;

        $rows = $DB->get_records_sql($sql, $inparams);

        $result = [];
        foreach ($eventids as $eid) {
            $done = isset($rows[$eid]) ? (int)$rows[$eid]->donecnt : 0;
            $result[$eid] = (int)round(($done / $total) * 100);
        }
        return $result;
    }
}
