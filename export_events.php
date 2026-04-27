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
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2025 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require('../../config.php');
require_once('lib.php');

use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\entity\resource\bookit_event_resource;
use mod_bookit\local\manager\event_manager;
use mod_bookit\output\ics_exporter;

/* ------------------------------------------------------------------
   0.  Parameters & capability check
   ------------------------------------------------------------------ */
$cmid    = required_param('id', PARAM_INT);          /* course-module id */
$ids     = optional_param_array('ids', [], PARAM_INT); /* ids[]=1 & ids[]=2…  No codechecker this is not code!!!!*/
$room    = optional_param('room', 0, PARAM_INT);
$faculty = optional_param('faculty', '', PARAM_TEXT);
$status  = optional_param('status', -1, PARAM_INT);

$cm      = get_coursemodule_from_id('bookit', $cmid, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/bookit:viewownoverview', $context);

/* ------------------------------------------------------------------
   1.  Fetch events (either explicit ids[] or time-range)
   ------------------------------------------------------------------ */
global $USER;

// Resolve time range when no explicit ids are given.
$startts = null;
$endts   = null;
if (empty($ids)) {
    $start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
    $end   = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);

    $startts = (new DateTime(str_replace('T', ' ', $start)))->getTimestamp();
    $endts   = (new DateTime(str_replace('T', ' ', $end)))->getTimestamp();
}

$events = bookit_event::get_for_export($context, (int)$USER->id, $ids, $startts, $endts);

/* additional UI filters ------------------------------------------------ */
$events = event_manager::apply_export_filters($events, $room, $faculty, $status);

if (!$events) {
    throw new moodle_exception('noevents', 'mod_bookit');
}

/* add the event room */
$events = bookit_event_resource::annotate_events_with_room($events);

/* resolve institution names */
$events = bookit_event::resolve_institution_names($events);
/* ------------------------------------------------------------------
   2.  Build VCALENDAR
   ------------------------------------------------------------------ */
$ics = ics_exporter::build($events, parse_url($CFG->wwwroot, PHP_URL_HOST));

/* ------------------------------------------------------------------
3.  Output
------------------------------------------------------------------ */
$filename = clean_filename('bookit-events-' . date('Ymd-His') . '.ics');
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $ics;
exit;