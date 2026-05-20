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

namespace mod_bookit\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External API for the governed overview queue read contract.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_overview_queue extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'workspace' => new external_value(PARAM_ALPHA, 'Workspace name'),
            'bookingstatuses' => new external_multiple_structure(
                new external_value(PARAM_INT),
                'Booking status filter values',
                VALUE_DEFAULT,
                []
            ),
            'facultyids' => new external_multiple_structure(
                new external_value(PARAM_INT),
                'Faculty filter ids',
                VALUE_DEFAULT,
                []
            ),
            'semesterids' => new external_multiple_structure(
                new external_value(PARAM_INT),
                'Semester filter ids',
                VALUE_DEFAULT,
                []
            ),
            'reportstart' => new external_value(PARAM_TEXT, 'Reporting range start', VALUE_DEFAULT, ''),
            'reportend' => new external_value(PARAM_TEXT, 'Reporting range end', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the overview queue read.
     *
     * @param int $cmid
     * @param string $workspace
     * @param array $bookingstatuses
     * @param array $facultyids
     * @param array $semesterids
     * @param string $reportstart
     * @param string $reportend
     * @return array
     */
    public static function execute(
        int $cmid,
        string $workspace,
        array $bookingstatuses = [],
        array $facultyids = [],
        array $semesterids = [],
        string $reportstart = '',
        string $reportend = ''
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'workspace' => $workspace,
            'bookingstatuses' => $bookingstatuses,
            'facultyids' => $facultyids,
            'semesterids' => $semesterids,
            'reportstart' => $reportstart,
            'reportend' => $reportend,
        ]);

        $cm = get_coursemodule_from_id('bookit', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        if (!has_capability('mod/bookit:viewownoverview', $context) && !has_capability('mod/bookit:view', $context)) {
            return event_access_manager::build_governed_denied_response('overviewqueue', $params) + [
                'workspace' => $workspace,
                'items' => [],
                'summary' => ['count' => 0, 'openrequestcount' => 0, 'rejectedrequestcount' => 0],
            ];
        }

        $filters = event_access_manager::normalise_governed_read_filters([
            'workspace' => $params['workspace'],
            'bookingstatuses' => $params['bookingstatuses'],
            'facultyids' => $params['facultyids'],
            'semesterids' => $params['semesterids'],
            'start' => $params['reportstart'],
            'end' => $params['reportend'],
        ]);

        $payload = event_manager::get_governed_overview_queue(
            $context,
            (int)$USER->id,
            $params['workspace'],
            $filters,
            $filters['start'] ? (new \DateTime($filters['start']))->getTimestamp() : null,
            $filters['end'] ? (new \DateTime($filters['end']))->getTimestamp() : null
        );

        return event_access_manager::build_governed_empty_response(
            'overviewqueue',
            $filters,
            empty($payload['items']) ? 'no_matches' : 'none'
        ) + $payload;
    }

    /**
     * Describe the overview queue response.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'scope' => new external_value(PARAM_ALPHAEXT, 'Read scope'),
            'status' => new external_value(PARAM_ALPHA, 'Read status'),
            'denied' => new external_value(PARAM_BOOL, 'Whether access was denied'),
            'emptyreason' => new external_value(PARAM_ALPHAEXT, 'Reason for an empty payload'),
            'filters' => new external_single_structure([
                'start' => new external_value(PARAM_TEXT, 'Normalised start filter', VALUE_OPTIONAL),
                'end' => new external_value(PARAM_TEXT, 'Normalised end filter', VALUE_OPTIONAL),
                'roomids' => new external_multiple_structure(new external_value(PARAM_INT)),
                'facultyids' => new external_multiple_structure(new external_value(PARAM_INT)),
                'bookingstatuses' => new external_multiple_structure(new external_value(PARAM_INT)),
                'semesterids' => new external_multiple_structure(new external_value(PARAM_INT)),
                'search' => new external_value(PARAM_RAW_TRIMMED, 'Search filter'),
                'workspace' => new external_value(PARAM_ALPHAEXT, 'Workspace filter'),
                'exportmode' => new external_value(PARAM_BOOL, 'Export mode'),
            ]),
            'workspace' => new external_value(PARAM_ALPHA, 'Workspace name'),
            'items' => new external_multiple_structure(new external_single_structure([
                'eventid' => new external_value(PARAM_INT, 'Event id'),
                'id' => new external_value(PARAM_INT, 'Compatibility event id'),
                'name' => new external_value(PARAM_RAW, 'Event title'),
                'bookingstatus' => new external_value(PARAM_INT, 'Booking status'),
                'starttime' => new external_value(PARAM_INT, 'Event start timestamp'),
                'endtime' => new external_value(PARAM_INT, 'Event end timestamp'),
                'room' => new external_value(PARAM_RAW, 'Room label'),
                'historyclassification' => new external_value(PARAM_ALPHAEXT, 'History classification'),
                'datestr' => new external_value(PARAM_RAW, 'Display date'),
                'actions' => new external_single_structure([
                    'caneventdetails' => new external_value(PARAM_BOOL, 'Whether details may be opened'),
                    'canreactivate' => new external_value(PARAM_BOOL, 'Whether the event may be reactivated'),
                    'allowedstatuses' => new external_multiple_structure(new external_value(PARAM_INT)),
                ]),
                'statusstyle' => new external_value(PARAM_RAW, 'Inline status style'),
                'statustext' => new external_value(PARAM_RAW, 'Status label'),
                'latesthistorysummary' => new external_value(PARAM_RAW, 'Latest history summary'),
            ])),
            'summary' => new external_single_structure([
                'count' => new external_value(PARAM_INT, 'Number of items in the current workspace'),
                'openrequestcount' => new external_value(PARAM_INT, 'Open request count'),
                'rejectedrequestcount' => new external_value(PARAM_INT, 'Rejected request count'),
            ]),
        ]);
    }
}
