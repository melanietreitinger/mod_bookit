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
 * Database class for bookit_events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity;

use coding_exception;
use dml_exception;
use mod_bookit\local\entity\resource\bookit_event_resource;
use mod_bookit\local\entity\resource\bookit_resource_status;
use mod_bookit\local\persistent\room;

/**
 * Database class for bookit_events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_event {
    /**
     * Create a new instance of this class.
     *
     * @param int $id
     * @param string $name
     * @param int $semester
     * @param int $institutionid
     * @param int $starttime
     * @param int $endtime
     * @param int|null $duration
     * @param int $roomid
     * @param int|null $participantsamount
     * @param int|null $timecompensation
     * @param string|null $compensationfordisadvantages
     * @param int|null $bookingstatus
     * @param int|null $personinchargeid
     * @param string|null $otherexaminers
     * @param int|null $coursetemplate
     * @param string|null $notes
     * @param string|null $internalnotes
     * @param string|null $supportpersons
     * @param int $extratimebefore
     * @param int $extratimeafter
     * @param mixed $refcourseid
     * @param int|null $usercreated
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     * @param array $resources
     */
    public function __construct(
        /** @var int id */
        public int $id,
        /** @var string name */
        public string $name,
        /** @var ?int semester */
        public int $semester,
        /** @var int institutionid */
        public int $institutionid,
        /** @var int starttime */
        public int $starttime,
        /** @var int $endtime endtime */
        public int $endtime,
        /** @var int $duration duration */
        public ?int $duration,
        /** @var int roomid */
        public int $roomid,
        /** @var int $participantsamount participantsamount  */
        public ?int $participantsamount,
        /** @var int $timecompensation timecompensation */
        public ?int $timecompensation,
        /** @var  string $compensationfordisadvantages compensationfordisadvantages */
        public ?string $compensationfordisadvantages,
        /** @var int $bookingstatus bookingstatus  */
        public ?int $bookingstatus,
        /** @var int $personinchargeid personinchargeid  */
        public ?int $personinchargeid,
        /** @var string $otherexaminers otherexaminers  */
        public ?string $otherexaminers,
        /** @var int $coursetemplate coursetemplate  */
        public ?int $coursetemplate,
        /** @var string $notes notes */
        public ?string $notes,
        /** @var string $internalnotes internalnotes  */
        public ?string $internalnotes,
        /** @var string $supportpersons supportpersons  */
        public ?string $supportpersons,
        /** @var int $extratimebefore extratimebefore*/
        public int $extratimebefore,
        /** @var int $extratimeafter extratimeafter*/
        public int $extratimeafter,
        /** @var mixed $refcourseid refcourseid */
        public mixed $refcourseid,
        /** @var ?int usercreated */
        public ?int $usercreated,
        /** @var int $usermodified usermodified  */
        /** @var ?int usermodified */
        public ?int $usermodified,
        /** @var int $timecreated timecreated  */
        public ?int $timecreated,
        /** @var int $timemodified timemodified  */
        public ?int $timemodified,
        /** @var array $resources resources */
        public array $resources,
    ) {
    }

    /**
     * Fetch a record from the database and return an object.
     *
     * @param int $id id of event to fetch.
     * @return self
     * @throws dml_exception|coding_exception
     */
    public static function from_database(int $id): self {
        global $DB;
        $record = $DB->get_record('bookit_event', ['id' => $id], '*', MUST_EXIST);

        $mappings = $DB->get_records('bookit_event_resource', ['eventid' => $record->id]);
        $map = [];
        foreach ($mappings as $mapping) {
            $map[] = (object)[
                'resourceid' => $mapping->resourceid,
                'amount'     => $mapping->amount,
            ];
        }
        $record->resources = $map;

        return self::from_record($record);
    }

    /**
     * Create an object from a stdClass or array record.
     *
     * @param array|object $record
     * @return self
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function from_record(array|object $record): self {
        $record = (object) $record;

        $room = room::get_record(['id' => $record->roomid], MUST_EXIST);

        return new self(
            $record->id ?? null,
            $record->name,
            $record->semester,
            $record->institutionid,
            $record->starttime,
            $record->endtime,
            $record->duration,
            $record->roomid,
            $record->participantsamount ?? null,
            $record->timecompensation ?? null,
            $record->compensationfordisadvantages ?? null,
            $record->bookingstatus ?? 0,
            $record->personinchargeid ?? null,
            ltrim(is_array($record->otherexaminers ?? [])
                ? implode(',', $record->otherexaminers ?? [])
                : ($record->otherexaminers ?? ''), ','),
            $record->coursetemplate ?? 0,
            $record->notes ?? null,
            $record->internalnotes ?? null,
            $record->supportpersons ?? null,
            $record->extratimebefore ?? $room->get('extratimebefore') ?? get_config('mod_bookit', 'extratimebefore'),
            $record->extratimeafter ?? $room->get('extratimeafter') ?? get_config('mod_bookit', 'extratimeafter'),
            $record->refcourseid ?? null,
            $record->usercreated ?? null,
            $record->usermodified ?? null,
            $record->timecreated ?? null,
            $record->timemodified ?? null,
            $record->resources ?? [],
        );
    }

    /**
     * Save this event to the database (insert or update).
     *
     * @param int|null $userid Optionally override the user performing the save.
     * @return void
     * @throws dml_exception
     */
    final public function save(?int $userid = null): void {
        global $DB, $USER;

        $uid = $userid ?? $USER->id;
        $this->usermodified = $uid;

        if (empty($this->id)) {
            // INSERT: set creator now if not provided.
            $this->usercreated = $this->usercreated ?? $uid;
        } else if (empty($this->usercreated)) {
            // UPDATE: preserve existing creator from DB so we never clobber it with null/0.
            $existing = $DB->get_field('bookit_event', 'usercreated', ['id' => $this->id]);
            if ($existing) {
                $this->usercreated = (int)$existing;
           }
        }
        $this->timecreated  ??= time();
        $this->timemodified  = time();
        $this->bookingstatus ??= 0;

        // Clone for main table, strip resources.
        $data     = clone $this;
        $mappings = $data->resources;
        unset($data->resources);

        if (!empty($this->id)) {
            $DB->update_record('bookit_event', $data);

            // Preserve existing resource statuses so that re-saving the booking
            // form does not reset checklist progress that was already set.
            $existingrows = $DB->get_records(
                'bookit_event_resource',
                ['eventid' => $this->id],
                '',
                'resourceid, status'
            );
            $existingstatuses = [];
            foreach ($existingrows as $row) {
                $existingstatuses[(int)$row->resourceid] = $row->status;
            }

            // Build the new resource-id set so we can remove de-selected ones.
            $newresourceids = array_map(fn($m) => (int)$m->resourceid, $mappings);

            // Delete only rows for resources no longer in the mapping.
            foreach ($existingrows as $row) {
                if (!in_array((int)$row->resourceid, $newresourceids, true)) {
                    $DB->delete_records('bookit_event_resource', [
                        'eventid'    => $this->id,
                        'resourceid' => (int)$row->resourceid,
                    ]);
                }
            }
        } else {
            $this->id = $DB->insert_record('bookit_event', $data);
            $existingstatuses = [];
        }

        $time = time();
        foreach ($mappings as $mapping) {
            $rid = (int)$mapping->resourceid;
            if (isset($existingstatuses[$rid])) {
                // Resource already existed — update amount, keep status.
                $DB->set_field_select(
                    'bookit_event_resource',
                    'amount',
                    $mapping->amount,
                    'eventid = :eventid AND resourceid = :resourceid',
                    ['eventid' => $this->id, 'resourceid' => $rid]
                );
                $DB->set_field_select(
                    'bookit_event_resource',
                    'timemodified',
                    $time,
                    'eventid = :eventid AND resourceid = :resourceid',
                    ['eventid' => $this->id, 'resourceid' => $rid]
                );
            } else {
                // New resource — insert with REQUESTED status.
                $DB->insert_record('bookit_event_resource', [
                        'eventid'      => $this->id,
                        'resourceid'   => $rid,
                        'amount'       => $mapping->amount,
                        'status'       => bookit_resource_status::REQUESTED->value,
                        'usermodified' => $this->usermodified,
                        'timecreated'  => $time,
                        'timemodified' => $time,
                ]);
            }
        }
    }
    /**
     * Fetch event metadata for a list of event ids.
     *
     * Returns a map of event id to a stdClass containing bookingstatus,
     * institutionid, roomid and roomname. Used by the calendar feed to
     * enrich events that were originally produced for FullCalendar.
     *
     * @param array $ids Event ids to look up.
     * @return array Map of event id to stdClass with bookingstatus,
     *               institutionid, roomid and roomname fields.
     * @throws dml_exception
     */
    public static function get_metadata_for_ids(array $ids): array {
        global $DB;

        if (empty($ids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $sql = "SELECT e.id,
                       e.bookingstatus,
                       e.institutionid,
                       e.roomid,
                       r.name AS roomname
                  FROM {bookit_event} e
             LEFT JOIN {bookit_room} r ON r.id = e.roomid
                 WHERE e.id $insql";
        return $DB->get_records_sql($sql, $inparams);
    }

    /**
     * Add metadata fields to a list of calendar feed events.
     *
     * Mutates the passed events by setting bookingstatus, institutionid,
     * roomid and roomname on each one based on a single batched query.
     * Events without a matching record in the database are left untouched.
     *
     * @param array $events Events as produced by event_manager::get_events_in_timerange().
     * @return array Same events with the four enrichment fields added.
     * @throws dml_exception
     */
    public static function enrich_with_metadata(array $events): array {
        if (empty($events)) {
            return $events;
        }

        $ids = [];
        foreach ($events as $ev) {
            // Works for array or object.
            $evid = is_array($ev) ? ($ev['id'] ?? null) : ($ev->id ?? null);
            if ($evid) {
                $ids[] = (int)$evid;
            }
        }
        if (empty($ids)) {
            return $events;
        }

        // Fetch all enrichment rows in a single query.
        $rows = self::get_metadata_for_ids($ids);

        foreach ($events as &$ev) {
            $evid = is_array($ev) ? ($ev['id'] ?? null) : ($ev->id ?? null);
            // Skip if nothing found.
            if (!$evid || !isset($rows[$evid])) {
                continue;
            }
            $row = $rows[$evid];

            // Assign values safely for array or object.
            if (is_array($ev)) {
                $ev['bookingstatus'] = (int)($row->bookingstatus ?? 0);
                $ev['institutionid']    = (string)($row->institutionid ?? '');
                $ev['roomid']        = (int)($row->roomid ?? 0);
                $ev['roomname']      = (string)($row->roomname ?? '');
            } else {
                $ev->bookingstatus = (int)($row->bookingstatus ?? 0);
                $ev->institutionid    = (string)($row->institutionid ?? '');
                $ev->roomid        = (int)($row->roomid ?? 0);
                $ev->roomname      = (string)($row->roomname ?? '');
            }
        }
        unset($ev);
        return $events;
    }

    /**
     * Fetch events for the export endpoint, scoped by capability.
     *
     * If a non-empty list of event ids is given, only those events are
     * returned, still subject to the user's capability scope. Otherwise
     * all events whose [starttime, endtime] window overlaps the given
     * range are returned.
     *
     * Users with mod/bookit:viewalldetailsofevent receive every event in
     * scope. Users with mod/bookit:viewalldetailsofownevent receive only
     * events they created, are person in charge of, or are listed as other
     * examiners on. Users with neither capability receive an empty array.
     *
     * @param \context_module $context Module context for the capability checks.
     * @param int $userid User performing the export.
     * @param array $ids Optional explicit event ids; empty means use the time range.
     * @param int|null $startts Unix timestamp of range start, used when $ids is empty.
     * @param int|null $endts Unix timestamp of range end, used when $ids is empty.
     * @return array Event records keyed by id.
     * @throws dml_exception
     */
    public static function get_for_export(\context_module $context, int $userid,
                                           array $ids = [],
                                           ?int $startts = null,
                                           ?int $endts = null): array {
        global $DB;

        $viewall = has_capability('mod/bookit:viewalldetailsofevent', $context);
        $viewown = has_capability('mod/bookit:viewalldetailsofownevent', $context);

        if (!$viewall && !$viewown) {
            // No details capability: nothing exportable.
            return [];
        }

        if (!empty($ids)) {
            // Export specific IDs, but only those the user is allowed to see in detail.
            [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'e');
            if ($viewall) {
                $sql = "SELECT *
                          FROM {bookit_event}
                         WHERE id $insql";
                return $DB->get_records_sql($sql, $inparams);
            }
            $like = $DB->sql_like('otherexaminers', ':otherex');
            $sql = "SELECT *
                      FROM {bookit_event}
                     WHERE id $insql
                       AND (
                              usercreated = :uid
                           OR personinchargeid = :uid2
                           OR $like
                       )";
            $params = $inparams + ['uid' => $userid, 'uid2' => $userid, 'otherex' => $userid];
            return $DB->get_records_sql($sql, $params);
        }

        // Time-range export, capability-safe.
        $startts = $startts ?? 0;
        $endts   = $endts   ?? 4102444800; // 2100-01-01 UTC.

        if ($viewall) {
            $sql = "SELECT *
                      FROM {bookit_event}
                     WHERE endtime >= :starttime
                       AND starttime <= :endtime";
            return $DB->get_records_sql($sql, ['starttime' => $startts, 'endtime' => $endts]);
        }

        $like = $DB->sql_like('otherexaminers', ':otherex');
        $sql = "SELECT *
                  FROM {bookit_event}
                 WHERE endtime >= :starttime
                   AND starttime <= :endtime
                   AND (
                          usercreated = :uid
                       OR personinchargeid = :uid2
                       OR $like
                   )";
        $params = [
            'starttime' => $startts,
            'endtime'   => $endts,
            'uid'       => $userid,
            'uid2'      => $userid,
            'otherex'   => $userid,
        ];
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Resolve institutionid foreign keys to institution names for export.
     *
     * Replaces the integer institutionid field on each event with the
     * institution's name. Events whose institutionid does not resolve to
     * an existing record have their institutionid cleared to an empty string.
     *
     * @param array $events Events keyed by event id.
     * @return array Same events with institutionid replaced by the institution name.
     * @throws dml_exception
     */
    public static function resolve_institution_names(array $events): array {
        global $DB;

        if (empty($events)) {
            return $events;
        }

        $ids = [];
        foreach ($events as $ev) {
            $iid = (int)($ev->institutionid ?? 0);
            if ($iid > 0) {
                $ids[$iid] = $iid;
            }
        }
        if (empty($ids)) {
            return $events;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $sql = "SELECT id, name
                  FROM {bookit_institution}
                 WHERE id $insql";
        $names = $DB->get_records_sql_menu($sql, $inparams);

        foreach ($events as &$ev) {
            $iid = (int)($ev->institutionid ?? 0);
            $ev->institutionid = $names[$iid] ?? '';
        }
        unset($ev);
        return $events;
    }

}
