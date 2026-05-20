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

/**
 * Maps legacy calendar event arrays to the governed read-model shape.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_event_read_mapper {
    /**
     * Map a legacy calendar event array to the governed read model while keeping compatibility keys.
     *
     * @param array $event
     * @param array $facultylabels
     * @return array
     */
    public static function map(array $event, array $facultylabels = []): array {
        $eventid = (int)($event['id'] ?? 0);
        $roomid = (int)($event['roomid'] ?? 0);
        $roomname = (string)($event['roomname'] ?? '');
        $facultyid = (int)($event['institutionid'] ?? 0);
        $faculty = (string)($facultylabels[$facultyid] ?? '');
        $extendedprops = (array)($event['extendedProps'] ?? []);
        $reserved = !empty($extendedprops['reserved']) || !empty($extendedprops['is_reserved_projection']);

        return [
            'eventid' => $eventid,
            'id' => $eventid,
            'title' => (string)($event['title'] ?? ''),
            'titlehtml' => (string)($event['titleHTML'] ?? ''),
            'titleHTML' => (string)($event['titleHTML'] ?? ''),
            'start' => self::normalise_datetime((string)($event['start'] ?? '')),
            'end' => self::normalise_datetime((string)($event['end'] ?? '')),
            'bookingstatus' => (int)($event['bookingstatus'] ?? -1),
            'semesterid' => (int)($event['semester'] ?? 0),
            'visibilitymode' => $reserved ? 'reserved_projection' : 'full',
            'room' => [
                'roomid' => $roomid,
                'roomname' => $roomname,
                'location' => (string)($extendedprops['location'] ?? ''),
                'shortname' => (string)($extendedprops['shortname'] ?? ''),
            ],
            'roomid' => $roomid,
            'roomname' => $roomname,
            'location' => (string)($extendedprops['location'] ?? ''),
            'shortname' => (string)($extendedprops['shortname'] ?? ''),
            'facultyid' => $facultyid,
            'faculty' => $faculty,
            'department' => $faculty,
            'style' => [
                'backgroundcolor' => (string)($event['backgroundColor'] ?? ''),
                'textcolor' => (string)($event['textColor'] ?? ''),
                'classnames' => array_values($event['classNames'] ?? []),
            ],
            'backgroundColor' => (string)($event['backgroundColor'] ?? ''),
            'textColor' => (string)($event['textColor'] ?? ''),
            'classNames' => array_values($event['classNames'] ?? []),
            'extendedprops' => $extendedprops,
            'extendedProps' => $extendedprops,
        ];
    }

    /**
     * Normalise the datetime format consumed by JS clients.
     *
     * @param string $value
     * @return string
     */
    private static function normalise_datetime(string $value): string {
        if ($value === '') {
            return '';
        }

        return str_contains($value, 'T') ? $value : ($value . ':00');
    }
}
