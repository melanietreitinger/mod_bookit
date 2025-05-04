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

use dml_exception;

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
     * @param string|null $semester
     * @param string $institutionid
     * @param int $starttime
     * @param int $endtime
     * @param int|null $duration
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
     * @param array $resources
     * @param mixed $refcourseid
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     */
    public function __construct(
        /** @var int id */
        public int $id,
        /** @var string name */
        public string $name,
        /** @var ?string semester */
        public ?string $semester,
        /** @var int institutionid */
        public int $institutionid,
        /** @var int starttime */
        public int $starttime,
        /** @var int endtime */
        public int $endtime,
        /** @var ?int duration */
        public ?int $duration,
        /** @var ?int participantsamount */
        public ?int $participantsamount,
        /** @var ?int timecompensation */
        public ?int $timecompensation,
        /** @var ?string compensationfordisadvantages */
        public ?string $compensationfordisadvantages,
        /** @var ?int bookingstatus */
        public ?int $bookingstatus,
        /** @var ?int personinchargeid */
        public ?int $personinchargeid,
        /** @var ?string otherexaminers */
        public ?string $otherexaminers,
        /** @var ?int coursetemplate */
        public ?int $coursetemplate,
        /** @var ?string notes */
        public ?string $notes,
        /** @var ?string internalnotes */
        public ?string $internalnotes,
        /** @var ?string supportpersons */
        public ?string $supportpersons,
        /** @var array resources */
        public array $resources,
        /** @var mixed refcourseid */
        public mixed $refcourseid,
        /** @var ?int usermodified */
        public ?int $usermodified,
        /** @var ?int timecreated */
        public ?int $timecreated,
        /** @var ?int timemodified */
        public ?int $timemodified,
    ) {
    }

    /**
     * Get record from database.
     *
     * @param int $id id of event to fetch.
     * @return self
     * @throws dml_exception
     */
    public static function from_database(int $id): self {
        global $DB;
        $record = $DB->get_record("bookit_event", ["id" => $id], '*', MUST_EXIST);

        $mappings = $DB->get_records('bookit_event_resources', ['eventid' => $record->id]);
        $map = [];
        foreach ($mappings as $mapping) {
            $map[] = (object) ['resourceid' => $mapping->resourceid, 'amount' => $mapping->amount];
        }
        $record->resources = $map;

        return self::from_record($record);
    }

    /**
     * Create object from record.
     *
     * @param array|object $record
     * @return self
     */
    public static function from_record(array|object $record): self {
        $record = (object) $record;

        return new self(
                $record->id ?? null,
                $record->name,
                $record->semester,
                $record->institutionid,
                $record->starttime,
                $record->endtime,
                $record->duration,
                $record->participantsamount ?? null,
                $record->timecompensation ?? null,
                $record->compensationfordisadvantages ?? null,
                $record->bookingstatus,
                $record->personinchargeid ?? null,
                ltrim(implode(',', $record->otherexaminers), ','),
                $record->coursetemplate ?? 0,
                $record->notes ?? null,
                $record->internalnotes ?? null,
                $record->supportpersons,
                $record->resources,
                $record->refcourseid ?? 0,
                $record->usermodified ?? null,
                $record->timecreated ?? null,
                $record->timemodified ?? null,
        );
    }

    /**
     * Save to database.
     *
     * @param int|null $userid
     * @return void
     * @throws dml_exception
     */
    final public function save(int|null $userid = null): void {
        global $DB, $USER;
        $this->usermodified = $userid ?? $USER->id;
        if (!$this->timecreated) {
            $this->timecreated = time();
        }
        $this->timemodified = time();

        if (!isset($this->bookingstatus)) {
            $this->bookingstatus = 0;
        }

        $data = clone $this;
        $mappings = $data->resources;
        unset($data->resources);

        if ($this->id) {
            $DB->update_record('bookit_event', $this);
            $DB->delete_records("bookit_event_resources", ['eventid' => $this->id]);
        } else {
            $this->id = $DB->insert_record('bookit_event', $this);
        }

        foreach ($mappings as $mapping) {
            $DB->insert_record('bookit_event_resources', [
                    'eventid' => $this->id,
                    'resourceid' => $mapping->resourceid,
                    'amount' => $mapping->amount,
            ]);
        }
    }

}
