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
 * List institutions for mod_bookit.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('bookit_institutions');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/institutions.php'));
$PAGE->set_heading(get_string('institutions', 'mod_bookit'));

$renderer = $PAGE->get_renderer('mod_bookit');
$tabrow = tabs::get_tabrow();
$id = optional_param('id', 'settings', PARAM_TEXT);
$renderer->tabs($tabrow, $id);

$table = new \mod_bookit\local\table\institutions_table();

echo $OUTPUT->header();

echo $OUTPUT->render(new \core\output\single_button(
    new moodle_url('/mod/bookit/edit_institution.php'),
    get_string('new_institution', 'mod_bookit'),
    'post',
    single_button::BUTTON_PRIMARY
)) . '<br><br>';

$table->out(48, false);

echo $OUTPUT->footer();
