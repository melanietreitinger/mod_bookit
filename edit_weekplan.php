<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit an institution.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\manager\weekplan_manager;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');

// Override active url for admin tree / breadcrumbs.
navigation_node::override_active_url(new moodle_url('/mod/bookit/weekplans.php'));
admin_externalpage_setup('mod_bookit_weekplans');

$id = optional_param('id', null, PARAM_INT);

$params = [];
$weekplan = null;
if ($id) {
    $params['id'] = $id;
    $weekplan = $DB->get_record('bookit_weekplan', ['id' => $id], '*', MUST_EXIST);
    $weekplan->weekplan = weekplan_manager::create_string_weekplan_from_db($id);
}

$PAGE->set_url(new moodle_url('/mod/bookit/edit_weekplan.php', $params));

if ($id) {
    $title = get_string('edit_weekplan', 'mod_bookit');
    $PAGE->navbar->add($weekplan->name, new moodle_url('/mod/bookit/weekplan.php', ['id' => $id]));
} else {
    $title = get_string('new_weekplan', 'mod_bookit');
}

$PAGE->set_heading($title);
$PAGE->set_title($title);
$returnurl = new moodle_url('/mod/bookit/weekplans.php');
$PAGE->navbar->add($title, new moodle_url($PAGE->url));

$mform = new \mod_bookit\local\form\edit_weekplan_form($PAGE->url);
if ($id) {
    $mform->set_data($weekplan);
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    if ($id ?? false) {
        $data->id = $id;
        $DB->update_record('bookit_weekplan', $data);
    } else {
        $id = $DB->insert_record('bookit_weekplan', $data);
    }
    weekplan_manager::save_string_weekplan_to_db($data->weekplan, $id);
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
