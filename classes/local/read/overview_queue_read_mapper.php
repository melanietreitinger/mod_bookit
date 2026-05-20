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

namespace mod_bookit\local\read;

use context_module;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use stdClass;

/**
 * Maps overview queue events to the governed read-model shape.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview_queue_read_mapper {
    /**
     * Map an overview event to the governed queue model.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @param array $statuscolors
     * @param stdClass|null $latesthistory
     * @return array
     */
    public static function map(
        stdClass $event,
        context_module $context,
        int $userid,
        array $statuscolors = [],
        ?stdClass $latesthistory = null
    ): array {
        $bookingstatus = (int)($event->bookingstatus ?? -1);
        $historyclassification = event_manager::is_event_in_history($event)
            ? 'history'
            : (event_manager::is_hidden_from_active_overview($event) ? 'hidden_from_active' : 'active');
        $statusstyle = $statuscolors[$bookingstatus] ?? ['bg' => '#ffffff', 'fg' => '#000000'];

        return [
            'eventid' => (int)$event->id,
            'id' => (int)$event->id,
            'name' => (string)($event->name ?? ''),
            'bookingstatus' => $bookingstatus,
            'starttime' => (int)($event->starttime ?? 0),
            'endtime' => (int)($event->endtime ?? 0),
            'room' => (string)($event->room ?? ''),
            'historyclassification' => $historyclassification,
            'datestr' => userdate((int)($event->starttime ?? 0)),
            'actions' => [
                'caneventdetails' => event_access_manager::can_user_view_event_details($event, $context, $userid),
                'canreactivate' => event_access_manager::can_reactivate_rejected_request($event, $context),
                'allowedstatuses' => array_map(
                    static fn(array $option): int => (int)$option['value'],
                    event_manager::get_booking_status_transition_options($bookingstatus, true)
                ),
            ],
            'statusstyle' => 'background-color:' . $statusstyle['bg'] . ';color:' . $statusstyle['fg'] . ';',
            'statustext' => get_string('event_bookingstatus_' . $bookingstatus, 'mod_bookit'),
            'latesthistorysummary' => self::map_latest_history_summary($latesthistory),
        ];
    }

    /**
     * Convert the latest history entry into a short human-readable summary.
     *
     * @param stdClass|null $latesthistory
     * @return string
     */
    private static function map_latest_history_summary(?stdClass $latesthistory): string {
        if (!$latesthistory) {
            return '';
        }

        $action = trim((string)($latesthistory->action ?? ''));
        if ($action === '') {
            return '';
        }

        return get_string('overview_workflow_history', 'mod_bookit') . ': ' . $action;
    }
}
