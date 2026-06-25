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
use mod_bookit\external\get_possible_starttimes;
use mod_bookit\local\install_helper;
use stdClass;

/**
 * Centralises booking-state and participant checks for event-level views.
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * @SuppressWarnings(PHPMD)
 */
class event_access_manager {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /** Booking status: new request. */
    public const BOOKINGSTATUS_NEW = 0;

    /** Booking status: in progress (being processed by service team). */
    public const BOOKINGSTATUS_IN_PROGRESS = 1;

    /** Booking status: accepted by service team. */
    public const BOOKINGSTATUS_ACCEPTED = 2;

    /** Booking status: canceled. */
    public const BOOKINGSTATUS_CANCELED = 3;

    /** Booking status: rejected. */
    public const BOOKINGSTATUS_REJECTED = 4;

    /** @var int[] booking statuses that still require service-team action. */
    public const OPEN_BOOKING_STATUSES = [
        self::BOOKINGSTATUS_NEW,
        self::BOOKINGSTATUS_IN_PROGRESS,
    ];

    /** @var array<int, int[]> allowed workflow transitions keyed by current status. */
    private const ALLOWED_TRANSITIONS = [
        self::BOOKINGSTATUS_NEW => [
            self::BOOKINGSTATUS_IN_PROGRESS,
            self::BOOKINGSTATUS_ACCEPTED,
            self::BOOKINGSTATUS_CANCELED,
            self::BOOKINGSTATUS_REJECTED,
        ],
        self::BOOKINGSTATUS_IN_PROGRESS => [
            self::BOOKINGSTATUS_ACCEPTED,
            self::BOOKINGSTATUS_CANCELED,
            self::BOOKINGSTATUS_REJECTED,
        ],
        self::BOOKINGSTATUS_ACCEPTED => [
            self::BOOKINGSTATUS_CANCELED,
        ],
        self::BOOKINGSTATUS_CANCELED => [
            self::BOOKINGSTATUS_NEW,
            self::BOOKINGSTATUS_IN_PROGRESS,
            self::BOOKINGSTATUS_ACCEPTED,
        ],
        self::BOOKINGSTATUS_REJECTED => [
            self::BOOKINGSTATUS_NEW,
        ],
    ];

    /**
     * Check whether the event booking has been confirmed by the service team.
     *
     * @param stdClass $event
     * @return bool
     */
    public static function is_booking_confirmed(stdClass $event): bool {
        return (int)($event->bookingstatus ?? -1) === self::BOOKINGSTATUS_ACCEPTED;
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
        return $status === self::BOOKINGSTATUS_IN_PROGRESS || $status === self::BOOKINGSTATUS_ACCEPTED;
    }

    /**
     * Check whether the event is still an open request for service-team processing.
     *
     * @param stdClass $event
     * @return bool
     */
    public static function is_open_request(stdClass $event): bool {
        return in_array((int)($event->bookingstatus ?? -1), self::OPEN_BOOKING_STATUSES, true);
    }

    /**
     * Get the statuses that represent open requests.
     *
     * @return int[]
     */
    public static function get_open_request_statuses(): array {
        return self::OPEN_BOOKING_STATUSES;
    }

    /**
     * Check whether the event currently belongs to the rejected-request trash workflow.
     *
     * @param stdClass $event
     * @param int|null $referencetime Deprecated compatibility parameter, ignored.
     * @return bool
     */
    public static function is_rejected_request(stdClass $event, ?int $referencetime = null): bool {
        return (int)($event->bookingstatus ?? -1) === self::BOOKINGSTATUS_REJECTED;
    }

    /**
     * Return the allowed transitions for a given current booking status.
     *
     * @param int $status
     * @param bool $includecurrent
     * @return int[]
     */
    public static function get_allowed_booking_status_transitions(int $status, bool $includecurrent = false): array {
        $transitions = self::ALLOWED_TRANSITIONS[$status] ?? [];
        if ($includecurrent) {
            array_unshift($transitions, $status);
        }
        return array_values(array_unique(array_map('intval', $transitions)));
    }

    /**
     * Check whether a status transition is allowed by the workflow.
     *
     * @param int $fromstatus
     * @param int $tostatus
     * @return bool
     */
    public static function can_transition_booking_status(int $fromstatus, int $tostatus): bool {
        if ($fromstatus === $tostatus) {
            return true;
        }
        return in_array($tostatus, self::get_allowed_booking_status_transitions($fromstatus), true);
    }

    /**
     * Check whether the current user may manage open requests.
     *
     * @param context_module $context
     * @return bool
     */
    public static function can_manage_open_requests(context_module $context): bool {
        return has_capability('mod/bookit:managebasics', $context)
            || has_capability('mod/bookit:viewalldetailsofevent', $context);
    }

    /**
     * Check whether the current user may request bookings in the past.
     *
     * @param context_module $context
     * @return bool
     */
    public static function can_manage_past_bookings(context_module $context): bool {
        return has_capability('mod/bookit:managebasics', $context)
            || has_capability('mod/bookit:editevent', $context);
    }

    /**
     * Check whether the optional checklist module is enabled.
     *
     * @return bool
     */
    public static function is_checklist_enabled(): bool {
        return install_helper::is_checklist_enabled();
    }

    /**
     * Check whether the optional resources module is enabled.
     *
     * @return bool
     */
    public static function is_resources_enabled(): bool {
        return install_helper::is_resources_enabled();
    }

    /**
     * Check whether the current user is in restricted observer mode.
     *
     * @param context_module $context
     * @return bool
     */
    public static function is_observer_restricted_mode(context_module $context): bool {
        if (!get_capability_info('mod/bookit:viewrestrictedobserver', false)) {
            return false;
        }

        if (!has_capability('mod/bookit:viewrestrictedobserver', $context)) {
            return false;
        }

        // Mixed-role users with service/detail access must never fall back to the
        // neutral observer projection because the calendar/export feed reads this
        // decision before the per-event visibility checks run.
        if (self::can_manage_open_requests($context)) {
            return false;
        }

        return true;
    }

    /**
     * Return all BookIt participant roles that the user has on the event.
     *
     * @param stdClass $event
     * @param int $userid
     * @return string[]
     */
    public static function get_user_roles_for_event(stdClass $event, int $userid): array {
        $roles = [];

        if ((int)($event->personinchargeid ?? 0) === $userid) {
            $roles[] = 'personincharge';
        }

        if ((int)($event->usermodified ?? 0) === $userid) {
            $roles[] = 'bookingperson';
        }

        if (in_array($userid, self::parse_csv_ids($event->otherexaminers ?? ''), true)) {
            $roles[] = 'otherexaminer';
        }

        if (in_array($userid, self::parse_csv_ids($event->supportpersons ?? ''), true)) {
            $roles[] = 'supportperson';
        }

        return $roles;
    }

    /**
     * Check whether the event can still be freely edited by a participant.
     *
     * @param stdClass $event
     * @param int $userid
     * @return bool
     */
    public static function can_participant_edit_event(stdClass $event, int $userid): bool {
        if ((int)($event->bookingstatus ?? self::BOOKINGSTATUS_NEW) !== self::BOOKINGSTATUS_NEW) {
            return false;
        }

        $roles = self::get_user_roles_for_event($event, $userid);
        return !empty(array_intersect($roles, ['bookingperson', 'personincharge', 'otherexaminer']));
    }

    /**
     * Check whether a participant-owned New booking must be blocked because it already lies in the past.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function should_block_participant_past_edit(stdClass $event, context_module $context, int $userid): bool {
        if (self::can_manage_past_bookings($context)) {
            return false;
        }

        if (!self::can_participant_edit_event($event, $userid)) {
            return false;
        }

        return get_possible_starttimes::is_starttime_in_past((int)($event->starttime ?? 0));
    }

    /**
     * Check whether the user may self-cancel a still-editable New request.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_self_cancel_new_request(stdClass $event, context_module $context, int $userid): bool {
        return self::can_participant_edit_event($event, $userid)
            && !self::can_manage_open_requests($context)
            && (int)($event->bookingstatus ?? self::BOOKINGSTATUS_NEW) === self::BOOKINGSTATUS_NEW;
    }

    /**
     * Check whether the user may cancel a booking from the personal overview row action.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_participant_overview_cancel(stdClass $event, context_module $context, int $userid): bool {
        if (self::should_block_participant_past_edit($event, $context, $userid)) {
            return false;
        }

        if (self::can_manage_open_requests($context)) {
            return false;
        }

        return self::can_self_cancel_new_request($event, $context, $userid)
            || self::can_participant_cancel_only($event, $context, $userid);
    }

    /**
     * Check whether the user may open the event details with full event data.
     *
     * Service-team users may always access the event. Other users need the own-event
     * detail capability and must participate in the booking. Support persons may access
     * the booking only once it has been accepted unless another participant role grants
     * wider visibility.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_user_view_event_details(stdClass $event, context_module $context, int $userid): bool {
        if (self::can_manage_open_requests($context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (self::is_observer_restricted_mode($context)) {
            return false;
        }

        if (!has_capability('mod/bookit:viewalldetailsofownevent', $context)) {
            return false;
        }

        $roles = self::get_user_roles_for_event($event, $userid);
        if (
            in_array('supportperson', $roles, true)
            && empty(array_intersect($roles, ['bookingperson', 'personincharge', 'otherexaminer']))
            && (int)($event->bookingstatus ?? -1) === self::BOOKINGSTATUS_NEW
        ) {
            return false;
        }

        return self::user_has_participant_visibility($event, $userid);
    }

    /**
     * Check whether the user may see the event in participant-facing overviews.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_user_view_event_in_overview(stdClass $event, context_module $context, int $userid): bool {
        if (self::is_rejected_request($event)) {
            return false;
        }

        if (self::can_manage_open_requests($context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (self::is_observer_restricted_mode($context)) {
            return self::is_booking_confirmed($event);
        }

        if (!has_capability('mod/bookit:viewownoverview', $context)) {
            return false;
        }

        return self::user_has_participant_visibility($event, $userid);
    }

    /**
     * Check whether the user may see the detailed calendar event instead of a reserved placeholder.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_user_view_event_in_calendar(stdClass $event, context_module $context, int $userid): bool {
        if (self::is_rejected_request($event)) {
            return false;
        }

        if (self::can_manage_open_requests($context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            if ((int)($event->bookingstatus ?? -1) === self::BOOKINGSTATUS_CANCELED) {
                return false;
            }

            return true;
        }

        if (self::is_observer_restricted_mode($context)) {
            return self::is_booking_confirmed($event);
        }

        if (self::is_support_on_site_user($context, $userid)) {
            $roles = self::get_user_roles_for_event($event, $userid);
            if (empty(array_intersect($roles, ['bookingperson', 'personincharge', 'otherexaminer']))) {
                $status = (int)($event->bookingstatus ?? -1);
                if (in_array($status, self::OPEN_BOOKING_STATUSES, true)) {
                    return true;
                }
                if ($status === self::BOOKINGSTATUS_ACCEPTED) {
                    return in_array($userid, self::parse_csv_ids($event->supportpersons ?? ''), true);
                }

                return false;
            }
        }

        return self::can_user_view_event_in_overview($event, $context, $userid);
    }

    /**
     * Check whether the user may see the event in the overview history.
     *
     * History follows the same visibility projection as the active overview so hidden
     * requests never reappear through a different entry point.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_user_view_event_in_history(stdClass $event, context_module $context, int $userid): bool {
        if (self::can_manage_open_requests($context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (self::is_observer_restricted_mode($context)) {
            return false;
        }

        return self::can_user_view_event_in_overview($event, $context, $userid);
    }

    /**
     * Check whether support-on-site fields may be shown to the user.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_supportperson_view_internal_fields(
        stdClass $event,
        context_module $context,
        int $userid
    ): bool {
        return in_array('supportperson', self::get_user_roles_for_event($event, $userid), true)
            && self::can_user_view_event_details($event, $context, $userid);
    }

    /**
     * Check whether the user may edit only the internal notes field.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_supportperson_edit_internal_notes(
        stdClass $event,
        context_module $context,
        int $userid
    ): bool {
        return self::can_supportperson_view_internal_fields($event, $context, $userid);
    }

    /**
     * Check whether the user may only cancel the event after the workflow left "New".
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_participant_cancel_only(stdClass $event, context_module $context, int $userid): bool {
        return self::can_cancel_event($event, $context, $userid)
            && !self::can_participant_edit_event($event, $userid)
            && !has_capability('mod/bookit:editevent', $context)
            && !has_capability('mod/bookit:editinternal', $context);
    }

    /**
     * Check whether the current user may cancel the event.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    public static function can_cancel_event(stdClass $event, context_module $context, int $userid): bool {
        if (self::can_manage_open_requests($context)) {
            return true;
        }

        if (
            in_array(
                (int)($event->bookingstatus ?? -1),
                [
                    self::BOOKINGSTATUS_CANCELED,
                    self::BOOKINGSTATUS_REJECTED,
                ],
                true
            )
        ) {
            return false;
        }

        return !empty(array_intersect(
            self::get_user_roles_for_event($event, $userid),
            ['bookingperson', 'personincharge', 'otherexaminer']
        ));
    }

    /**
     * Check whether a rejected request may be reactivated by the service team.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int|null $referencetime
     * @return bool
     */
    public static function can_reactivate_rejected_request(
        stdClass $event,
        context_module $context,
        ?int $referencetime = null
    ): bool {
        return self::can_manage_open_requests($context) && self::is_rejected_request($event, $referencetime);
    }

    /**
     * Check whether a cancelled booking may be restored to a prior valid state.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int|null $restoredstatus
     * @return bool
     */
    public static function can_restore_canceled_booking(
        stdClass $event,
        context_module $context,
        ?int $restoredstatus = null
    ): bool {
        if (!self::can_manage_open_requests($context)) {
            return false;
        }

        if ((int)($event->bookingstatus ?? -1) !== self::BOOKINGSTATUS_CANCELED) {
            return false;
        }

        if ($restoredstatus === null) {
            return false;
        }

        return self::can_transition_booking_status(self::BOOKINGSTATUS_CANCELED, $restoredstatus);
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
        if (!self::is_checklist_enabled()) {
            return false;
        }

        if (has_capability('mod/bookit:managebasics', $context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (!self::is_booking_accessible($event) || !self::can_user_view_event_details($event, $context, $userid)) {
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
        if (!self::is_resources_enabled()) {
            return false;
        }

        if (has_capability('mod/bookit:managebasics', $context) || has_capability('mod/bookit:viewalldetailsofevent', $context)) {
            return true;
        }

        if (!self::is_booking_accessible($event) || !self::can_user_view_event_details($event, $context, $userid)) {
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
     * Normalise a read-filter payload so read contracts can share one input shape.
     *
     * @param array $filters
     * @return array
     */
    public static function normalise_governed_read_filters(array $filters): array {
        return [
            'start' => self::normalise_governed_datetime_filter($filters['start'] ?? null),
            'end' => self::normalise_governed_datetime_filter($filters['end'] ?? null),
            'roomids' => self::normalise_governed_filter_ids($filters['roomids'] ?? []),
            'facultyids' => self::normalise_governed_filter_ids($filters['facultyids'] ?? []),
            'bookingstatuses' => self::normalise_governed_filter_ids($filters['bookingstatuses'] ?? []),
            'semesterids' => self::normalise_governed_filter_ids($filters['semesterids'] ?? []),
            'search' => self::normalise_governed_text_filter($filters['search'] ?? null),
            'workspace' => self::normalise_governed_text_filter($filters['workspace'] ?? null),
            'exportmode' => !empty($filters['exportmode']),
        ];
    }

    /**
     * Normalise integer-id filters from request payloads or comma-separated legacy values.
     *
     * @param mixed $values
     * @return int[]
     */
    public static function normalise_governed_filter_ids(mixed $values): array {
        if (is_string($values)) {
            $values = $values === '' ? [] : explode(',', $values);
        } else if (!is_array($values)) {
            $values = [$values];
        }

        return array_values(array_unique(array_map('intval', array_filter(
            $values,
            static fn(mixed $value): bool => $value !== '' && $value !== null
        ))));
    }

    /**
     * Normalise free-text search values used by governed reads.
     *
     * @param mixed $value
     * @return string
     */
    public static function normalise_governed_text_filter(mixed $value): string {
        if ($value === null) {
            return '';
        }

        return trim((string)$value);
    }

    /**
     * Normalise incoming datetimes to the legacy manager format.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function normalise_governed_datetime_filter(mixed $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        try {
            return (new \DateTime($text))->format('Y-m-d H:i');
        } catch (\Exception $exception) {
            throw new \invalid_parameter_exception('Invalid datetime filter: ' . $text);
        }
    }

    /**
     * Build a shared empty-state payload for governed read contracts.
     *
     * @param string $scope
     * @param array $filters
     * @param string $emptyreason
     * @return array
     */
    public static function build_governed_empty_response(
        string $scope,
        array $filters = [],
        string $emptyreason = 'no_matches'
    ): array {
        $filters = self::normalise_governed_read_filters($filters);
        if ($filters['start'] === null) {
            unset($filters['start']);
        }
        if ($filters['end'] === null) {
            unset($filters['end']);
        }

        return [
            'scope' => $scope,
            'status' => 'ok',
            'denied' => false,
            'emptyreason' => $emptyreason,
            'filters' => $filters,
        ];
    }

    /**
     * Build a shared denied-state payload for governed read contracts.
     *
     * @param string $scope
     * @param array $filters
     * @return array
     */
    public static function build_governed_denied_response(string $scope, array $filters = []): array {
        $filters = self::normalise_governed_read_filters($filters);
        if ($filters['start'] === null) {
            unset($filters['start']);
        }
        if ($filters['end'] === null) {
            unset($filters['end']);
        }

        return [
            'scope' => $scope,
            'status' => 'denied',
            'denied' => true,
            'emptyreason' => 'access_denied',
            'filters' => $filters,
        ];
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

    /**
     * Check whether the user has the support-on-site module role without broader read grants.
     *
     * @param context_module $context
     * @param int $userid
     * @return bool
     */
    private static function is_support_on_site_user(context_module $context, int $userid): bool {
        if (self::can_manage_open_requests($context)) {
            return false;
        }

        if (has_capability('mod/bookit:viewalldetailsofevent', $context, $userid)) {
            return false;
        }

        if (self::is_observer_restricted_mode($context)) {
            return false;
        }

        foreach (get_user_roles($context, $userid) as $role) {
            if ($role->shortname === 'bookit_supportonsite') {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve participant visibility for non-service-team users once so all booking views stay aligned.
     *
     * @param stdClass $event
     * @param int $userid
     * @return bool
     */
    private static function user_has_participant_visibility(stdClass $event, int $userid): bool {
        $roles = self::get_user_roles_for_event($event, $userid);
        if (empty($roles)) {
            return false;
        }

        if (!empty(array_intersect($roles, ['bookingperson', 'personincharge', 'otherexaminer']))) {
            return true;
        }

        return in_array('supportperson', $roles, true)
            && in_array((int)($event->bookingstatus ?? -1), [
                self::BOOKINGSTATUS_NEW,
                self::BOOKINGSTATUS_IN_PROGRESS,
                self::BOOKINGSTATUS_ACCEPTED,
            ], true);
    }
}
