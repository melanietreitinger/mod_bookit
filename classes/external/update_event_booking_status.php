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
 * External API for updating the booking status of an event.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\tabs;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * External API for updating the booking status of an event.
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * @SuppressWarnings(PHPMD)
 */
class update_event_booking_status extends external_api {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /**
     * Description of parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'    => new external_value(PARAM_INT, 'Course module ID'),
            'eventid' => new external_value(PARAM_INT, 'Event ID'),
            'status'  => new external_value(PARAM_INT, 'New booking status value (0-4)'),
            'tab' => new external_value(
                PARAM_ALPHA,
                'Overview tab to return to after the status update.',
                VALUE_DEFAULT,
                ''
            ),
            'page' => new external_value(PARAM_INT, 'Request workspace page to preserve', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Update event booking status.
     *
     * @param int $cmid Course module ID
     * @param int $eventid Event ID
     * @param int $status New booking status
     * @param string|null $tab Overview tab to return to
     * @param int $page Request workspace page to return to
     * @return array
     */
    public static function execute(int $cmid, int $eventid, int $status, ?string $tab = '', int $page = 0): array {
        global $DB, $OUTPUT, $USER;

        $rawparams = [
            'cmid' => $cmid,
            'eventid' => $eventid,
            'status' => $status,
            'tab' => $tab ?? '',
            'page' => $page,
        ];

        $params = self::validate_parameters(self::execute_parameters(), $rawparams);

        $cm = get_coursemodule_from_id('bookit', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $canviewrequestworkspace = event_access_manager::can_view_request_workspace($context);
        if (empty($params['tab'])) {
            $params['tab'] = tabs::get_default_overview_tab($canviewrequestworkspace);
        }
        $params['tab'] = tabs::validate_overview_tab($params['tab'], $canviewrequestworkspace);

        $validstatuses = [
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            event_access_manager::BOOKINGSTATUS_REJECTED,
        ];
        if (!in_array($params['status'], $validstatuses, true)) {
            throw new \invalid_parameter_exception('Invalid booking status: ' . $params['status']);
        }

        $event = $DB->get_record('bookit_event', ['id' => $params['eventid']], '*', MUST_EXIST);
        $oldstatus = (int)($event->bookingstatus ?? event_access_manager::BOOKINGSTATUS_NEW);
        $effectivestatus = event_manager::resolve_requested_booking_status($event, (int)$params['status']);

        if (
            $params['status'] === event_access_manager::BOOKINGSTATUS_NEW
            && $oldstatus === event_access_manager::BOOKINGSTATUS_REJECTED
        ) {
            if (!event_access_manager::can_reactivate_rejected_request($event, $context)) {
                throw new \required_capability_exception($context, 'mod/bookit:managebasics', 'nopermissions', '');
            }
        } else if (
            $params['status'] === event_access_manager::BOOKINGSTATUS_NEW
            && $oldstatus === event_access_manager::BOOKINGSTATUS_CANCELED
            && $params['tab'] === 'history'
            && event_access_manager::can_reactivate_canceled_request($event, $context)
        ) {
            $effectivestatus = event_access_manager::BOOKINGSTATUS_NEW;
        } else if (
            $params['status'] === event_access_manager::BOOKINGSTATUS_NEW
            && $oldstatus === event_access_manager::BOOKINGSTATUS_CANCELED
            && $params['tab'] === 'history'
            && event_access_manager::can_manage_open_requests($context)
        ) {
            $effectivestatus = event_access_manager::BOOKINGSTATUS_NEW;
        } else if (
            $params['status'] === event_access_manager::BOOKINGSTATUS_NEW
            && $oldstatus === event_access_manager::BOOKINGSTATUS_CANCELED
        ) {
            if (
                $params['tab'] === 'rejectedcancelled'
                && event_access_manager::can_restore_terminal_request($event, $context)
            ) {
                $effectivestatus = event_access_manager::BOOKINGSTATUS_NEW;
            } else if (
                $effectivestatus === null
                || !event_access_manager::can_restore_canceled_booking($event, $context, $effectivestatus)
            ) {
                throw new \required_capability_exception($context, 'mod/bookit:managebasics', 'nopermissions', '');
            }
        } else {
            $allowparticipantoverviewcancel = (int)$params['status'] === event_access_manager::BOOKINGSTATUS_CANCELED
                && $effectivestatus === event_access_manager::BOOKINGSTATUS_CANCELED
                && $params['tab'] === 'myevents'
                && event_access_manager::can_participant_overview_cancel($event, $context, (int)$USER->id);
            if (!$allowparticipantoverviewcancel) {
                require_capability('mod/bookit:managebasics', $context);
            }
        }

        if ($effectivestatus === null || !event_access_manager::can_transition_booking_status($oldstatus, $effectivestatus)) {
            throw new \invalid_parameter_exception('Invalid booking workflow transition.');
        }

        event_manager::transition_booking_status($event, $effectivestatus, (int)$USER->id, $context, (int)$params['cmid']);

        $redirecttab = match (true) {
            in_array($params['tab'], ['openrequests', 'confirmedrequests', 'rejectedcancelled'], true)
                && event_access_manager::can_manage_open_requests($context) => $params['tab'],
            $params['tab'] === 'allrequests'
                && event_access_manager::can_manage_open_requests($context) => 'allrequests',
            $params['tab'] === 'history' => 'history',
            $params['tab'] === 'myevents'
                && event_access_manager::can_manage_open_requests($context) => 'allrequests',
            default => 'myevents',
        };
        $redirectparams = [
            'id' => $cm->id,
            'tab' => $redirecttab,
        ];
        $page = max(0, (int)$params['page']);

        $queuepayload = null;
        $queueworkspace = $redirecttab;
        if (
            in_array($redirecttab, ['openrequests', 'confirmedrequests', 'rejectedcancelled'], true)
            && event_access_manager::can_manage_open_requests($context)
        ) {
            $queuepayload = event_manager::get_governed_overview_queue(
                $context,
                (int)$USER->id,
                $queueworkspace,
                ['workspace' => $queueworkspace],
                null,
                null,
                $page
            );
            $page = (int)$queuepayload['paging']['currentpage'];

            $paginghtml = '';
            if (!empty($queuepayload['paging']['haspaging'])) {
                $pagingbar = new \paging_bar(
                    (int)$queuepayload['paging']['totalcount'],
                    (int)$queuepayload['paging']['currentpage'],
                    (int)$queuepayload['paging']['perpage'],
                    new \moodle_url('/mod/bookit/overview.php', [
                        'id' => $cm->id,
                        'tab' => $redirecttab,
                    ])
                );
                $paginghtml = $OUTPUT->render($pagingbar);
            }
            unset($queuepayload['events']);
            $queuepayload['fragments'] = ['paginghtml' => $paginghtml];
        }
        if ($page > 0) {
            $redirectparams['page'] = $page;
        }
        $redirecturl = (new \moodle_url('/mod/bookit/overview.php', $redirectparams))->out(false);

        $response = [
            'status' => $effectivestatus,
            'tab' => $redirecttab,
            'redirecturl' => $redirecturl,
        ];
        if ($queuepayload !== null) {
            $response['queue'] = $queuepayload;
        }

        return $response;
    }

    /**
     * Description of return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, 'Updated booking status value'),
            'tab' => new external_value(PARAM_ALPHA, 'Overview tab to reopen after the workflow action'),
            'redirecturl' => new external_value(PARAM_URL, 'Redirect URL that preserves the active overview queue'),
            'queue' => new external_single_structure([
                'workspace' => new external_value(PARAM_ALPHA, 'Workspace name'),
                'filters' => new external_single_structure([
                    'start' => new external_value(PARAM_TEXT, 'Normalised start filter', VALUE_OPTIONAL),
                    'end' => new external_value(PARAM_TEXT, 'Normalised end filter', VALUE_OPTIONAL),
                    'roomids' => new \core_external\external_multiple_structure(new external_value(PARAM_INT)),
                    'facultyids' => new \core_external\external_multiple_structure(new external_value(PARAM_INT)),
                    'bookingstatuses' => new \core_external\external_multiple_structure(new external_value(PARAM_INT)),
                    'semesterids' => new \core_external\external_multiple_structure(new external_value(PARAM_INT)),
                    'search' => new external_value(PARAM_RAW_TRIMMED, 'Search filter'),
                    'workspace' => new external_value(PARAM_ALPHAEXT, 'Workspace filter'),
                    'assignmentfilter' => new external_value(PARAM_ALPHA, 'Assignment filter', VALUE_OPTIONAL),
                    'exportmode' => new external_value(PARAM_BOOL, 'Export mode'),
                ]),
                'items' => new \core_external\external_multiple_structure(new external_single_structure([
                    'eventid' => new external_value(PARAM_INT, 'Event id'),
                    'id' => new external_value(PARAM_INT, 'Compatibility event id'),
                    'name' => new external_value(PARAM_RAW, 'Event title'),
                    'bookingstatus' => new external_value(PARAM_INT, 'Booking status'),
                    'starttime' => new external_value(PARAM_INT, 'Start time'),
                    'endtime' => new external_value(PARAM_INT, 'End time'),
                    'room' => new external_value(PARAM_RAW, 'Room label'),
                    'personincharge' => new external_value(PARAM_RAW, 'Person in charge'),
                    'myrole' => new external_value(PARAM_RAW, 'Current user roles'),
                    'historyclassification' => new external_value(PARAM_ALPHAEXT, 'History classification'),
                    'datestr' => new external_value(PARAM_RAW, 'Display date'),
                    'actions' => new external_single_structure([
                        'caneventdetails' => new external_value(PARAM_BOOL, 'Whether details may be opened'),
                    ]),
                    'statusgroupkey' => new external_value(PARAM_ALPHAEXT, 'Grouped status key for row accent'),
                    'statuscellhtml' => new external_value(PARAM_RAW, 'Pre-rendered status cell HTML'),
                    'modalfootermode' => new external_value(PARAM_ALPHAEXT, 'Modal footer mode'),
                    'hasreactivateaction' => new external_value(PARAM_BOOL, 'Whether reactivation is allowed'),
                    'reactivateactionlabel' => new external_value(PARAM_RAW, 'Reactivation button label'),
                    'reactivatetargetstatus' => new external_value(PARAM_INT, 'Reactivation target status'),
                    'overviewtab' => new external_value(PARAM_ALPHA, 'Overview tab for reactivation action'),
                ])),
                'summary' => new external_single_structure([
                    'count' => new external_value(PARAM_INT, 'Number of items in the current workspace'),
                    'openrequestcount' => new external_value(PARAM_INT, 'Open request count'),
                    'rejectedrequestcount' => new external_value(PARAM_INT, 'Rejected request count'),
                ]),
                'paging' => new external_single_structure([
                    'requestedpage' => new external_value(PARAM_INT, 'Requested page number'),
                    'currentpage' => new external_value(PARAM_INT, 'Effective page number'),
                    'perpage' => new external_value(PARAM_INT, 'Items per page'),
                    'totalpages' => new external_value(PARAM_INT, 'Total number of pages'),
                    'totalcount' => new external_value(PARAM_INT, 'Total number of items in the workspace'),
                    'hasprevious' => new external_value(PARAM_BOOL, 'Whether a previous page exists'),
                    'hasnext' => new external_value(PARAM_BOOL, 'Whether a next page exists'),
                    'haspaging' => new external_value(PARAM_BOOL, 'Whether paging is needed'),
                    'adjusted' => new external_value(PARAM_BOOL, 'Whether the requested page had to be adjusted'),
                ]),
                'fragments' => new external_single_structure([
                    'paginghtml' => new external_value(PARAM_RAW, 'Rendered paging markup'),
                ]),
            ], 'Updated request workspace payload', VALUE_OPTIONAL),
        ]);
    }
}
