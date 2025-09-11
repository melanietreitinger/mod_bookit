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

/*Summary changes vs optimizations: 
-Added examiner ID (in first block until around line 130, there is nothing else new) 
- Rewrote function save
*/

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
     * @param int         $id
     * @param string      $name
     * @param string|null $semester
     * @param string      $department
     * @param int         $starttime
     * @param int         $endtime
     * @param int|null    $duration
     * @param int|null    $participantsamount
     * @param int|null    $timecompensation
     * @param string|null $compensationfordisadvantages
     * @param int|null    $bookingstatus
     * @param int|null    $personinchargeid
     * @param string|null $otherexaminers
     * @param int|null    $coursetemplate
     * @param string|null $notes
     * @param string|null $internalnotes
     * @param string|null $supportpersons
     * @param array       $resources
     * @param mixed       $refcourseid
     * @param int|null    $usermodified
     * @param int|null    $timecreated
     * @param int|null    $timemodified
     * @param int|null    $examinerid (optional) User ID of the examiner responsible
     */
    public function __construct(
        public int     $id,
        public string  $name,
        public ?string $semester,
        public string  $department,
        public int     $starttime,
        public int     $endtime,
        public ?int    $duration,
        public ?int    $participantsamount,
        public ?int    $timecompensation,
        public ?string $compensationfordisadvantages,
        public ?int    $bookingstatus,
        public ?int    $personinchargeid,
        public ?string $otherexaminers,
        public ?int    $coursetemplate,
        public ?string $notes,
        public ?string $internalnotes,
        public ?string $supportpersons,
        public array   $resources,
        public mixed   $refcourseid,
        public ?int    $usermodified,
        public ?int    $timecreated,
        public ?int    $timemodified,
        public ?int    $examinerid = null
    ) {
    }

    /**
     * Fetch a record from the database and return an object.
     *
     * @param int $id id of event to fetch.
     * @return self
     * @throws dml_exception
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
     */
    public static function from_record(array|object $record): self {
        $record = (object)$record;
        return new self(
                $record->id ?? null,
                $record->name,
                $record->semester,
                $record->department,
                $record->starttime,
                $record->endtime,
                $record->duration,
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
                $record->resources ?? [],
                $record->refcourseid ?? null,
                $record->usermodified ?? null,
                $record->timecreated ?? null,
                $record->timemodified ?? null,
                // if examinerid column is present, use it; else fallback
                $record->examinerid ?? $record->personinchargeid ?? null
        );
    }

    /**
     * Save this event to the database (insert or update).
     *
     * @param int|null $userid Optionally override the user performing the save.
     * @return void
     * @throws dml_exception
     */
    final public function save(int $userid = null): void {
        global $DB, $USER;

        // Ensure examinerid is set for the overview.
        if (empty($this->examinerid)) {
            $this->examinerid = $USER->id;
        }

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
