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
 * Audit event for lifecycle status changes.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\event;

use context_module;
use core\event\base;
use stdClass;

/**
 * Audit event for lifecycle status changes.
 */
class booking_status_changed extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'bookit_event';
    }

    /**
     * Create an audit event from a booking record.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @param int $oldstatus
     * @param int $newstatus
     * @param string $action
     * @return self
     */
    public static function create_from_event(
        stdClass $event,
        context_module $context,
        int $userid,
        int $oldstatus,
        int $newstatus,
        string $action
    ): self {
        $audit = self::create([
            'objectid' => (int)$event->id,
            'context' => $context,
            'userid' => $userid,
            'other' => [
                'action' => $action,
                'oldstatus' => $oldstatus,
                'newstatus' => $newstatus,
                'eventname' => (string)($event->name ?? ''),
            ],
        ]);

        $audit->add_record_snapshot('bookit_event', $event);
        return $audit;
    }

    /**
     * Get localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventaudit_booking_status_changed', 'mod_bookit');
    }

    /**
     * Describe the event for logs.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' changed booking event '{$this->objectid}' from status "
            . "'{$this->other['oldstatus']}' to '{$this->other['newstatus']}' ({$this->other['action']}).";
    }
}
