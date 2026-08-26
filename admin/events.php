<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin backend for event settings.
 *
 * @package    mod_bookit
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\form\settings_events_form;
use mod_bookit\local\tabs;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/admin/events.php'));
$PAGE->set_primary_active_tab('bookit_settings');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('events', 'mod_bookit'));
$PAGE->set_heading(get_string('settings_overview', 'mod_bookit'));

$returnurl = new moodle_url('/mod/bookit/admin/events.php');

require_capability('mod/bookit:managebasics', $context);

$mform = new settings_events_form();

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    unset($data->submitbutton);
    foreach ($data as $key => $value) {
        set_config($key, is_array($value) ? implode(',', $value) : $value, 'mod_bookit');
    }
}

$mform->set_data(get_config('mod_bookit'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('events', 'mod_bookit'));

$renderer = $PAGE->get_renderer('mod_bookit');
echo $renderer->tabs(tabs::get_tabrow($context), 'events');

$mform->display();

echo $OUTPUT->footer();
