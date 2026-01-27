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

use mod_bookit\local\table\rooms_table;
use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();

require_login();
require_capability('mod/bookit:managemasterchecklist', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/rooms.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('rooms', 'mod_bookit'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'mod_bookit'));

// Show tabs.
$renderer = $PAGE->get_renderer('mod_bookit');
$tabrow = tabs::get_tabrow($context);
$id = optional_param('id', 'settings', PARAM_TEXT);
echo $renderer->tabs($tabrow, $id);

echo $OUTPUT->render(new \core\output\single_button(
    new moodle_url('/mod/bookit/admin/edit_room.php'),
    get_string('new_room', 'mod_bookit'),
    'post',
    single_button::BUTTON_PRIMARY
)) . '<br><br>';

$table = new rooms_table();

$table->out(48, false);

echo $OUTPUT->footer();
