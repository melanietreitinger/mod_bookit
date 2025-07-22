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
 * Admin-Backend concerning rooms for mod_bookit.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');

// Override active url for admin tree / breadcrumbs.
navigation_node::override_active_url(new moodle_url('/mod/bookit/rooms.php'));
admin_externalpage_setup('mod_bookit_rooms');

$id = required_param('id', PARAM_INT);
$room = \mod_bookit\local\persistent\room::get_record(['id' => $id], MUST_EXIST);

$url = new moodle_url('/mod/bookit/view_room.php', ['id' => $id]);

$action = optional_param('action', null, PARAM_ALPHANUMEXT);
if ($action === 'delete') {
    $weekplanroomid = required_param('weekplanroomid', PARAM_INT);
    $record = \mod_bookit\local\persistent\weekplan_room::get_record(['id' => $weekplanroomid], MUST_EXIST);
    $record->delete();
    redirect($url);
}

$PAGE->set_url($url);
$title = $room->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->navbar->add($title, new moodle_url($PAGE->url));

$PAGE->requires->js(new moodle_url('/mod/bookit/thirdpartylibs/event-calendar/event-calendar.min.js'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/thirdpartylibs/event-calendar/event-calendar.min.css'));
$PAGE->requires->css(new moodle_url('/mod/bookit/thirdpartylibs/event-calendar/custom-calendar.min.css'));
$eventsource = (new moodle_url('/mod/bookit/events_available.php', ['roomid' => $room->get('id')]))->out(false);
$PAGE->requires->js_call_amd('mod_bookit/available_calendar', 'init',
    [
        $eventsource,
        current_language(),
        [],
    ]);

$table = new \mod_bookit\local\table\weekplan_room_table($id);

echo $OUTPUT->header();

echo $OUTPUT->render(new \core\output\single_button(
    new moodle_url('/mod/bookit/edit_weekplan_room.php', ['roomid' => $room->get('id')]),
    get_string('new_weekplan_assignment', 'mod_bookit'),
    'post',
    single_button::BUTTON_PRIMARY
)) . '<br><br>';

$table->out(48, false);

echo '<div id="ec" class="mt-6 mb-2"></div>';

echo $OUTPUT->render_from_template('mod_bookit/admin_calendar_legend', []);

echo $OUTPUT->footer();

