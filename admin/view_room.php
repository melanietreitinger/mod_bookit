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

use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();

require_login();
require_capability('mod/bookit:managemasterchecklist', $context); // XXX TODO: use other capability.

$id     = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHANUMEXT);

$url = new moodle_url('/mod/bookit/admin/view_room.php', ['id' => $id]);

$room = \mod_bookit\local\persistent\room::get_record(['id' => $id], MUST_EXIST);

if ($action === 'delete') {
    $weekplanroomid = required_param('weekplanroomid', PARAM_INT);
    $record = \mod_bookit\local\persistent\weekplan_room::get_record(['id' => $weekplanroomid], MUST_EXIST);
    $record->delete();
    redirect($url);
}

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$title = $room->get('name');
$PAGE->set_title($title);

$PAGE->requires->js(new moodle_url('/mod/bookit/thirdpartylibs/event-calendar/event-calendar.min.js'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/thirdpartylibs/event-calendar/event-calendar.min.css'));
$PAGE->requires->css(new moodle_url('/mod/bookit/thirdpartylibs/event-calendar/custom-calendar.min.css'));
$eventsource = (new moodle_url('/mod/bookit/events_available.php', ['roomid' => $room->get('id')]))->out(false);
$PAGE->requires->js_call_amd(
    'mod_bookit/available_calendar',
    'init',
    [
        $eventsource,
        [],
        current_language(),
    ]
);

$table = new \mod_bookit\local\table\weekplan_room_table($id);

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);

// Show tabs.
$renderer = $PAGE->get_renderer('mod_bookit');
$tabrow = tabs::get_tabrow($context);
$id = optional_param('id', 'settings', PARAM_TEXT);
echo $renderer->tabs($tabrow, $id);

echo \core\output\html_writer::link(
    new moodle_url('/mod/bookit/admin/edit_room.php', ['id' => $id]),
    get_string('edit_room_data', 'mod_bookit'),
    ['class' => 'btn btn-primary mb-3']
);

echo $OUTPUT->heading(get_string('weekplan_assignments', 'mod_bookit'), 3, 'mt-4');

echo \core\output\html_writer::link(
    new moodle_url('/mod/bookit/admin/edit_weekplan_room.php', ['roomid' => $room->get('id')]),
    get_string('new_weekplan_assignment', 'mod_bookit'),
    ['class' => 'btn btn-primary mb-2']
);

$table->out(48, false);

echo $OUTPUT->heading(get_string('calendar', 'mod_bookit'), 3, 'mt-4');

echo '<div id="ec" class="mt-2 mb-2"></div>';

echo $OUTPUT->render_from_template('mod_bookit/admin_calendar_legend', []);

echo $OUTPUT->footer();
