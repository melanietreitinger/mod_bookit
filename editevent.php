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

use mod_bookit\local\entity\event;
use mod_bookit\local\manager\categories_manager;

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
$PAGE->set_context($modulecontext);
$PAGE->set_cm($cm, $course, $moduleinstance);
$title = get_string('edit_event', 'mod_bookit');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$form = new \mod_bookit\local\form\bookit_form($PAGE->url);

$redirecturl = new moodle_url('/mod/bookit/view.php', ['id' => $cm->id]);

if ($form->is_cancelled()) {
    redirect($redirecturl);
}

if ($data = $form->get_data()) {
    $mappings = [];
    echo json_encode(categories_manager::get_categories());
    echo json_encode($data);
    foreach (categories_manager::get_categories() as $category) {

        foreach ($category['resources'] as $resource) {
            $checkboxname = 'checkbox_' . $resource['id'];
            if ($data->$checkboxname ?? false) {
                $mappings[] = (object) [
                    'resourceid' => $resource['id'],
                    'amount' => $data->{'amount_' . $resource['id']},
                ];
            }
        }
    }
    echo json_encode($mappings);
    $data->resources = $mappings;
    $data->status = event::STATUS_OPEN;
    $event = event::from_record($data);
    $event->save();
    redirect($redirecturl);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
