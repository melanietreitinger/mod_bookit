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
 * Admin-Backend concerning master checklists for mod_bookit.
 *
 * @package    mod_bookit
 * @copyright  2025 ssystems GmbH <oss@ssystems.de>
 * @author     Andreas Rosenthal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\manager\checklist_manager;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_master;
use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();

require_login();
require_capability('mod/bookit:managemasterchecklist', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/master_checklist.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('master_checklist', 'mod_bookit'));
$PAGE->set_heading(get_string('settings_overview', 'mod_bookit'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('master_checklist', 'mod_bookit'));

// Show tabs.
$renderer = $PAGE->get_renderer('mod_bookit');
$tabrow = tabs::get_tabrow($context);
$id = optional_param('id', 'settings', PARAM_TEXT);
echo $renderer->tabs($tabrow, $id);

echo html_writer::div(get_string('settings_master_checklist_desc', 'mod_bookit'));

$defaultchecklistmaster = checklist_manager::get_default_master();

if (!$defaultchecklistmaster) {
    $defaultchecklistmaster = new bookit_checklist_master(
        null,
        'Master Checklist',
        'Default Master Checklist',
        1
    );

    $defaultchecklistmaster->save();
}

echo $renderer->render($defaultchecklistmaster);

echo $OUTPUT->footer();
