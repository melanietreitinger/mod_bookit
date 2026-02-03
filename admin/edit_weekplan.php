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

use mod_bookit\local\tabs;
use mod_bookit\local\manager\weekplan_manager;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();

require_login();
require_capability('mod/bookit:managebasics', $context);

$id = optional_param('id', null, PARAM_INT);

$params = [];
$weekplan = null;
if ($id) {
    $params['id'] = $id;
    $weekplan = $DB->get_record('bookit_weekplan', ['id' => $id], '*', MUST_EXIST);
    $weekplan->weekplan = weekplan_manager::create_string_weekplan_from_db($id);
    $title = get_string('edit_weekplan', 'mod_bookit');
} else {
    $title = get_string('new_weekplan', 'mod_bookit');
}

$returnurl = new moodle_url('/mod/bookit/admin/weekplans.php');

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/edit_weekplan.php', $params));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading(get_string('settings_overview', 'mod_bookit'));

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
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
