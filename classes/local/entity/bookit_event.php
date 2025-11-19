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

        $mappings = $DB->get_records('bookit_event_resources', ['eventid' => $record->id]);
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
            ltrim(implode(',', $record->otherexaminers ?? []), ','),
            $record->coursetemplate ?? 0,
            $record->notes ?? null,
            $record->internalnotes ?? null,
            $record->supportpersons ?? null,
            $record->extratimebefore ?? $room->get('extratimebefore') ?? get_config('mod_bookit', 'extratimebefore'),
            $record->extratimeafter ?? $room->get('extratimeafter') ?? get_config('mod_bookit', 'extratimeafter'),
            $record->refcourseid ?? null,
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

        $this->usermodified = $userid ?? $USER->id;
        $this->timecreated  ??= time();
        $this->timemodified  = time();
        $this->bookingstatus ??= 0;

        // Clone for main table, strip resources.
        $data     = clone $this;
        $mappings = $data->resources;
        unset($data->resources);

        if (!empty($this->id)) {
            $DB->update_record('bookit_event', $data);
            $DB->delete_records('bookit_event_resources', ['eventid' => $this->id]);
        } else {
            $this->id = $DB->insert_record('bookit_event', $data);
        }

        foreach ($mappings as $mapping) {
            $DB->insert_record('bookit_event_resources', [
                    'eventid'    => $this->id,
                    'resourceid' => $mapping->resourceid,
                    'amount'     => $mapping->amount,
            ]);
        }
    }
}
