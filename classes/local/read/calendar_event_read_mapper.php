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
 * Maps calendar event arrays to the canonical read-model shape.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_event_read_mapper {
    /**
     * Map a canonical calendar event array to the read model.
     *
     * Expected keys match the production calendar builder: id, title, start, end,
     * backgroundColor, textColor, classNames, and extendedProps (CamelCase) with
     * titlehtml, bookingstatus, semesterid, visibilitymode, modalfootermode, room, faculty.
     *
     * @param array $event
     * @param array $facultylabels Kept for caller compatibility; faculty comes from extendedProps.
     * @return array
     */
    public static function map(array $event, array $facultylabels = []): array {
        // Second argument retained for BC with the production calendar builder signature.
        unset($facultylabels);

        $extendedprops = (array)($event['extendedProps'] ?? []);
        $room = (array)($extendedprops['room'] ?? []);
        $faculty = (array)($extendedprops['faculty'] ?? []);
        $facultyid = (int)($faculty['facultyid'] ?? 0);
        $facultylabel = (string)($faculty['label'] ?? '');

        $visibilitymode = (string)($extendedprops['visibilitymode'] ?? '');
        if ($visibilitymode === '') {
            $reserved = !empty($extendedprops['reserved']) || !empty($extendedprops['is_reserved_projection']);
            $visibilitymode = $reserved ? 'reserved_projection' : 'full';
        }

        $mappedextendedprops = [
            'titlehtml' => (string)($extendedprops['titlehtml'] ?? ''),
            'bookingstatus' => (int)($extendedprops['bookingstatus'] ?? -1),
            'semesterid' => (int)($extendedprops['semesterid'] ?? 0),
            'visibilitymode' => $visibilitymode,
            'modalfootermode' => (string)($extendedprops['modalfootermode'] ?? ''),
            'room' => [
                'roomid' => (int)($room['roomid'] ?? 0),
                'roomname' => (string)($room['roomname'] ?? ''),
                'location' => (string)($room['location'] ?? ''),
                'shortname' => (string)($room['shortname'] ?? ''),
            ],
        ];

        if ($facultyid > 0 || $facultylabel !== '') {
            $mappedextendedprops['faculty'] = [
                'facultyid' => $facultyid,
                'label' => $facultylabel,
            ];
        }

        return [
            'id' => (int)($event['id'] ?? 0),
            'title' => (string)($event['title'] ?? ''),
            'start' => self::normalise_datetime((string)($event['start'] ?? '')),
            'end' => self::normalise_datetime((string)($event['end'] ?? '')),
            'backgroundColor' => (string)($event['backgroundColor'] ?? ''),
            'textColor' => (string)($event['textColor'] ?? ''),
            'classNames' => array_values($event['classNames'] ?? []),
            'extendedProps' => $mappedextendedprops,
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
