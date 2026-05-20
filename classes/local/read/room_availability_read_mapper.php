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
 * Maps room availability entries to the governed read-model shape.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class room_availability_read_mapper {
    /**
     * Map a legacy room-availability entry to the governed contract format.
     *
     * @param array $entry
     * @param int $roomid
     * @return array
     */
    public static function map(array $entry, int $roomid): array {
        $meta = (array)($entry['extendedProps'] ?? []);
        $type = (string)($meta['type'] ?? 'reservation');
        $color = (string)($entry['backgroundColor'] ?? '');

        return [
            'type' => $type,
            'id' => (string)($entry['id'] ?? ''),
            'title' => (string)($entry['title'] ?? ''),
            'start' => self::normalise_datetime((string)($entry['start'] ?? '')),
            'end' => self::normalise_datetime((string)($entry['end'] ?? '')),
            'color' => $color,
            'resourceid' => $roomid,
            'meta' => $meta + ['roomid' => $roomid],
            'backgroundColor' => $color,
            'extendedProps' => $meta + ['roomid' => $roomid],
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
