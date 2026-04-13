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
 * Resource status view for event participants and service team.
 *
 * Service team (managebasics): interactive checklist with status dropdowns.
 * Bookers and examiners: read-only view matching the booking form layout.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

use mod_bookit\local\form\resource\view_event_resources_form;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\resource_manager;

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

if (!event_access_manager::can_view_event_resources($event, $context, (int)$USER->id)) {
    throw new required_capability_exception($context, 'mod/bookit:viewalldetailsofownevent', 'nopermissions', '');
}

$canmanage = has_capability('mod/bookit:managebasics', $context);

$PAGE->set_url(new moodle_url('/mod/bookit/view/event_resources.php', ['id' => $cmid, 'eventid' => $eventid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($course->fullname);

$titlestr = $canmanage
    ? get_string('event_resources_checklist_title', 'mod_bookit')
    : get_string('event_resources_title', 'mod_bookit');
$PAGE->set_title($titlestr);

echo $OUTPUT->header();

$backurl = new moodle_url('/mod/bookit/overview.php', ['id' => $cmid]);
$checklisturl = new moodle_url('/mod/bookit/view/event_checklist_view.php', ['id' => $cmid, 'eventid' => $eventid]);
echo html_writer::start_tag('div', ['class' => 'container-fluid py-3']);
echo html_writer::start_tag('div', ['class' => 'mb-3 d-flex gap-3']);
echo html_writer::link($backurl, get_string('back_to_overview', 'mod_bookit'), ['class' => 'btn btn-secondary me-3']);
echo html_writer::link($checklisturl, get_string('event_resources:go_to_checklist', 'mod_bookit'), ['class' => 'btn btn-primary']);
echo html_writer::end_tag('div');

if ($canmanage) {
    echo $OUTPUT->heading(get_string('event_resources_checklist_heading', 'mod_bookit', format_string($event->name)));

    $catalog = new \mod_bookit\output\event_resources_checklist_catalog($eventid, $cmid, $canmanage, $event);
    echo $OUTPUT->render($catalog);

    $PAGE->requires->js_call_amd(
        'mod_bookit/event_resources_checklist/event_resources_checklist_container',
        'init',
        ['#mod-bookit-event-resources-checklist-container']
    );
} else {
    // Bookers and examiners: read-only form matching the booking form layout.
    echo $OUTPUT->heading(get_string('event_resources_heading', 'mod_bookit', format_string($event->name)));

    $bookedresources = [];
    foreach (resource_manager::get_resources_of_event($eventid) as $rid => $br) {
        $bookedresources[$rid] = [
            'amount' => $br->get_amount(),
            'status' => $br->get_status()->value,
        ];
    }

    if (empty($bookedresources)) {
        echo $OUTPUT->notification(get_string('event_resources_checklist_no_resources', 'mod_bookit'), 'info');
    } else {
        $resourcesdata = resource_manager::get_active_resources_grouped();
        echo html_writer::start_tag('div', ['class' => 'mt-3']);
        $form = new view_event_resources_form(null, [
            'bookedresources' => $bookedresources,
            'resourcesdata'   => $resourcesdata,
        ]);
        $form->display();
        echo html_writer::end_tag('div');
    }
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
