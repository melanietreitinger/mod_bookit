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
$roomid = required_param('roomid', PARAM_INT);
$room = \mod_bookit\local\persistent\room::get_record(['id' => $roomid], MUST_EXIST);

$params = ['roomid' => $roomid];
$weekplanroom = null;
if ($id) {
    $params['id'] = $id;
    $weekplanroom = \mod_bookit\local\persistent\weekplan_room::get_record(['id' => $id], MUST_EXIST);
    $title = get_string('edit_weekplan_assignment', 'mod_bookit');
} else {
    $title = get_string('new_weekplan_assignment', 'mod_bookit');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/edit_weekplan_room.php', $params));
$returnurl = new moodle_url('/mod/bookit/admin/view_room.php', ['id' => $roomid]);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);

$mform = new \mod_bookit\local\form\edit_weekplan_room_form($PAGE->url, [
    'persistent' => $weekplanroom,
    'room' => $room,
]);

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    if ($data->id) {
        $weekplanroom->from_record($data);
        $weekplanroom->update();
    } else {
        $weekplanroom = new \mod_bookit\local\persistent\weekplan_room(0, $data);
        $weekplanroom->create();
    }
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$mform->display();
echo $OUTPUT->footer();
