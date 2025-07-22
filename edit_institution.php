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

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');

// Override active url for admin tree / breadcrumbs.
navigation_node::override_active_url(new moodle_url('/mod/bookit/institutions.php'));
admin_externalpage_setup('mod_bookit_institutions');

$id = optional_param('id', null, PARAM_INT);

$params = [];
$institution = null;
if ($id) {
    $params['id'] = $id;
    $institution = \mod_bookit\local\persistent\institution::get_record(['id' => $id], MUST_EXIST);
}

$PAGE->set_url(new moodle_url('/mod/bookit/edit_institution.php', $params));
if ($id) {
    $title = get_string('edit_institution', 'mod_bookit');
} else {
    $title = get_string('new_institution', 'mod_bookit');
}

$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->navbar->add($title, new moodle_url($PAGE->url));

$returnurl = new moodle_url('/mod/bookit/institutions.php');

$mform = new \mod_bookit\local\form\edit_institution_form($PAGE->url, [
    'persistent' => $institution,
]);

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    if ($data->id) {
        $institution->from_record($data);
        $institution->update();
    } else {
        $institution = new \mod_bookit\local\persistent\institution(0, $data);
        $institution->create();
    }
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
