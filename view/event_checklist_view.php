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
 * Event-specific master checklist view with check-off and progress bar.
 *
 * Service team (managebasics): can check off items and see progress.
 * Bookers and examiners: can check off their own items and see progress.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use mod_bookit\local\manager\checklist_manager;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\output\event_checklist_catalog;

$eventid = required_param('eventid', PARAM_INT);
$cmid    = required_param('id', PARAM_INT);

$cm     = get_coursemodule_from_id('bookit', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$event  = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/bookit:view', $context);
$isadmin = has_capability('mod/bookit:managebasics', $context)
    || has_capability('mod/bookit:viewalldetailsofevent', $context);
if (!$isadmin && !event_access_manager::is_booking_accessible($event)) {
    $backurl = new moodle_url('/mod/bookit/overview.php', ['id' => $cmid]);
    redirect(
        $backurl,
        get_string('overview_action_requires_confirmed_booking', 'mod_bookit'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

if (!event_access_manager::can_view_event_checklist($event, $context, (int)$USER->id)) {
    throw new required_capability_exception($context, 'mod/bookit:viewalldetailsofownevent', 'nopermissions', '');
}

$PAGE->set_url(new moodle_url('/mod/bookit/view/event_checklist_view.php', ['id' => $cmid, 'eventid' => $eventid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('event_checklist_title', 'mod_bookit'));

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'container-fluid py-3']);

$backurl = new moodle_url('/mod/bookit/overview.php', ['id' => $cmid]);
$resourcesurl = new moodle_url('/mod/bookit/view/event_resources.php', ['id' => $cmid, 'eventid' => $eventid]);
echo html_writer::start_tag('div', ['class' => 'mb-3 d-flex gap-3']);
echo html_writer::link($backurl, get_string('back_to_overview', 'mod_bookit'), ['class' => 'btn btn-secondary me-3']);
echo html_writer::link(
    $resourcesurl,
    get_string('event_checklist:go_to_resources', 'mod_bookit'),
    ['class' => 'btn btn-primary']
);
echo html_writer::end_tag('div');

echo $OUTPUT->heading(get_string('event_checklist_heading', 'mod_bookit', format_string($event->name)));

$canmarkallitems = has_capability('mod/bookit:managebasics', $context)
    || has_capability('mod/bookit:viewalldetailsofevent', $context);
$userbookitroleids = checklist_manager::get_user_bookit_role_ids((int)$USER->id);
$output = new event_checklist_catalog($eventid, $cmid, $context->id, $canmarkallitems, $userbookitroleids);
echo $OUTPUT->render($output);

echo html_writer::end_tag('div');

$PAGE->requires->js_call_amd(
    'mod_bookit/event_checklist/event_checklist_container',
    'init',
    ['[data-region="event-checklist-container"]']
);

echo $OUTPUT->footer();
