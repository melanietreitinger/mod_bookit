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
 * Access helpers for event overview actions.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use context_module;
use stdClass;

/**
 * Centralises booking-state and participant checks for event-level views.
 */
class event_access_manager {
    /** Booking status: in progress (being processed by service team). */
    public const BOOKINGSTATUS_IN_PROGRESS = 1;

    /** Confirmed booking status. */
    public const BOOKINGSTATUS_CONFIRMED = 2;

    /**
     * Check whether the event booking has been confirmed by the service team.
     *
     * @param stdClass $event
     * @return bool
     */
    public static function is_booking_confirmed(stdClass $event): bool {
        return (int)($event->bookingstatus ?? -1) === self::BOOKINGSTATUS_CONFIRMED;
    }

    /**
     * Check whether the event booking is accessible for checklist/resources.
     * Returns true when status is "In progress" (1) or "Accepted" (2).
     *
     * @param stdClass $event
     * @return bool
     */
    public static function is_booking_accessible(stdClass $event): bool {
        $status = (int)($event->bookingstatus ?? -1);
        return $status === self::BOOKINGSTATUS_IN_PROGRESS || $status === self::BOOKINGSTATUS_CONFIRMED;
    }

    /**
     * Check whether the user participates in the event in any BookIt role.
     *
     * @param stdClass $event
     * @param int $userid
     * @return bool
     */
    public static function user_participates_in_event(stdClass $event, int $userid): bool {
        if ((int)($event->personinchargeid ?? 0) === $userid) {
            return true;
        }

        if ((int)($event->usermodified ?? 0) === $userid) {
            return true;
        }

        if (in_array($userid, self::parse_csv_ids($event->otherexaminers ?? ''), true)) {
            return true;
        }

        return in_array($userid, self::parse_csv_ids($event->supportpersons ?? ''), true);
    }

    /**
     * Check whether the current user may open the event checklist page.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_view_event_checklist(stdClass $event, context_module $context, int $userid): bool {
        if (has_capability('mod/bookit:managebasics', $context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (!self::is_booking_accessible($event)) {
            return false;
        }

        if (!has_capability('mod/bookit:viewalldetailsofownevent', $context)) {
            return false;
        }

        return self::user_participates_in_event($event, $userid);
    }

    /**
     * Check whether the current user may open the event resources page.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_view_event_resources(stdClass $event, context_module $context, int $userid): bool {
        if (has_capability('mod/bookit:managebasics', $context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (!self::is_booking_accessible($event)) {
            return false;
        }

        if (!has_capability('mod/bookit:viewalldetailsofownevent', $context)) {
            return false;
        }

        return self::user_participates_in_event($event, $userid);
    }

    /**
     * Check whether the user may toggle a specific checklist item.
     *
     * Service team may toggle all items. Other event participants may only toggle
     * items assigned to one of their BookIt roles.
     *
     * @param stdClass $event
     * @param int[]|null $itemroleids
     * @param context_module $context
     * @param int $userid
     * @param int[] $userroleids
     * @return bool
     */
    public static function can_toggle_event_checklist_item(
        stdClass $event,
        ?array $itemroleids,
        context_module $context,
        int $userid,
        array $userroleids = []
    ): bool {
        if (!self::can_view_event_checklist($event, $context, $userid)) {
            return false;
        }

        if (has_capability('mod/bookit:managebasics', $context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (empty($itemroleids)) {
            return true;
        }

        if (empty($userroleids)) {
            $userroleids = checklist_manager::get_user_bookit_role_ids($userid);
        }

        $itemroleids = array_map('intval', $itemroleids);
        return !empty(array_intersect($itemroleids, $userroleids));
    }

    /**
     * Parse comma-separated user IDs into an integer array.
     *
     * @param string $ids
     * @return int[]
     */
    private static function parse_csv_ids(string $ids): array {
        if ($ids === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $ids))));
    }
}
