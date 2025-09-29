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


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
global $OUTPUT;

use mod_bookit\local\entity\bookit_checklist_master;

require_login();
$context = context_system::instance();
is_siteadmin() || require_capability('mod/bookit:managemasterchecklist', $context);

$PAGE->set_context($context);


$PAGE->set_url(new moodle_url('/mod/bookit/master_checklist.php'));
$PAGE->set_heading(get_string('master_checklist', 'mod_bookit'));
$PAGE->set_title(get_string('master_checklist', 'mod_bookit'));

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();


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

$output = $PAGE->get_renderer('mod_bookit');

echo $output->render($defaultchecklistmaster);


echo $OUTPUT->footer();
