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
 * External API for the governed room-availability read contract.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_room_availability extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'roomid' => new external_value(PARAM_INT, 'Room id'),
            'start' => new external_value(PARAM_TEXT, 'Range start datetime'),
            'end' => new external_value(PARAM_TEXT, 'Range end datetime'),
        ]);
    }

    /**
     * Execute the room-availability read.
     *
     * @param int $roomid
     * @param string $start
     * @param string $end
     * @return array
     */
    public static function execute(int $roomid, string $start, string $end): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'roomid' => $roomid,
            'start' => $start,
            'end' => $end,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);

        if (!has_capability('mod/bookit:managebasics', $context)) {
            return event_access_manager::build_governed_denied_response('roomavailability', $params) + ['entries' => []];
        }

        $filters = event_access_manager::normalise_governed_read_filters($params);
        if ($filters['start'] === null || $filters['end'] === null) {
            throw new \invalid_parameter_exception('Room availability reads require start and end.');
        }

        $starttimestamp = (new \DateTime($filters['start']))->getTimestamp();
        $endtimestamp = (new \DateTime($filters['end']))->getTimestamp();
        $entries = event_manager::get_governed_room_availability($starttimestamp, $endtimestamp, (int)$params['roomid']);

        return event_access_manager::build_governed_empty_response(
            'roomavailability',
            $filters,
            empty($entries) ? 'no_matches' : 'none'
        ) + [
            'entries' => $entries,
            'resourceid' => (int)$params['roomid'],
        ];
    }

    /**
     * Describe the room-availability response.
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
            'resourceid' => new external_value(PARAM_INT, 'Room id'),
            'entries' => new external_multiple_structure(new external_single_structure([
                'type' => new external_value(PARAM_ALPHA, 'Entry type'),
                'id' => new external_value(PARAM_RAW, 'Entry id'),
                'title' => new external_value(PARAM_RAW, 'Entry title'),
                'start' => new external_value(PARAM_TEXT, 'Entry start'),
                'end' => new external_value(PARAM_TEXT, 'Entry end'),
                'color' => new external_value(PARAM_RAW, 'Entry color'),
                'resourceid' => new external_value(PARAM_INT, 'Room id'),
                'meta' => new external_single_structure([
                    'type' => new external_value(PARAM_ALPHA, 'Entry type'),
                    'roomid' => new external_value(PARAM_INT, 'Room id'),
                ], 'Meta data', VALUE_OPTIONAL),
                'backgroundColor' => new external_value(PARAM_RAW, 'Compatibility background color'),
                'extendedProps' => new external_single_structure([
                    'type' => new external_value(PARAM_ALPHA, 'Entry type'),
                    'roomid' => new external_value(PARAM_INT, 'Room id'),
                ]),
            ])),
        ]);
    }
}
