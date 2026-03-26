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
 * Entity class for resource settings metadata.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\entity\resource;

/**
 * Entity class for resource settings.
 *
 * Extends resources with settings metadata like due dates,
 * notification configuration and independent sort order.
 */
class bookit_resource_settings {
    /** @var ?int Database ID */
    private ?int $id;

    /** @var int Foreign key to bookit_resource */
    private int $resourceid;

    /** @var ?int Default due date offset in seconds (null = no default) */
    private ?int $duedate;

    /** @var ?string Due date type: before_event, after_event, fixed_date */
    private ?string $duedatetype;

    /** @var int Independent sort order for checklist view */
    private int $sortorder;

    /** @var ?int FK to notification_slot (before due) */
    private ?int $beforedueid;

    /** @var ?int FK to notification_slot (when due) */
    private ?int $whendueid;

    /** @var ?int FK to notification_slot (overdue) */
    private ?int $overdueid;

    /** @var ?int FK to notification_slot (when done) */
    private ?int $whendoneid;

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
     * @param int $resourceid Resource ID
     * @param ?int $duedate Due date offset in seconds
     * @param ?string $duedatetype Due date type
     * @param int $sortorder Sort order
     * @param ?int $beforedueid Before due notification slot ID
     * @param ?int $whendueid When due notification slot ID
     * @param ?int $overdueid Overdue notification slot ID
     * @param ?int $whendoneid When done notification slot ID
     * @param int $timecreated Creation timestamp
     * @param int $timemodified Modification timestamp
     * @param int $usermodified User ID
     */
    public function __construct(
        ?int $id = null,
        int $resourceid = 0,
        ?int $duedate = null,
        ?string $duedatetype = null,
        int $sortorder = 0,
        ?int $beforedueid = null,
        ?int $whendueid = null,
        ?int $overdueid = null,
        ?int $whendoneid = null,
        int $timecreated = 0,
        int $timemodified = 0,
        int $usermodified = 0
    ) {
        $this->id = $id;
        $this->resourceid = $resourceid;
        $this->duedate = $duedate;
        $this->duedatetype = $duedatetype;
        $this->sortorder = $sortorder;
        $this->beforedueid = $beforedueid;
        $this->whendueid = $whendueid;
        $this->overdueid = $overdueid;
        $this->whendoneid = $whendoneid;
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
            (int)($record->resourceid ?? 0),
            isset($record->duedate) ? (int)$record->duedate : null,
            $record->duedatetype ?? null,
            (int)($record->sortorder ?? 0),
            isset($record->beforedueid) ? (int)$record->beforedueid : null,
            isset($record->whendueid) ? (int)$record->whendueid : null,
            isset($record->overdueid) ? (int)$record->overdueid : null,
            isset($record->whendoneid) ? (int)$record->whendoneid : null,
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
     * Get resource ID.
     *
     * @return int
     */
    public function get_resourceid(): int {
        return $this->resourceid;
    }

    /**
     * Set resource ID.
     *
     * @param int $resourceid
     */
    public function set_resourceid(int $resourceid): void {
        $this->resourceid = $resourceid;
    }

    /**
     * Get due date offset.
     *
     * @return ?int
     */
    public function get_duedate(): ?int {
        return $this->duedate;
    }

    /**
     * Set due date offset.
     *
     * @param ?int $duedate
     */
    public function set_duedate(?int $duedate): void {
        $this->duedate = $duedate;
    }

    /**
     * Get due date type.
     *
     * @return ?string
     */
    public function get_duedatetype(): ?string {
        return $this->duedatetype;
    }

    /**
     * Set due date type.
     *
     * @param ?string $duedatetype
     */
    public function set_duedatetype(?string $duedatetype): void {
        $this->duedatetype = $duedatetype;
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
     * Set sort order.
     *
     * @param int $sortorder
     */
    public function set_sortorder(int $sortorder): void {
        $this->sortorder = $sortorder;
    }

    /**
     * Get before due notification slot ID.
     *
     * @return ?int
     */
    public function get_beforedueid(): ?int {
        return $this->beforedueid;
    }

    /**
     * Set before due notification slot ID.
     *
     * @param ?int $beforedueid
     */
    public function set_beforedueid(?int $beforedueid): void {
        $this->beforedueid = $beforedueid;
    }

    /**
     * Get when due notification slot ID.
     *
     * @return ?int
     */
    public function get_whendueid(): ?int {
        return $this->whendueid;
    }

    /**
     * Set when due notification slot ID.
     *
     * @param ?int $whendueid
     */
    public function set_whendueid(?int $whendueid): void {
        $this->whendueid = $whendueid;
    }

    /**
     * Get overdue notification slot ID.
     *
     * @return ?int
     */
    public function get_overdueid(): ?int {
        return $this->overdueid;
    }

    /**
     * Set overdue notification slot ID.
     *
     * @param ?int $overdueid
     */
    public function set_overdueid(?int $overdueid): void {
        $this->overdueid = $overdueid;
    }

    /**
     * Get when done notification slot ID.
     *
     * @return ?int
     */
    public function get_whendoneid(): ?int {
        return $this->whendoneid;
    }

    /**
     * Set when done notification slot ID.
     *
     * @param ?int $whendoneid
     */
    public function set_whendoneid(?int $whendoneid): void {
        $this->whendoneid = $whendoneid;
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
     * Get user ID who last modified.
     *
     * @return int
     */
    public function get_usermodified(): int {
        return $this->usermodified;
    }

    /**
     * Set user ID who last modified.
     *
     * @param int $usermodified
     */
    public function set_usermodified(int $usermodified): void {
        $this->usermodified = $usermodified;
    }
}
