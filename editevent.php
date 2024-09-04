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
 * Database class for bookit_events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

global $DB, $OUTPUT;

// Activity instance id.
$b = required_param('b', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

$moduleinstance = $DB->get_record('bookit', ['id' => $b], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('bookit', $moduleinstance->id, $course->id, false, MUST_EXIST);

$modulecontext = context_module::instance($cm->id);

$params = ['b' => $b];
if ($id) {
    $params['id'] = $id;
}
$PAGE->set_url('/mod/bookit/editevent.php', $params);
$title = get_string('edit_event', 'mod_bookit');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_context($modulecontext);
$PAGE->set_cm($cm, $course, $moduleinstance);

$form = new \mod_bookit\local\form\bookit_form();

echo $OUTPUT->header();

$form->display();


echo $OUTPUT->footer();
