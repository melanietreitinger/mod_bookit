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

/**
 * External API for the governed calendar/export read contract.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_calendar_events extends external_api {
    /**
     * Describe input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'start' => new external_value(PARAM_TEXT, 'Range start datetime', VALUE_DEFAULT, ''),
            'end' => new external_value(PARAM_TEXT, 'Range end datetime', VALUE_DEFAULT, ''),
            'roomids' => new external_multiple_structure(new external_value(PARAM_INT), 'Room filter ids', VALUE_DEFAULT, []),
            'facultyids' => new external_multiple_structure(new external_value(PARAM_INT), 'Faculty filter ids', VALUE_DEFAULT, []),
            'bookingstatuses' => new external_multiple_structure(
                new external_value(PARAM_INT),
                'Booking status filter values',
                VALUE_DEFAULT,
                []
            ),
            'search' => new external_value(PARAM_RAW_TRIMMED, 'Free-text search', VALUE_DEFAULT, ''),
            'exportmode' => new external_value(PARAM_BOOL, 'Whether export preview consumes the read', VALUE_DEFAULT, false),
            'aggregate' => new external_value(PARAM_BOOL, 'Whether to return per-slot summary blocks', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the calendar read.
     *
     * @param int $cmid
     * @param string $start
     * @param string $end
     * @param array $roomids
     * @param array $facultyids
     * @param array $bookingstatuses
     * @param string $search
     * @param bool $exportmode
     * @return array
     */
    public static function execute(
        int $cmid,
        string $start = '',
        string $end = '',
        array $roomids = [],
        array $facultyids = [],
        array $bookingstatuses = [],
        string $search = '',
        bool $exportmode = false,
        bool $aggregate = false
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'start' => $start,
            'end' => $end,
            'roomids' => $roomids,
            'facultyids' => $facultyids,
            'bookingstatuses' => $bookingstatuses,
            'search' => $search,
            'exportmode' => $exportmode,
            'aggregate' => $aggregate,
        ]);

        $cm = get_coursemodule_from_id('bookit', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        if (!has_capability('mod/bookit:view', $context)) {
            return event_access_manager::build_governed_denied_response('calendar', $params) + ['events' => []];
        }

        $filters = event_access_manager::normalise_governed_read_filters($params);
        $filters['start'] = $filters['start'] ?? '1970-01-01 00:00';
        $filters['end'] = $filters['end'] ?? '2100-01-01 00:00';

        $events = event_manager::get_governed_calendar_events(
            $context,
            (int)$USER->id,
            $filters['start'],
            $filters['end'],
            $filters
               );

        if ($params['aggregate'] && !empty($events)) {
            $events = self::aggregate_events($events);
        }

        return event_access_manager::build_governed_empty_response(
            'calendar',
            $filters,
            empty($events) ? 'no_matches' : 'none'
        ) + ['events' => $events];
    }

      /**
     * Aggregate individual events into per-slot summary blocks.
     *
     * Groups events that share the same start and end into one block titled with
     * the number of exams. The underlying events are embedded as JSON so the client
     * can expand them inline without a second request. The count reflects the
     * already-filtered set.
     *
     * @param array $events Individual calendar events (read-mapper shape).
     * @return array Summary blocks (same event shape).
     */
    private static function aggregate_events(array $events): array {
        $groups = [];
        foreach ($events as $event) {
            $key = ($event['start'] ?? '') . '|' . ($event['end'] ?? '');
            $groups[$key][] = $event;
        }

        $summaries = [];
        $index = 0;
        foreach ($groups as $key => $groupevents) {
            $index++;
            [$gstart, $gend] = array_pad(explode('|', $key, 2), 2, '');
            $count = count($groupevents);
            $label = get_string('calendar_summary_count', 'mod_bookit', $count);
            $summaries[] = [
                'id' => -$index,
                'title' => $label,
                'start' => $gstart,
                'end' => $gend,
                'backgroundColor' => '#3B4A5A',
                'textColor' => '#ffffff',
                'classNames' => ['bookit-summary-event'],
                'extendedProps' => [
                    'titlehtml' => $label,
                    'bookingstatus' => -1,
                    'semesterid' => 0,
                    'visibilitymode' => 'summary',
                    'modalfootermode' => 'readonly',
                    'room' => ['roomid' => 0, 'roomname' => '', 'location' => '', 'shortname' => ''],
                    'issummary' => true,
                    'summarycount' => $count,
                    'childrenjson' => json_encode(array_values($groupevents)),
                ],
            ];
        }

        return $summaries;
    }

    /**
     * Describe the read result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'scope' => new external_value(PARAM_ALPHA, 'Read scope'),
            'status' => new external_value(PARAM_ALPHA, 'Read status'),
            'denied' => new external_value(PARAM_BOOL, 'Whether the read is denied'),
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
            'events' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Event id'),
                'title' => new external_value(PARAM_RAW, 'Event title'),
                'start' => new external_value(PARAM_TEXT, 'Event start'),
                'end' => new external_value(PARAM_TEXT, 'Event end'),
                'backgroundColor' => new external_value(PARAM_RAW, 'Background color'),
                'textColor' => new external_value(PARAM_RAW, 'Text color'),
                'classNames' => new external_multiple_structure(new external_value(PARAM_RAW)),
                'extendedProps' => new external_single_structure([
                    'titlehtml' => new external_value(PARAM_RAW, 'Rendered event title html'),
                    'bookingstatus' => new external_value(PARAM_INT, 'Booking status'),
                    'semesterid' => new external_value(PARAM_INT, 'Semester id'),
                    'visibilitymode' => new external_value(PARAM_ALPHAEXT, 'Visibility mode'),
                    'modalfootermode' => new external_value(PARAM_ALPHAEXT, 'Modal footer mode'),
                    'room' => new external_single_structure([
                        'roomid' => new external_value(PARAM_INT, 'Room id'),
                        'roomname' => new external_value(PARAM_RAW, 'Room name'),
                        'location' => new external_value(PARAM_RAW, 'Room location'),
                        'shortname' => new external_value(PARAM_RAW, 'Room shortname'),
                    ]),
                        'faculty' => new external_single_structure([
                        'facultyid' => new external_value(PARAM_INT, 'Faculty id'),
                        'label' => new external_value(PARAM_RAW, 'Faculty label'),
                    ], 'Faculty metadata', VALUE_OPTIONAL),
                    'issummary' => new external_value(PARAM_BOOL, 'Whether this is an aggregated summary block', VALUE_OPTIONAL),
                    'summarycount' => new external_value(PARAM_INT, 'Number of exams in the summary slot', VALUE_OPTIONAL),
                    'childrenjson' => new external_value(PARAM_RAW, 'JSON of underlying events for inline expansion', VALUE_OPTIONAL),
                ]),
            ])),
        ]);
    }
}
