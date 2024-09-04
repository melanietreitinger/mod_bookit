<?php

namespace mod_bookit\local\entity;

/**
 * Database class for bookit_resources.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category {

    public ?int $id;
    public string $name;
    public ?string $description;
    public ?int $usermodified;
    public ?int $timecreated;
    public ?int $timemodified;

    /**
     * @param string $name
     * @param string|null $description
     * @param int $usermodified
     * @param int $timecreated
     * @param int $timemodified
     * @param int|null $id
     */
    public function __construct(string $name, ?string $description, int $usermodified = null, int $timecreated = null,
            int $timemodified = null, ?int $id = null) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->usermodified = $usermodified;
        $this->timecreated = $timecreated;
        $this->timemodified = $timemodified;
    }

    /**
     * @param int $id id of event to fetch.
     * @return self
     */
    public static function from_database($id) {
        global $DB;
        $record = $DB->get_record("bookit_category", array("id" => $id), '*', MUST_EXIST);

        return self::from_record($record);
    }

    /**
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
