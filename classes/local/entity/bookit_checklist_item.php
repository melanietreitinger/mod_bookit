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

defined('MOODLE_INTERNAL') || die();

/**
 * Database class for bookit_checklist_item.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_checklist_item implements \renderable, \templatable {

    public const ITEM_STATE_OPEN = 1;
    public const ITEM_STATE_PROCESSING = 2;
    public const ITEM_STATE_DONE = 3;
    public const ITEM_STATE_UNKNOWN = 0;

    /**
     * Create a new instance of this class.
     *
     * @param int $id
     * @param int $masterid
     * @param int|null $categoryid
     * @param int|null $parentid
     * @param int|null $roomid
     * @param int|null $roleid
     * @param string $title
     * @param string $description
     * @param int $itemtype
     * @param string|null $options
     * @param int $sortorder
     * @param int $isrequired
     * @param string|null $defaultvalue
     * @param int|null $due_days_offset
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
        /** @var ?int roomid */
        public ?int $roomid,
        /** @var ?int roleid */
        public ?int $roleid,
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
                $record->roomid,
                $record->roleid,
                $record->title,
                $record->description,
                $record->itemtype,
                $record->options,
                $record->sortorder,
                $record->isrequired,
                $record->defaultvalue,
                $record->duedaysoffset,
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
        $record->roomid = $this->roomid;
        $record->roleid = $this->roleid;
        $record->title = $this->title;
        $record->description = $this->description;
        $record->itemtype = $this->itemtype;
        $record->options = $this->options;
        $record->sortorder = $this->sortorder;
        $record->isrequired = $this->isrequired;
        $record->defaultvalue = $this->defaultvalue;
        $record->duedaysoffset = $this->duedaysoffset;
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

    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();

        $data->id = $this->id;
        $data->title = $this->title;
        $data->order = $this->sortorder;
        $data->categoryid = $this->categoryid;

        $data->roomid = $this->roomid;
        $data->roomname = \mod_bookit\local\manager\checklist_manager::get_roomname_by_id($this->roomid);
        $data->roleid = $this->roleid;
        $data->rolename = \mod_bookit\local\manager\checklist_manager::get_rolename_by_id($this->roleid);

        $data->type = 'item';

        return $data;
    }
}
