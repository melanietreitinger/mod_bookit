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
 * Database class for bookit_notification_slots.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity;

use dml_exception;
use mod_bookit\local\manager\checklist_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Database class for bookit_notification_slots.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_notification_slot implements \renderable, \templatable {

    const TYPE_BEFORE_DUE = 0;
    const TYPE_WHEN_DUE = 1;
    const TYPE_OVERDUE = 2;
    const TYPE_WHEN_DONE = 3;



    /**
     * Create a new instance of this class.
     *
     * @param int|null $id
     * @param int $checklistitemid
     * @param int $type Type of notification (e.g. email, dashboard, etc.)
     * @param string|null $roleids JSON-encoded list of role IDs to notify
     * @param int|null $duedaysoffset
     * @param string|null $duedaysrelation
     * @param int $isactive
     * @param string|null $messagetext
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     */
    public function __construct(
        /** @var int|null id */
        public ?int $id,
        /** @var int checklistitemid */
        public int $checklistitemid,
        /** @var int type */
        public int $type,
        /** @var string|null roleids */
        public ?string $roleids,
        /** @var int|null duedaysoffset */
        public ?int $duedaysoffset,
        /** @var string|null duedaysrelation */
        public ?string $duedaysrelation,
        /** @var int isactive */
        public int $isactive = 0,
        /** @var string|null messagetext */
        public ?string $messagetext,
        /** @var int|null usermodified */
        public ?int $usermodified = null,
        /** @var int|null timecreated */
        public ?int $timecreated = null,
        /** @var int|null timemodified */
        public ?int $timemodified = null,
    ) {
    }

    /**
     * Get record from database.
     *
     * @param int $id id of notification slot to fetch.
     * @return self
     * @throws dml_exception
     */
    public static function from_database(int $id): self {
        global $DB;
        $record = $DB->get_record("bookit_notification_slots", ["id" => $id], '*', MUST_EXIST);
        return self::from_record($record);
    }

    /**
     * Get notification slots for a specific checklist item
     *
     * @param int $checklistitemid ID of the checklist item
     * @return array Array of notification slot objects
     * @throws dml_exception
     */
    public static function get_slots_for_item(int $checklistitemid): array {
        global $DB;
        $records = $DB->get_records("bookit_notification_slots", ["checklistitemid" => $checklistitemid]);
        $slots = [];
        foreach ($records as $record) {
            $slots[] = self::from_record($record);
        }
        return $slots;
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
                $record->checklistitemid,
                $record->type ?? 0,
                $record->roleids,
                $record->duedaysoffset,
                $record->duedaysrelation,
                $record->isactive,
                $record->messagetext,
                $record->usermodified,
                $record->timecreated,
                $record->timemodified
        );
    }

    /**
     * Save this notification slot to the database.
     *
     * @return int ID of the saved record
     * @throws dml_exception
     */
    public function save(): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->checklistitemid = $this->checklistitemid;
        $record->type = $this->type;
        $record->roleids = $this->roleids;
        $record->duedaysoffset = $this->duedaysoffset;
        $record->duedaysrelation = $this->duedaysrelation;
        $record->isactive = $this->isactive;
        $record->messagetext = $this->messagetext;
        $record->usermodified = $USER->id;
        $record->timemodified = time();

        if (empty($this->id)) {
            $record->timecreated = time();
            $this->id = $DB->insert_record("bookit_notification_slots", $record);
            return $this->id;
        } else {
            $record->id = $this->id;
            $DB->update_record("bookit_notification_slots", $record);
            return $this->id;
        }
    }

    /**
     * Delete this notification slot from the database.
     *
     * @return bool Success
     * @throws dml_exception
     */
    public function delete(): bool {
        global $DB;
        return $DB->delete_records("bookit_notification_slots", ["id" => $this->id]);
    }

    public static function get_all_notification_slot_types(): array {
        $reflection = new \ReflectionClass(self::class);
        $constants = $reflection->getConstants();

        return $constants;
    }

    /**
     * Get the role IDs as an array
     *
     * @return array Array of role IDs
     */
    public function get_role_ids(): array {
        if (empty($this->roleids)) {
            return [];
        }

        // TODO this is string?

        return json_decode($this->roleids, true) ?: [];
    }

    /**
     * Set role IDs from an array
     *
     * @param array $roleids Array of role IDs
     * @return void
     */
    public function set_role_ids(array $roleids): void {
        // TODO this is string?
        $this->roleids = json_encode($roleids);
    }

    /**
     * Export data for use in templates
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();

        $data->id = $this->id;
        $data->checklistitemid = $this->checklistitemid;
        $data->type = $this->type;
        $data->typename = checklist_manager::get_notification_slot_type($this->type);
        $data->roleids = $this->get_role_ids();
        $data->duedaysoffset = $this->duedaysoffset;
        $data->duedaysrelation = $this->duedaysrelation;
        $data->isactive = $this->isactive;
        $data->messagetext = $this->messagetext;

        return $data;
    }
}
