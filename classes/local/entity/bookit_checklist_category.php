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
 * Database class for bookit_checklist_category.
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
 * Database class for bookit_checklist_category.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_checklist_category implements \renderable, \templatable {

    /**
     * Create a new instance of this class.
     *
     * @param int $id
     * @param int $masterid
     * @param string $name
     * @param string|null $description
     * @param int $sortorder
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     */
    public function __construct(
        /** @var int id */
        public ?int $id,
        /** @var int masterid */
        public int $masterid,
        /** @var string name */
        public string $name,
        /** @var ?string description */
        public ?string $description,
        /** @var ?array checklistitems */
        public $checklistitems = null,
        /** @var int sortorder */
        public int $sortorder = 0,
        /** @var ?int usermodified */
        public ?int $usermodified = null,
        /** @var ?int timecreated */
        public ?int $timecreated = null,
        /** @var ?int timemodified */
        public ?int $timemodified = null,
    ) {

        global $USER;

        $now = time();
        $this->checklistitems ??= '';
        $this->usermodified ??= $USER->id;
        $this->timecreated ??= $now;
        $this->timemodified ??= $now;
    }

    /**
     * Get record from database.
     *
     * @param int $id id of checklist category to fetch.
     * @return self
     * @throws dml_exception
     */
    public static function from_database(int $id): self {
        global $DB;
        $record = $DB->get_record("bookit_checklist_category", ["id" => $id], '*', MUST_EXIST);
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
                $record->name,
                $record->description,
                $record->checklistitems,
                $record->sortorder,
                $record->usermodified,
                $record->timecreated,
                $record->timemodified
        );
    }

    /**
     * Save this checklist category to the database.
     *
     * @return int ID of the saved record
     * @throws dml_exception
     */
    public function save(): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->masterid = $this->masterid;
        $record->name = $this->name;
        $record->description = $this->description;
        $record->checklistitems = trim($this->checklistitems, '"');
        $record->sortorder = $this->sortorder;
        $record->usermodified = $USER->id;
        $record->timemodified = time();

        if (empty($this->id)) {
            $record->timecreated = time();
            $this->id = $DB->insert_record("bookit_checklist_category", $record);
            return $this->id;
        } else {
            $record->id = $this->id;
            $DB->update_record("bookit_checklist_category", $record);
            return $this->id;
        }
    }

    /**
     * Delete this checklist category from the database.
     *
     * @return bool Success
     * @throws dml_exception
     */
    public function delete(): bool {
        global $DB;

        return $DB->delete_records("bookit_checklist_category", ["id" => $this->id]);
    }

    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();

        $data->id = $this->id;
        $data->name = $this->name;
        $data->order = $this->sortorder;
        $data->masterid = $this->masterid;

        $data->checklistitems = [];

        if (!empty($this->checklistitems)) {
            $itemids = explode(',', $this->checklistitems);

            foreach ($itemids as $itemid) {

                $item = bookit_checklist_item::from_database($itemid);

                $data->checklistitems[] = $item->export_for_template($output);
            }
        }

        $data->type = 'category';

        return $data;
    }

}
