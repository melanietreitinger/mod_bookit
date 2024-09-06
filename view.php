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
 * Prints an instance of mod_bookit.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\event\course_module_viewed;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$b = optional_param('b', 0, PARAM_INT);

if ($id) {
  $cm = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
  $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
  $moduleinstance = $DB->get_record('bookit', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
  $moduleinstance = $DB->get_record('bookit', ['id' => $b], '*', MUST_EXIST);
  $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
  $cm = get_coursemodule_from_instance('bookit', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = course_module_viewed::create([
  'objectid' => $moduleinstance->id,
  'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('bookit', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/bookit/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->requires->js(new moodle_url('/mod/bookit/assets/event-calendar.min.js'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/assets/event-calendar.min.css'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/assets/custom-calendar.min.css'), true);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

$entryform = (new moodle_url('/mod/bookit/editevent.php', ['b' => $moduleinstance->id]))->out(false);
$eventsource =  (new moodle_url('/mod/bookit/events.php', ['id' => $cm->id]))->out(false);

echo '<div id="ec"></div>';
$PAGE->requires->js_call_amd('mod_bookit/calendar', 'init', [$entryform, $eventsource]);

echo $OUTPUT->footer();
