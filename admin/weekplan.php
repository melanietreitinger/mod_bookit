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
global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/adminlib.php');

// Override active url for admin tree / breadcrumbs.
navigation_node::override_active_url(new moodle_url('/mod/bookit/weekplans.php'));
admin_externalpage_setup('mod_bookit_weekplans');

$id = required_param('id', PARAM_INT);
$weekplan = $DB->get_record('bookit_weekplan', ['id' => $id], '*', MUST_EXIST);

$records = $DB->get_records('bookit_weekplanslot', ['weekplanid' => $id]);
$eventsbyday = \mod_bookit\local\manager\weekplan_manager::group_events_by_day($records);

$PAGE->set_url(new moodle_url('/mod/bookit/weekplan.php', ['id' => $id]));
$PAGE->set_heading($weekplan->name);
$PAGE->set_title($weekplan->name);

$PAGE->navbar->add($weekplan->name, new moodle_url($PAGE->url));

echo $OUTPUT->header();

echo $OUTPUT->render(new \core\output\single_button(
    new moodle_url('/mod/bookit/admin/edit_weekplan.php', ['id' => $id]),
    get_string('edit'),
    'post',
    single_button::BUTTON_PRIMARY
)) . '<br><br>';

foreach ($eventsbyday as $weekdayindex => $events) {
    echo "<h3>" . \mod_bookit\local\manager\weekplan_manager::WEEKDAYS[$weekdayindex] . "</h3>";
    echo "<ul>";
    foreach ($events as $event) {
        echo "<li>" . htmlentities($event) . "</li>";
    }
    echo "</ul>";
}

echo $OUTPUT->footer();
