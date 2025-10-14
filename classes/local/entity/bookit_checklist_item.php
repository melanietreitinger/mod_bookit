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
 * Database class for bookit_checklist_item.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity;

use dml_exception;
use mod_bookit\local\manager\checklist_manager;

/**
 * Database class for bookit_checklist_item.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_checklist_item implements \renderable, \templatable {


    /** @var int|null ID of the checklist item */
    public ?int $itemid;

    /** @var string|null Due date as string */
    public ?string $duedate;

    /** @var int|null Order of the checklist item */
    public ?int $order;

    /**
     * Create a new instance of this class.
     *
     * @param int|null $id
     * @param int $masterid
     * @param int|null $categoryid
     * @param int|null $parentid
     * @param array|null $roomids
     * @param array|null $roleids
     * @param string $title
     * @param string $description
     * @param int $itemtype
     * @param string|null $options
     * @param int $sortorder
     * @param int $isrequired
     * @param string|null $defaultvalue
     * @param int|null $duedaysoffset
     * @param string|null $duedaysrelation
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     */
    public function __construct(
        /** @var int id */
        public ?int $id,
        /** @var int masterid */
        public int $masterid,
        /** @var ?int categoryid */
        public ?int $categoryid,
        /** @var ?int parentid */
        public ?int $parentid,
        /** @var ?array roomids */
        public ?array $roomids,
        /** @var ?array roleids */
        public ?array $roleids,
        /** @var string title */
        public string $title,
        /** @var string description */
        public string $description,
        /** @var int itemtype */
        public int $itemtype,
        /** @var ?string options */
        public ?string $options,
        /** @var int sortorder */
        public int $sortorder,
        /** @var int isrequired */
        public int $isrequired,
        /** @var ?string defaultvalue */
        public ?string $defaultvalue,
        /** @var ?int due_days_offset */
        public ?int $duedaysoffset,
        /** @var ?string due_days_relation */
        public ?string $duedaysrelation,
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
     * @param int $id id of checklist item to fetch.
     * @return self
     * @throws dml_exception
     */
    public static function from_database(int $id): self {
        global $DB;
        $record = $DB->get_record("bookit_checklist_item", ["id" => $id], '*', MUST_EXIST);
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
            $record->masterid,
            $record->categoryid,
            $record->parentid,
            json_decode($record->roomids),
            json_decode($record->roleids),
            $record->title,
            $record->description,
            $record->itemtype,
            $record->options,
            $record->sortorder,
            $record->isrequired,
            $record->defaultvalue,
            $record->duedaysoffset,
            $record->duedaysrelation,
            $record->usermodified,
            $record->timecreated,
            $record->timemodified
        );
    }

    /**
     * Save this checklist item to the database.
     *
     * @return int ID of the saved record
     * @throws dml_exception
     */
    public function save(): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->masterid = $this->masterid;
        $record->categoryid = $this->categoryid;
        $record->parentid = $this->parentid;
        $record->roomids = json_encode($this->roomids);
        $record->roleids = json_encode($this->roleids);
        $record->title = $this->title;
        $record->description = $this->description;
        $record->itemtype = $this->itemtype;
        $record->options = $this->options;
        $record->sortorder = $this->sortorder;
        $record->isrequired = $this->isrequired;
        $record->defaultvalue = $this->defaultvalue;
        $record->duedaysoffset = $this->duedaysoffset;
        $record->duedaysrelation = $this->duedaysrelation;
        $record->usermodified = $USER->id;
        $record->timemodified = time();

        if (empty($this->id)) {
            $record->timecreated = time();
            $this->id = $DB->insert_record("bookit_checklist_item", $record);
            return $this->id;
        } else {
            $record->id = $this->id;
            $DB->update_record("bookit_checklist_item", $record);
            return $this->id;
        }
    }

    /**
     * Delete this checklist item from the database.
     *
     * @return bool Success
     * @throws dml_exception
     */
    public function delete(): bool {
        global $DB;

        return $DB->delete_records("bookit_checklist_item", ["id" => $this->id]);
    }

    /**
     * Exports the data for template rendering.
     *
     * @param \renderer_base $output The renderer to be used
     * @return \stdClass Data for the template
     */
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();

        $data->id = $this->id;
        $data->title = $this->title;
        $data->order = $this->sortorder;
        $data->categoryid = $this->categoryid;
        $data->roomids = json_encode($this->roomids);

        foreach ($this->roomids as $roomid) {
            $room = checklist_manager::get_room_by_id((int) $roomid);
            $data->roomnames[] = [
                'roomname' => $room->name,
                'roomid' => (int) $roomid,
                'eventcolor' => $room->eventcolor,
                'textclass' => $room->textclass,
            ];
        }

        $data->roleids = json_encode($this->roleids);
        $data->rolenames = [];

        foreach ($this->roleids as $roleid) {
            $role = checklist_manager::get_role_by_id((int) $roleid);

            $roleData = [
                'rolename' => $role ? $role->name : '',
                'roleid' => (int) $roleid,
            ];

            // Check if current user has this specific role.
            if (checklist_manager::user_has_bookit_role((int) $roleid)) {
                $roleData['extraclasses'] = 'badge badge-warning text-dark';
            } else {
                $roleData['extraclasses'] = 'badge badge-primary text-light';
            }

            $data->rolenames[] = $roleData;
        }

        $data->type = 'item';

        // die(print_r($data, true));

        return $data;
    }
}
