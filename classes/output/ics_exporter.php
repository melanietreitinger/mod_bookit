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
 *
 * DESCRIPTION includes booking person (usercreated), person in charge, other
 * examiners, semester, duration, faculty name, technical needs and participants
 * when those fields are present.
 */
class ics_exporter {
    /**
     * Build an iCalendar string for a list of events.
     *
     * @param array $events Event records; required fields are id, name,
     *                      starttime and endtime. Optional fields include
     *                      room, usercreated, personinchargeid, otherexaminers,
     *                      semester, institutionid, technicalneeds and
     *                      participantsamount.
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

        $usernames = self::resolve_user_names(self::collect_user_ids($events));
        $institutionnames = self::resolve_institution_names(self::collect_institution_ids($events));

        foreach ($events as $ev) {
            $uid = $ev->id . '@' . $hostname;
            // Floating local time: no Z suffix, formatted in the server's local timezone
            // to match the times shown in the BookIt calendar UI.
            $start = date('Ymd\THis', (int)$ev->starttime);
            $end = date('Ymd\THis', (int)($ev->endtime ?? ($ev->starttime + 3600)));

            $summary = self::escape((string)$ev->name);
            $loc = self::escape((string)($ev->room ?? ''));

            $descrrows = self::build_description_rows($ev, $usernames, $institutionnames);

            $evlines = [
                'BEGIN:VEVENT',
                'UID:' . $uid,
                'DTSTAMP:' . gmdate('Ymd\THis\Z'),
                'DTSTART:' . $start,
                'DTEND:' . $end,
                'SUMMARY:' . $summary,
                'LOCATION:' . $loc,
            ];

            if ($descrrows) {
                // One logical DESCRIPTION line: escape each row, then join with the
                // iCalendar text line break (\n) so clients show each row separately.
                $escaped = array_map([self::class, 'escape'], $descrrows);
                $evlines[] = 'DESCRIPTION:' . implode('\n', $escaped);
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

    /**
     * Build localized DESCRIPTION rows for one event.
     *
     * @param object $ev Event record with optional usercreated, personinchargeid, and related fields.
     * @param array $usernames Map of user id to display name.
     * @param array $institutionnames Map of institution id to display name.
     * @return array Localized description rows for the ICS DESCRIPTION property.
     */
    private static function build_description_rows(object $ev, array $usernames, array $institutionnames): array {
        $descrrows = [];
        $bookingpersonid = (int)($ev->usercreated ?? 0);
        $personinchargeid = (int)($ev->personinchargeid ?? 0);
        $institutionid = (int)($ev->institutionid ?? 0);
        $otherexaminerids = self::csv_user_ids($ev->otherexaminers ?? null);
        $otherexaminernames = array_values(array_filter(array_map(
            static fn(int $userid): ?string => $usernames[$userid] ?? null,
            $otherexaminerids
        )));

        if ($bookingpersonid && isset($usernames[$bookingpersonid])) {
            $descrrows[] = get_string('exportevents_ics_bookingperson', 'mod_bookit')
                . ': ' . $usernames[$bookingpersonid];
        }
        if ($personinchargeid && isset($usernames[$personinchargeid])) {
            $descrrows[] = get_string('exportevents_ics_personincharge', 'mod_bookit')
                . ': ' . $usernames[$personinchargeid];
        }
        if (!empty($otherexaminernames)) {
            $descrrows[] = get_string('exportevents_ics_otherexaminers', 'mod_bookit')
                . ': ' . implode(', ', $otherexaminernames);
        }
        if (!empty($ev->semester)) {
            $descrrows[] = get_string('exportevents_ics_semester', 'mod_bookit')
                . ': ' . self::semester_label((int)$ev->semester);
        }
        $descrrows[] = get_string('exportevents_ics_duration', 'mod_bookit')
            . ': ' . self::duration_label((int)$ev->starttime, (int)($ev->endtime ?? $ev->starttime));
        if ($institutionid && isset($institutionnames[$institutionid])) {
            $descrrows[] = get_string('exportevents_ics_faculty', 'mod_bookit')
                . ': ' . $institutionnames[$institutionid];
        } else if (!empty($ev->institutionid) && !is_numeric((string)$ev->institutionid)) {
            // Already resolved institution name (legacy callers).
            $descrrows[] = get_string('exportevents_ics_faculty', 'mod_bookit')
                . ': ' . $ev->institutionid;
        }
        if (!empty($ev->technicalneeds)) {
            $descrrows[] = get_string('exportevents_ics_requirements', 'mod_bookit')
                . ': ' . $ev->technicalneeds;
        }
        if (!empty($ev->participantsamount)) {
            $descrrows[] = get_string('exportevents_ics_participants', 'mod_bookit')
                . ': ' . $ev->participantsamount;
        }

        return $descrrows;
    }

    /**
     * Collect related user IDs from exported events.
     *
     * @param array $events
     * @return int[]
     */
    private static function collect_user_ids(array $events): array {
        $userids = [];
        foreach ($events as $ev) {
            $userids[] = (int)($ev->usercreated ?? 0);
            $userids[] = (int)($ev->personinchargeid ?? 0);
            foreach (self::csv_user_ids($ev->otherexaminers ?? null) as $userid) {
                $userids[] = $userid;
            }
        }
        return $userids;
    }

    /**
     * Collect institution IDs from exported events.
     *
     * @param array $events
     * @return int[]
     */
    private static function collect_institution_ids(array $events): array {
        $ids = [];
        foreach ($events as $ev) {
            $iid = (int)($ev->institutionid ?? 0);
            if ($iid > 0) {
                $ids[] = $iid;
            }
        }
        return $ids;
    }

    /**
     * Parse a CSV list of user IDs into integers.
     *
     * @param string|null $csv
     * @return int[]
     */
    private static function csv_user_ids(?string $csv): array {
        if ($csv === null || $csv === '') {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', $csv))));
    }

    /**
     * Resolve display names for the given user IDs.
     *
     * @param int[] $userids
     * @return array<int,string>
     */
    private static function resolve_user_names(array $userids): array {
        global $DB;

        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        if (empty($userids)) {
            return [];
        }

        $fields = 'id, firstname, lastname, middlename, alternatename, firstnamephonetic, lastnamephonetic';
        $records = $DB->get_records_list('user', 'id', $userids, '', $fields);
        $names = [];
        foreach ($records as $record) {
            $names[(int)$record->id] = fullname($record);
        }
        return $names;
    }

    /**
     * Resolve institution names for the given IDs.
     *
     * @param int[] $institutionids
     * @return array<int,string>
     */
    private static function resolve_institution_names(array $institutionids): array {
        global $DB;

        $institutionids = array_values(array_unique(array_filter(array_map('intval', $institutionids))));
        if (empty($institutionids)) {
            return [];
        }

        $records = $DB->get_records_list('bookit_institution', 'id', $institutionids, '', 'id, name');
        $names = [];
        foreach ($records as $record) {
            $names[(int)$record->id] = $record->name;
        }
        return $names;
    }

    /**
     * Build a localized semester label from the encoded semester value.
     *
     * @param int|null $semester
     * @return string
     */
    private static function semester_label(?int $semester): string {
        if (empty($semester)) {
            return '';
        }
        $year = (int)floor($semester / 10);
        $term = ((int)$semester % 10) === 1 ? 'event_semester_summer' : 'event_semester_winter';
        return get_string($term, 'mod_bookit') . ' ' . $year;
    }

    /**
     * Build a short duration label between start and end timestamps.
     *
     * @param int $starttime
     * @param int $endtime
     * @return string
     */
    private static function duration_label(int $starttime, int $endtime): string {
        $seconds = max(0, $endtime - $starttime);
        $hours = intdiv($seconds, HOURSECS);
        $minutes = intdiv($seconds % HOURSECS, MINSECS);
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes . 'm';
        }
        return implode(' ', $parts);
    }
}
