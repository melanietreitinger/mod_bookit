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
 * Define institutions for mod_bookit.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('mod_bookit_define_institutions');
$PAGE->set_url(new moodle_url('/mod/bookit/define_institutions.php'));
$PAGE->set_heading(get_string('institutions', 'mod_bookit'));

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

