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

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();

require_login();
require_capability('mod/bookit:managemasterchecklist', $context); // XXX TODO: use other capability.

$id = optional_param('id', null, PARAM_INT);
$params = [];
$room = null;
if ($id) {
    $params['id'] = $id;
    $room = \mod_bookit\local\persistent\room::get_record(['id' => $id], MUST_EXIST);
    $title = get_string('edit_room', 'mod_bookit');
} else {
    $title = get_string('new_room', 'mod_bookit');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/edit_room.php', $params));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading(get_string('settings_overview', 'mod_bookit'));

$returnurl = new moodle_url('/mod/bookit/admin/view_room.php', ['id' => $id]);

$mform = new \mod_bookit\local\form\edit_room_form($PAGE->url, [
    'persistent' => $room,
]);

if ($mform->is_cancelled()) {
    if ($id) {
        redirect(new moodle_url('/mod/bookit/admin/view_room.php', ['id' => $id]));
    } else {
        redirect(new moodle_url('/mod/bookit/admin/rooms.php'));
    }
} else if ($data = $mform->get_data()) {
    if ($data->id) {
        $room->from_record($data);
        $room->update();
    } else {
        $room = new \mod_bookit\local\persistent\room(0, $data);
        $room->create();
        $id = $room->get('id');
    }
    redirect(new moodle_url('/mod/bookit/admin/view_room.php', ['id' => $id]));
} // Else display form.

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
