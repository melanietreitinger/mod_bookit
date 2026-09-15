<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin backend for booking-form custom fields (#97).
 *
 * @package    mod_bookit
 * @copyright   2026 Vadym Kuzyak, Humboldt-Universität zu Berlin
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/customfield.php'));
$PAGE->set_primary_active_tab('bookit_settings');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('customfields', 'mod_bookit'));
$PAGE->set_heading(get_string('settings_overview', 'mod_bookit'));

require_capability('mod/bookit:managebasics', $context);

$handler = \mod_bookit\customfield\event_handler::create();
$output = $PAGE->get_renderer('core_customfield');
$managepage = new \core_customfield\output\management($handler);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('customfields', 'mod_bookit'));
$renderer = $PAGE->get_renderer('mod_bookit');
echo $renderer->tabs(tabs::get_tabrow($context), 'customfields');
echo $output->render($managepage);
echo $OUTPUT->footer();
