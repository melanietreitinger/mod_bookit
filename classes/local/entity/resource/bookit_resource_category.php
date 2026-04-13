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

namespace mod_bookit\local\entity\resource;

/**
 * Entity class for resource categories.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_resource_category {
    /** @var ?int Database ID */
    private ?int $id;

    /** @var string Category name */
    private string $name;

    /** @var ?string Optional description */
    private ?string $description;

    /** @var int Sort order for drag and drop */
    private int $sortorder;

    /** @var int Unix timestamp of creation */
    private int $timecreated;

    /** @var int Unix timestamp of last modification */
    private int $timemodified;

    /** @var int User ID who last modified */
    private int $usermodified;

    /**
     * Constructor.
     *
     * @param ?int $id Database ID, null for new objects
     * @param string $name Category name
     * @param ?string $description Optional description
     * @param int $sortorder Sort order
     * @param int $timecreated Creation timestamp
     * @param int $timemodified Modification timestamp
     * @param int $usermodified User ID
     */
    public function __construct(
        ?int $id = null,
        string $name = '',
        ?string $description = null,
        int $sortorder = 0,
        int $timecreated = 0,
        int $timemodified = 0,
        int $usermodified = 0
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->sortorder = $sortorder;
        $this->timecreated = $timecreated;
        $this->timemodified = $timemodified;
        $this->usermodified = $usermodified;
    }

    /**
     * Create entity from database record.
     *
     * @param \stdClass $record Database record
     * @return self
     */
    public static function from_record(\stdClass $record): self {
        return new self(
            isset($record->id) ? (int)$record->id : null,
            $record->name ?? '',
            $record->description ?? null,
            (int)($record->sortorder ?? 0),
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0),
            (int)($record->usermodified ?? 0)
        );
    }

    /**
     * Get database ID.
     *
     * @return ?int
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get category name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get description.
     *
     * @return ?string
     */
    public function get_description(): ?string {
        return $this->description;
    }

    /**
     * Get sort order.
     *
     * @return int
     */
    public function get_sortorder(): int {
        return $this->sortorder;
    }

    /**
     * Get creation timestamp.
     *
     * @return int
     */
    public function get_timecreated(): int {
        return $this->timecreated;
    }

    /**
     * Get modification timestamp.
     *
     * @return int
     */
    public function get_timemodified(): int {
        return $this->timemodified;
    }

    /**
     * Get user who last modified.
     *
     * @return int
     */
    public function get_usermodified(): int {
        return $this->usermodified;
    }

    /**
     * Set category name.
     *
     * @param string $name
     * @return void
     */
    public function set_name(string $name): void {
        $this->name = $name;
    }

    /**
     * Set description.
     *
     * @param ?string $description
     * @return void
     */
    public function set_description(?string $description): void {
        $this->description = $description;
    }

    /**
     * Set sort order.
     *
     * @param int $sortorder
     * @return void
     */
    public function set_sortorder(int $sortorder): void {
        $this->sortorder = $sortorder;
    }
}
