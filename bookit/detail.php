<?php
require(__DIR__.'/../../config.php');


// Single wrapper for both read-only detail and editable form
$cmid    = required_param('id',    PARAM_INT);   // course-module id
$eventid = required_param('event', PARAM_INT);   // booking event id
$readonly = optional_param('readonly', 0, PARAM_BOOL); // force view-only

$cm      = get_coursemodule_from_id('bookit', $cmid, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

// Decide capability: edit if user can, else view

$canedit = has_capability('mod/bookit:editevent', $context);
$viewcap = 'mod/bookit:viewownoverview';
if ($canedit) {
    require_capability('mod/bookit:editevent', $context);
} else {
    require_capability($viewcap, $context);
}
if ($readonly) {
    $canedit = false;
}

// Page setup␊
$PAGE->set_url('/mod/bookit/detail.php', ['id'=>$cmid,'event'=>$eventid]);
$PAGE->set_title(get_string($canedit ? 'editevent':'overview','bookit'));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url('/mod/bookit/overview.php', ['id'=>$cmid]),
    get_string('backtooverview','bookit'),
    ['class'=>'btn btn-secondary mb-3']
);

// Render the form (dynamic_form handles readonly when editevent = 0)
require_once($CFG->dirroot . '/mod/bookit/classes/form/edit_event_form.php');
$form = new \mod_bookit\form\edit_event_form(
    null, null, 'POST', '', [], true,
    [
        'cmid'         => $cmid,
        'id'           => $eventid,
        'editevent'    => (int)$canedit,
        'editinternal' => (int)has_capability('mod/bookit:editinternal', $context),
    ]
);
$form->display();

echo $OUTPUT->footer();