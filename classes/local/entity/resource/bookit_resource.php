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
 * Entity class for resources.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_resource {
    /** @var ?int Database ID */
    private ?int $id;

    /** @var string Resource name */
    private string $name;

    /** @var ?string Optional description */
    private ?string $description;

    /** @var int Foreign key to resource_categories */
    private int $categoryid;

    /** @var int Available amount */
    private int $amount;

    /** @var bool Flag: amount is irrelevant */
    private bool $amountirrelevant;

    /** @var int Sort order */
    private int $sortorder;

    /** @var bool Active/inactive flag */
    private bool $active;

    /** @var ?array Room IDs assigned to this resource; stored as JSON in DB, null means available in all rooms */
    private ?array $roomids;

    /** @var ?string Internal information visible only to admins */
    private ?string $internalinfo;

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
     * @param string $name Resource name
     * @param ?string $description Optional description
     * @param int $categoryid Category ID
     * @param int $amount Available amount
     * @param bool $amountirrelevant Amount irrelevant flag
     * @param int $sortorder Sort order
     * @param bool $active Active flag
     * @param ?array $roomids Room IDs or null if available in all rooms
     * @param int $timecreated Creation timestamp
     * @param int $timemodified Modification timestamp
     * @param int $usermodified User ID
     * @param ?string $internalinfo Internal information for admins
     */
    public function __construct(
        ?int $id = null,
        string $name = '',
        ?string $description = null,
        int $categoryid = 0,
        int $amount = 0,
        bool $amountirrelevant = false,
        int $sortorder = 0,
        bool $active = true,
        ?array $roomids = null,
        int $timecreated = 0,
        int $timemodified = 0,
        int $usermodified = 0,
        ?string $internalinfo = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->categoryid = $categoryid;
        $this->amount = $amount;
        $this->amountirrelevant = $amountirrelevant;
        $this->sortorder = $sortorder;
        $this->active = $active;
        $this->roomids = $roomids;
        $this->internalinfo = $internalinfo;
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
        $roomids = null;
        if (isset($record->roomids) && $record->roomids !== null) {
            $decoded = json_decode($record->roomids, true);
            $roomids = is_array($decoded) ? $decoded : null;
        }

        return new self(
            isset($record->id) ? (int)$record->id : null,
            $record->name ?? '',
            $record->description ?? null,
            (int)($record->categoryid ?? 0),
            (int)($record->amount ?? 0),
            (bool)($record->amountirrelevant ?? 0),
            (int)($record->sortorder ?? 0),
            (bool)($record->active ?? 1),
            $roomids,
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0),
            (int)($record->usermodified ?? 0),
            $record->internalinfo ?? null
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
     * Get resource name.
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
     * Get category ID.
     *
     * @return int
     */
    public function get_categoryid(): int {
        return $this->categoryid;
    }

    /**
     * Get available amount.
     *
     * @return int
     */
    public function get_amount(): int {
        return $this->amount;
    }

    /**
     * Check if amount is irrelevant.
     *
     * @return bool
     */
    public function is_amountirrelevant(): bool {
        return $this->amountirrelevant;
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
     * Check if resource is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->active;
    }

    /**
     * Get room IDs.
     *
     * @return ?array
     */
    public function get_roomids(): ?array {
        return $this->roomids;
    }

    /**
     * Get internal information.
     *
     * @return ?string
     */
    public function get_internalinfo(): ?string {
        return $this->internalinfo;
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
     * Set resource name.
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
     * Set category ID.
     *
     * @param int $categoryid
     * @return void
     */
    public function set_categoryid(int $categoryid): void {
        $this->categoryid = $categoryid;
    }

    /**
     * Set available amount.
     *
     * @param int $amount
     * @return void
     */
    public function set_amount(int $amount): void {
        $this->amount = $amount;
    }

    /**
     * Set amount irrelevant flag.
     *
     * @param bool $amountirrelevant
     * @return void
     */
    public function set_amountirrelevant(bool $amountirrelevant): void {
        $this->amountirrelevant = $amountirrelevant;
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

    /**
     * Set active flag.
     *
     * @param bool $active
     * @return void
     */
    public function set_active(bool $active): void {
        $this->active = $active;
    }

    /**
     * Set room IDs.
     *
     * @param ?array $roomids
     * @return void
     */
    public function set_roomids(?array $roomids): void {
        $this->roomids = $roomids;
    }

    /**
     * Set internal information.
     *
     * @param ?string $internalinfo
     * @return void
     */
    public function set_internalinfo(?string $internalinfo): void {
        $this->internalinfo = $internalinfo;
    }
}
