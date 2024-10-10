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
    /** const STATUS_OPEN */
    const STATUS_OPEN = 1;
    /** const STATUS_ACCEPTED */
    const STATUS_ACCEPTED = 2;
    /** const STATUS_REJECTED */
    const STATUS_REJECTED = 3;

    /** @var ?int id */
    public ?int $id;
    /** @var string name */
    public string $name;
    /** @var int semester */
    public int $semester;
    /** @var string department */
    public string $department;
    /** @var int starttime */
    public int $starttime;
    /** @var int endtime */
    public int $endtime;
    /** @var ?int duration */
    public ?int $duration;
    /** @var ?int participantsamount */
    public ?int $participantsamount;
    /** @var ?string compensationfordisadvantage */
    public ?string $compensationfordisadvantage;
    /** @var ?int status */
    public ?int $status;
    /** @var ?int personinchargeid */
    public ?int $personinchargeid;
    /** @var ?string personinchargename */
    public ?string $personinchargename;
    /** @var ?string personinchargeemail */
    public ?string $personinchargeemail;
    /** @var ?int coursetemplate */
    public ?int $coursetemplate;
    /** @var ?string notes */
    public ?string $notes;
    /** @var ?string internalnotes */
    public ?string $internalnotes;
    /** @var ?string support */
    public ?string $support;
    /** @var array resources */
    public array $resources;
    /** @var ?int refcourseid */
    public ?int $refcourseid;
    /** @var ?int usermodified */
    public ?int $usermodified;
    /** @var ?int timecreated */
    public ?int $timecreated;
    /** @var ?int timemodified */
    public ?int $timemodified;

    /**
     * Create a new instance of this class.
     *
     * @param string $name
     * @param string $semester
     * @param string $department
     * @param int $starttime
     * @param int $endtime
     * @param int $duration
     * @param int $participantsamount
     * @param string $compensationfordisadvantage
     * @param string $status
     * @param int $personinchargeid
     * @param string $personinchargename
     * @param string $personinchargeemail
     * @param int $coursetemplate
     * @param string $internalnotes
     * @param string $notes
     * @param string $support
     * @param array $resources
     * @param int $refcourseid
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     * @param int|null $id
     */
    public function __construct(string $name, string $semester, string $department, int $starttime, int $endtime, int $duration,
            int $participantsamount, string $compensationfordisadvantage, string $status, int $personinchargeid,
            string $personinchargename,
            string $personinchargeemail, int $coursetemplate, string $internalnotes, string $notes, string $support,
            array $resources, int $refcourseid,
            int|null $usermodified = null, int|null $timecreated = null, int|null $timemodified = null, int|null $id = null) {
        $this->id = $id;
        $this->name = $name;
        $this->semester = $semester;
        $this->department = $department;
        $this->starttime = $starttime;
        $this->endtime = $endtime;
        $this->duration = $duration;
        $this->participantsamount = $participantsamount;
        $this->compensationfordisadvantage = $compensationfordisadvantage;
        $this->status = $status;
        $this->personinchargeid = $personinchargeid;
        $this->personinchargename = $personinchargename;
        $this->personinchargeemail = $personinchargeemail;
        $this->coursetemplate = $coursetemplate;
        $this->notes = $notes;
        $this->internalnotes = $internalnotes;
        $this->support = $support;
        $this->resources = $resources;
        $this->refcourseid = $refcourseid;
        $this->usermodified = $usermodified;
        $this->timecreated = $timecreated;
        $this->timemodified = $timemodified;
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
                $record->name,
                $record->semester,
                $record->department,
                $record->starttime,
                $record->endtime,
                $record->duration,
                $record->participantsamount ?? null,
                $record->compensationfordisadvantage ?? null,
                $record->status,
                $record->personinchargeid ?? null,
                $record->personinchargename,
                $record->personinchargeemail,
                $record->coursetemplate ?? 0,
                $record->notes ?? null,
                $record->internalnotes ?? null,
                $record->support ?? null,
                $record->resources,
                $record->refcourseid ?? null,
                $record->usermodified ?? null,
                $record->timecreated ?? null,
                $record->timemodified ?? null,
                $record->id ?? null
        );
    }

    /**
     * Save to database.
     *
     * @param int|null $userid
     * @return void
     * @throws dml_exception
     */
    public function save(int|null $userid = null): void {
        global $DB, $USER;
        $this->usermodified = $userid ?? $USER->id;
        if (!$this->timecreated) {
            $this->timecreated = time();
        }
        $this->timemodified = time();

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
