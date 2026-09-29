<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Public ICS feed for confirmed BookIt events.
 *
 * @package     mod_bookit
 * @copyright   2026 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require('../../config.php');

use mod_bookit\local\manager\event_access_manager;
use mod_bookit\output\ics_exporter;

global $CFG, $DB;

if ((int)get_config('mod_bookit', 'public_exam_calendar') !== 1) {
    throw new moodle_exception(
        'publiccalendar_disabled',
        'mod_bookit'
    );
}

$events = $DB->get_records_sql(
    'SELECT e.id, e.starttime, e.endtime, e.roomid,
            r.name AS roomname,
            r.shortname AS roomshortname,
            r.location AS roomlocation
       FROM {bookit_event} e
  LEFT JOIN {bookit_room} r ON r.id = e.roomid
      WHERE e.bookingstatus = :confirmed
   ORDER BY e.starttime, e.id',
    [
        'confirmed' =>
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
    ]
);

foreach ($events as $event) {
    $event->room = implode(', ', array_filter([
        $event->roomname ?? '',
        $event->roomshortname ?? '',
        $event->roomlocation ?? '',
    ]));
}

$ics = ics_exporter::build_public(
    $events,
    (string)parse_url($CFG->wwwroot, PHP_URL_HOST)
);

header('Content-Type: text/calendar; charset=utf-8');
header(
    'Content-Disposition: inline; filename="bookit-public-calendar.ics"'
);

echo $ics;
exit;
