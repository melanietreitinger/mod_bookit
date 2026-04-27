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
 * iCalendar (RFC 5545) exporter for BookIt events.
 *
 * @package     mod_bookit
 * @copyright   2025 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\output;

/**
 * iCalendar (RFC 5545) exporter for BookIt events.
 *
 * Builds the body of a .ics file for a list of event records. Use build() to
 * obtain the response payload and escape() when manually composing iCalendar
 * property values.
 *
 * Times are emitted as floating local time (no "Z" suffix and no TZID). The
 * BookIt calendar UI already presents events in the local timezone of the
 * Moodle instance, so the exported times match what the user sees in the
 * calendar without further conversion. Importing calendar applications will
 * interpret these as wall-clock times in the importer's local timezone, which
 * is the desired behaviour for room bookings.
 */
class ics_exporter {

    /**
     * Build an iCalendar string for a list of events.
     *
     * Each event becomes one VEVENT block. The DESCRIPTION property contains
     * the institutionid, technical needs and participants amount when those
     * fields are present, joined into a single multi-line property using
     * RFC 5545 line folding.
     *
     * @param array $events Event records; required fields are id, name,
     *                      starttime and endtime. Optional fields are
     *                      institutionid, technicalneeds, participantsamount
     *                      and room.
     * @param string $hostname Hostname used for the UID property; typically
     *                         the host part of $CFG->wwwroot.
     * @return string The full .ics body, ready to be sent to the client.
     */
    public static function build(array $events, string $hostname): string {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//BookIT//Exportg//EN',
        ];

        foreach ($events as $ev) {
            $uid   = $ev->id . '@' . $hostname;
            // Floating local time: no Z suffix, formatted in the server's local timezone
            // to match the times shown in the BookIt calendar UI.
            $start = date('Ymd\THis', (int)$ev->starttime);
            $end   = date('Ymd\THis', (int)($ev->endtime ?? ($ev->starttime + 3600)));

            $summary = self::escape($ev->name);
            $loc     = self::escape($ev->room ?? '');

            $descrrows = [];
            if (!empty($ev->institutionid)) {
                $descrrows[] = 'Faculty: ' . $ev->institutionid;
            }
            if (!empty($ev->technicalneeds)) {
                $descrrows[] = '| Requirements: ' . $ev->technicalneeds;
            }
            if (!empty($ev->participantsamount)) {
                $descrrows[] = '| Participants: ' . $ev->participantsamount;
            }

            $evlines = [
                'BEGIN:VEVENT',
                'UID:'      . $uid,
                'DTSTAMP:'  . gmdate('Ymd\THis\Z'),
                'DTSTART:'  . $start,
                'DTEND:'    . $end,
                'SUMMARY:'  . $summary,
                'LOCATION:' . $loc,
            ];

            if ($descrrows) {
                $evlines[] = 'DESCRIPTION:' . self::escape(array_shift($descrrows));
                foreach ($descrrows as $row) {
                    $evlines[] = ' ' . self::escape($row);
                }
            } else {
                $evlines[] = 'DESCRIPTION:';
            }

            $evlines[] = 'END:VEVENT';
            $lines = array_merge($lines, $evlines);
        }

        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines);
    }

    /**
     * Escape a string for inclusion in an iCalendar text property.
     *
     * Implements the escaping rules from RFC 5545, section 3.3.11:
     * backslash, comma and semicolon are escaped, carriage returns are
     * dropped and newlines become "\N".
     *
     * @param string $s Input string.
     * @return string Escaped string suitable for an .ics property value.
     */
    public static function escape(string $s): string {
        return str_replace(
            ['\\', ',', ';', "\r", "\n"],
            ['\\\\', '\,', '\;', '', '\N'],
            $s
        );
    }
}