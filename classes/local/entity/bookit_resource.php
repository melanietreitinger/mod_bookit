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
 * Database class for bookit_resources.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity;

use dml_exception;

/**
 * Database class for bookit_resources.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_resource {
    /** @var ?int id*/
    public ?int $id;
    /** @var string name*/
    public string $name;
    /** @var ?string description*/
    public ?string $description;
    /** @var int amount*/
    public int $amount;
    /** @var int categoryid*/
    public int $categoryid;
    /** @var ?int usermodified*/
    public ?int $usermodified;
    /** @var ?int timecreated*/
    public ?int $timecreated;
    /** @var ?int timemodified*/
    public ?int $timemodified;

    /**
     * Create a new instance of this class.
     * @param string $name
     * @param string $description
     * @param int $amount
     * @param int $categoryid
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     * @param int|null $id
     */
    public function __construct(string $name, 
    string $description, 
    int $amount, int $categoryid,
    int|null $usermodified = null, 
    int|null $timecreated = null, 
    int|null $timemodified = null, 
    int|null $id = null) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->amount = $amount;
        $this->categoryid = $categoryid;
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
        $record = $DB->get_record("bookit_resource", ["id" => $id], '*', MUST_EXIST);

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
            $record->description,
            $record->amount,
            $record->categoryid,
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
        if ($this->id) {
            $DB->update_record('bookit_resource', $this);
        } else {
            $this->id = $DB->insert_record('bookit_resource', $this);
        }
    }
}
