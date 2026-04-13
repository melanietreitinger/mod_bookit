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
 * Admin page for managing resources and resource categories.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();

require_login();
require_capability('mod/bookit:managebasics', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/resources.php', ['id' => 'resources']));
$PAGE->set_primary_active_tab('bookit_settings');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('resources:overview', 'mod_bookit'));
$PAGE->set_heading(get_string('resources:overview', 'mod_bookit'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resources:overview', 'mod_bookit'));

// Show tabs.
$renderer = $PAGE->get_renderer('mod_bookit');
$tabrow = tabs::get_tabrow($context);
$id = optional_param('id', 'resources', PARAM_TEXT);
echo $renderer->tabs($tabrow, $id);

// Render via Output Class - renders full content with data-* attributes.
$catalog = new \mod_bookit\output\resource_catalog();
echo $renderer->render($catalog);

// Init Reactive JS - reads state from DOM data-* attributes.
$PAGE->requires->js_call_amd(
    'mod_bookit/resource_catalog/resource_catalog',
    'init',
    ['#mod-bookit-resource-catalog']
);

echo $OUTPUT->footer();
