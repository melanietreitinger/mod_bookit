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
 * Database class for bookit_checklist_master.
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
 * Database class for bookit_checklist_master.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_checklist_master implements \renderable, \templatable {

    /**
     * Table columns
     */
    public const DISPLAY_TABLE_COLUMNS = [
        'checklistitem',
        'room',
        'responsibility',
        'edit',
        'sort',
    ];

    /**
     * Create a new instance of this class.
     *
     * @param int $id
     * @param string $name
     * @param string $description
     * @param int $isdefault
     * @param $checklistcategories
     * @param int|null $usermodified
     * @param int|null $timecreated
     * @param int|null $timemodified
     */
    public function __construct(
        /** @var int id */
        public ?int $id,
        /** @var string name */
        public string $name,
        /** @var string description */
        public string $description,
        /** @var int isdefault */
        public ?int $isdefault = null,
        /** @var checklistcategories */
        public $checklistcategories = null,
        /** @var string|null mastercategoryorder */
        public ?string $mastercategoryorder = null,
        /** @var ?int usermodified */
        public ?int $usermodified = null,
        /** @var ?int timecreated */
        public ?int $timecreated = null,
        /** @var ?int timemodified */
        public ?int $timemodified = null,
    ) {
        global $USER, $PAGE;

        $now = time();
        $this->isdefault ??= 0;
        $this->checklistcategories ??= '';
        $this->usermodified ??= $USER->id;
        $this->timecreated ??= $now;
        $this->timemodified ??= $now;

        $PAGE->requires->js_call_amd('mod_bookit/master_checklist_reactive', 'init', ['mod-bookit-master-checklist']);
    }

    /**
     * Get record from database.
     *
     * @param int $id id of checklist master to fetch.
     * @return self
     * @throws dml_exception
     */
    public static function from_database(int $id): self {
        global $DB;
        $record = $DB->get_record("bookit_checklist_master", ["id" => $id], '*', MUST_EXIST);

        if (!empty($record->checklistcategories)) {

            $checklistcategories = checklist_manager::get_categories_by_master_id($record->id);

            $categoryorder = array_map('intval', explode(',', $record->checklistcategories));
            $sortedcategories = [];

            $categorymap = [];
            foreach ($checklistcategories as $category) {
                $categorymap[$category->id] = $category;
            }

            foreach ($categoryorder as $categoryid) {
                if (isset($categorymap[$categoryid])) {
                    $sortedcategories[] = $categorymap[$categoryid];
                }
            }

            $checklistcategories = $sortedcategories;
        } else {
            $checklistcategories = [];
        }

        return new self(
                $record->id,
                $record->name,
                $record->description,
                $record->isdefault,
                $checklistcategories,
                $record->checklistcategories,
                $record->usermodified,
                $record->timecreated,
                $record->timemodified
        );
    }

    /**
     * Save this checklist master to the database.
     *
     * @return int ID of the saved record
     * @throws dml_exception
     */
    public function save(): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->name = $this->name;
        $record->description = $this->description;
        $record->isdefault = $this->isdefault;
        $record->checklistcategories = $this->mastercategoryorder ?? '';
        $record->usermodified = $USER->id;
        $record->timemodified = time();

        if (empty($this->id)) {
            $record->timecreated = time();
            $this->id = $DB->insert_record("bookit_checklist_master", $record);
            return $this->id;
        } else {
            $record->id = $this->id;
            $DB->update_record("bookit_checklist_master", $record);
            return $this->id;
        }
    }

    /**
     * Delete this checklist master from the database.
     *
     * @return bool Success
     * @throws dml_exception
     */
    public function delete(): bool {
        // TODO remove or implement
        return true;
    }

    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();

        $tableheaders = [];
        foreach (self::DISPLAY_TABLE_COLUMNS as $column) {
            $tableheaders[] = get_string($column, 'mod_bookit');
        }

        $data->id = $this->id;
        $data->name = $this->name;
        $data->mastercategoryorder = $this->mastercategoryorder;
        $data->tableheaders = $tableheaders;
        $data->checklistcategories = [];
        $data->roles = checklist_manager::get_bookit_roles();
        $data->rooms = checklist_manager::get_bookit_rooms();

        foreach ($this->checklistcategories as $category) {
            $data->checklistcategories[] = $category->export_for_template($output);
        }

        return $data;
    }
}
