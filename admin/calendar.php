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
 * Admin-Backend for calendar settings.
 *
 * @package    mod_bookit
 * @copyright  2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\form\settings_calendar_form;
use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/calendar.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('calendar', 'mod_bookit'));
$PAGE->set_heading(get_string('settings_overview', 'mod_bookit'));

$returnurl = new moodle_url('/mod/bookit/admin/calendar.php');

require_capability('mod/bookit:managebasics', $context);

$mform = new settings_calendar_form();

// Standard form processing if statement.
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    foreach ($data as $key => $value) {
        unset($data->submitbutton);
        // Create data object for each entry.
        // Fields: id, plugin, name, value.
        $c = new stdClass();
        $c->plugin = 'mod_bookit';
        $c->name = $key;
        $c->value = (is_array($value) ? implode(',', $value) : $value);
        $record = $DB->get_record('config_plugins', ['plugin' => 'mod_bookit', 'name' => $key], 'id');
        if ($record) {
            $c->id = $record->id;
            $DB->update_record('config_plugins', $c);
        } else {
            $DB->insert_record('config_plugins', $c);
        }
    }
}

$config = get_config('mod_bookit');
$mform->set_data($config);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('calendar', 'mod_bookit'));

// Show tabs.
$renderer = $PAGE->get_renderer('mod_bookit');
$tabrow = tabs::get_tabrow($context);
$id = optional_param('id', 'settings', PARAM_TEXT);
echo $renderer->tabs($tabrow, $id);

$mform->display();

echo $OUTPUT->footer();
