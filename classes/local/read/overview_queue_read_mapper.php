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
use core_user;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\output\booking_status_cell;
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
     * @param stdClass|null $latesthistory
     * @param string $workspace
     * @param int $cmid
     * @return array
     */
    public static function map(
        stdClass $event,
        context_module $context,
        int $userid,
        ?stdClass $latesthistory = null,
        string $workspace = 'openrequests',
        int $cmid = 0
    ): array {
        global $PAGE;

        $bookingstatus = (int)($event->bookingstatus ?? -1);
        $historyclassification = event_manager::is_event_in_history($event)
            ? 'history'
            : (event_manager::is_hidden_from_active_overview($event) ? 'hidden_from_active' : 'active');
        $statusgroupkey = event_manager::get_booking_status_group_key($bookingstatus);
        $roles = [];
        foreach (event_access_manager::get_user_roles_for_event($event, $userid) as $role) {
            if ($role === 'personincharge') {
                $roles[] = get_string('overview_role_personincharge', 'mod_bookit');
            } else if ($role === 'bookingperson') {
                $roles[] = get_string('overview_role_bookingperson', 'mod_bookit');
            } else if ($role === 'otherexaminer') {
                $roles[] = get_string('overview_role_otherexaminer', 'mod_bookit');
            } else if ($role === 'supportperson') {
                $roles[] = get_string('overview_role_supportperson', 'mod_bookit');
            }
        }

        $personincharge = '-';
        if (!empty($event->personinchargeid)) {
            $user = core_user::get_user((int)$event->personinchargeid);
            if ($user) {
                $personincharge = fullname($user);
            }
        }

        $latesthistorysummary = self::map_latest_history_summary($latesthistory);
        $canmanagebasics = has_capability('mod/bookit:managebasics', $context);
        $statuscell = booking_status_cell::for_booking_overview_row(
            $event,
            $context,
            $userid,
            $cmid,
            $canmanagebasics,
            true,
            $workspace,
            $latesthistorysummary,
            []
        );
        $bookitrenderer = $PAGE->get_renderer('mod_bookit');
        $statuscellhtml = $bookitrenderer->render($statuscell);

        return [
            'eventid' => (int)$event->id,
            'id' => (int)$event->id,
            'name' => (string)($event->name ?? ''),
            'bookingstatus' => $bookingstatus,
            'starttime' => (int)($event->starttime ?? 0),
            'endtime' => (int)($event->endtime ?? 0),
            'room' => (string)($event->room ?? ''),
            'personincharge' => $personincharge,
            'myrole' => $roles ? implode(', ', $roles) : '-',
            'historyclassification' => $historyclassification,
            'datestr' => userdate((int)($event->starttime ?? 0), '%d.%m.%Y'),
            'actions' => [
                'caneventdetails' => event_access_manager::can_user_view_event_details($event, $context, $userid),
            ],
            'statusgroupkey' => $statusgroupkey,
            'statuscellhtml' => $statuscellhtml,
            'savebuttontext' => event_access_manager::should_block_participant_past_edit($event, $context, $userid)
                ? get_string('event_past_participant_close', 'mod_bookit')
                : (
                    event_access_manager::can_participant_cancel_only($event, $context, $userid)
                        ? get_string('event_status_action_cancel_only', 'mod_bookit')
                        : ''
                ),
            'cancelbuttontext' => event_access_manager::can_participant_cancel_only($event, $context, $userid)
                ? get_string('event_status_action_close_modal', 'mod_bookit')
                : '',
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

        $summary = get_string('history_action_' . $action, 'mod_bookit') . ' · '
            . userdate((int)$latesthistory->timecreated, get_string('strftimedatetime', 'langconfig'));
        $actorname = trim(($latesthistory->firstname ?? '') . ' ' . ($latesthistory->lastname ?? ''));
        if ($actorname !== '') {
            $summary .= ' · ' . $actorname;
        }

        return $summary;
    }
}
