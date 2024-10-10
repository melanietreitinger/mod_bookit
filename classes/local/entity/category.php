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
 * Database class for bookit_resources.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category {
    /** @var ?int id */
    public ?int $id;
    /** @var string name */
    public string $name;
    /** @var ?string description */
    public ?string $description;
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
     * @param string|null $description
     * @param int $usermodified
     * @param int $timecreated
     * @param int $timemodified
     * @param int|null $id
     */
    public function __construct(string $name, string|null $description, int|null $usermodified = null, int|null $timecreated = null,
            int|null $timemodified = null, int|null $id = null) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->usermodified = $usermodified;
        $this->timecreated = $timecreated;
        $this->timemodified = $timemodified;
    }

    /**
     * Fetch from database.
     * @param int $id id of event to fetch.
     * @return self
     */
    public static function from_database($id) {
        global $DB;
        $record = $DB->get_record("bookit_category", ["id" => $id], '*', MUST_EXIST);

        return self::from_record($record);
    }

    /**
     * Create object from record.
     * @param array|object $record
     * @return self
     */
    public static function from_record($record): self {
        $record = (object) $record;
        return new self(
                $record->name,
                $record->description,
                $record->usermodified ?? null,
                $record->timecreated ?? null,
                $record->timemodified ?? null,
                $record->id ?? null
        );
    }

    /**
     * Save to database.
     * @param $userid
     * @return void
     * @throws \dml_exception
     */
    public function save($userid = null): void {
        global $DB, $USER;
        $this->usermodified = $userid ?? $USER->id;
        if (!$this->timecreated) {
            $this->timecreated = time();
        }
        $this->timemodified = time();
        if ($this->id) {
            $DB->update_record('bookit_category', $this);
        } else {
            $this->id = $DB->insert_record('bookit_category', $this);
        }
    }
}
