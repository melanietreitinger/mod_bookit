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
 * Configure notifications for mod_bookit.
 *
 * @package    mod_bookit
 * @copyright  2026 ssystems GmbH <oss@ssystems.de>
 * @author     Andreas Rosenthal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\form\settings_notifications_form;
use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/notifications.php'));
$PAGE->set_primary_active_tab('bookit_settings');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('bookingstatus_notifications_heading', 'mod_bookit'));
$PAGE->set_heading(get_string('settings_overview', 'mod_bookit'));

$returnurl = new moodle_url('/mod/bookit/admin/notifications.php');

require_capability('mod/bookit:managebasics', $context);

$mform = new settings_notifications_form();

// Standard form processing if statement.
if ($mform->is_cancelled()) {
    redirect($returnurl);
    // Fix for #211: The Code should not store the configuration in the database because of caching.
} else if ($data = $mform->get_data()) {
    unset($data->submitbutton);
    foreach ($data as $key => $value) {
        set_config(
                $key,
                is_array($value) ? implode(',', $value) : $value,
                'mod_bookit'
        );
    }
}

$config = get_config('mod_bookit');
$mform->set_data($config);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bookingstatus_notifications_heading', 'mod_bookit'));

// Show tabs.
$renderer = $PAGE->get_renderer('mod_bookit');
$tabrow = tabs::get_tabrow($context);
$id = optional_param('id', 'settings', PARAM_TEXT);
echo $renderer->tabs($tabrow, $id);

$mform->display();

echo $OUTPUT->footer();
