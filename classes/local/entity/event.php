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

namespace mod_bookit\local\entity;

/**
 * Database class for bookit_events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event {

    const STATUS_OPEN = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_REJECTED = 3;


    public ?int $id;
    public string $name;
    public int $semester;
    public string $department;
    public int $starttime;
    public int $endtime;
    public ?int $duration;
    public ?int $participantsamount;
    public ?string $compensationfordisadvantage;
    public ?int $status;
    public ?int $personinchargeid;
    public ?string $personinchargename;
    public ?string $personinchargeemail;
    public ?int $coursetemplate;
    public ?string $notes;
    public ?string $internalnotes;
    public ?string $support;
    public array $resources;
    public ?int $refcourseid;
    public ?int $usermodified;
    public ?int $timecreated;
    public ?Int $timemodified;

    /**
     * @param string $name
     * @param int $semester
     * @param int $starttime
     * @param int $endtime
     * @param int $duration
     * @param int $participantsamount
     * @param string $compensationfordisadvantage
     * @param int|null $personinchargeid
     * @param string|null $personinchargename
     * @param string|null $personinchargeemail
     * @param int|null $coursetemplate
     * @param string $notes
     * @param string $support
     * @param int|null $refcourseid
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     */
    public function __construct(string $name, $semester, $department, int $starttime, int $endtime, int $duration,
            $participantsamount, $compensationfordisadvantage, $status, $personinchargeid, $personinchargename,
            $personinchargeemail, $coursetemplate, $internalnotes, $notes, $support, $resources, $refcourseid,
            $usermodified = null, $timecreated = null, $timemodified = null, $id = null) {
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
     * @param int $id id of event to fetch.
     * @return self
     */
    public static function from_database($id) {
        global $DB;
        $record = $DB->get_record("bookit_event", array("id" => $id), '*', MUST_EXIST);

        $mappings = $DB->get_records('bookit_event_resources', ['eventid' => $record->id]);
        $map = [];
        foreach ($mappings as $mapping) {
            $map[] = (object) ['resourceid' => $mapping->resourceid, 'amount' => $mapping->amount];
        }
        $record->resources = $map;

        return self::from_record($record);
    }

    /**
     * @param array|object $record
     * @return self
     */
    public static function from_record($record): self {
        $record = (object) $record;
        return new self(
                $record->name,
                $record->semester,
                $record->department,
                $record->starttime,
                $record->endtime,
                $record->duration,
                $record->participantsamount,
                $record->compensationfordisadvantage,
                $record->status,
                $record->personinchargeid,
                $record->personinchargename,
                $record->personinchargeemail,
                $record->coursetemplate,
                $record->notes,
                $record->internalnotes,
                $record->support,
                $record->resources,
                $record->refcourseid,
                $record->usermodified ?? null,
                $record->timecreated ?? null,
                $record->timemodified ?? null,
                $record->id
        );
    }

    public function save($userid = null): void {
        global $DB, $USER;
        $this->usermodified = $userid ?? $USER->id;
        if (!$this->timecreated) {
            $this->timecreated = time();
        }
        $this->timemodified = time();


        $data = (object) clone $this;
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
                'amount' => $mapping->amount
            ]);
        }
    }

}