<?php
/**
 * BookIT – calendar page with filters and “Export events”.
 *
 * @package     mod_bookit
 */

use mod_bookit\event\course_module_viewed;
use mod_bookit\local\manager\resource_manager;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

/* =====================================================================
   0.  Resolve module / course / context
   ===================================================================== */
$id = optional_param('id', 0, PARAM_INT);   // course-module id
$b  = optional_param('b',  0, PARAM_INT);   // activity instance id

if ($id) {
    $cm             = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('bookit', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('bookit', ['id' => $b], '*', MUST_EXIST);
    $course         = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('bookit', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

/* =====================================================================
   1.  Helper data for the filter <select>s
   ===================================================================== */
$statusmap = [
    0 => get_string('new',        'mod_bookit'),
    1 => get_string('inprogress', 'mod_bookit'),
    2 => get_string('accepted',   'mod_bookit'),
    3 => get_string('cancelled',  'mod_bookit'),
    4 => get_string('rejected',   'mod_bookit'),
];

$rooms = [];
foreach (resource_manager::get_resources()['Rooms']['resources'] ?? [] as $rid => $r) {
    $rooms[$rid] = $r['name'];
}

$faculties = $DB->get_fieldset_sql("
    SELECT DISTINCT department
      FROM {bookit_event}
     WHERE department <> ''
  ORDER BY department
");

/* =====================================================================
   2.  JS snippets – filters & export logic  
   ===================================================================== */
$tablefilterjs = "
    (function() {
        function send() {
            const p = {};
            const r = document.getElementById('filter-room').value;
            const f = document.getElementById('filter-faculty').value;
            const s = document.getElementById('filter-status').value;
            if (r) p.room    = r;
            if (f) p.faculty = f;
            if (s) p.status  = s;
            window.currentFilterParams = p;
            if (window.bookitCalendarUpdate) { window.bookitCalendarUpdate(p); }
        }
        ['filter-room','filter-faculty','filter-status'].forEach(id =>
            document.addEventListener('change', e => {
                if (e.target.id === id) { send(); }
            })
        );
    })();";

$exportjs = "
require(['jquery'], function($) {

    // open modal & load ALL events that match current filters
    $('#bookit-export').on('click', function () {

        const qs = { id: {$cm->id}, start:'1970-01-01T00:00', end:'2100-01-01T00:00' };
        if (window.currentFilterParams) { Object.assign(qs, window.currentFilterParams); }

        $('#bookit-export-list').html('<div class=\"text-center p-3\"><i class=\"fa fa-spinner fa-spin\"></i></div>');
        $('#bookit-export-modal').modal('show');

        $.getJSON(M.cfg.wwwroot + '/mod/bookit/events.php', qs, function(data){
            const list = $('#bookit-export-list').empty();
            if (!data.length) {
                list.append('<div class=\"text-muted\">".get_string('noevents', 'mod_bookit')."</div>');
                return;
            }
            data.forEach(function(e){
                const label = $('<label class=\"list-group-item d-flex gap-2 align-items-start\"></label>');
                label.append('<input class=\"form-check-input mt-1\" type=\"checkbox\" value=\"'+e.id+'\">')
                     .append('<span>'+e.title +
                             ' <small class=\"text-muted\">('+e.location+
                             ', '+e.start.substr(0,16).replace(\"T\",\" \")+')</small></span>');
                list.append(label);
            });
        });
    });

    // download .ics for checked ids
    $('#bookit-export-confirm').on('click', function () {
        const ids = $('#bookit-export-list input:checked').map(function(){return this.value;}).get();
        if (!ids.length) { alert('".get_string('chooseevent', 'mod_bookit')."'); return; }

        const qs = new URLSearchParams({id: {$cm->id}});
        if (window.currentFilterParams) { Object.entries(window.currentFilterParams).forEach(([k,v])=>qs.append(k,v)); }
        ids.forEach(id => qs.append('ids[]', id));

        window.location = M.cfg.wwwroot + '/mod/bookit/export_events.php?' + qs.toString();
        $('#bookit-export-modal').modal('hide');
    });
});";

$PAGE->requires->jquery();
$PAGE->requires->js_init_code($tablefilterjs);
$PAGE->requires->js_init_code($exportjs);

/* =====================================================================
   3.  Calendar feed URL & caps passed to AMD module
   ===================================================================== */
$eventsource   = (new moodle_url('/mod/bookit/events.php', ['id' => $cm->id]))->out(false);
$capabilities  = ['addevent' => has_capability('mod/bookit:addevent', $context)];
$configcalendar = [];
if ($tc = get_config('mod_bookit', 'textcolor')) { $configcalendar['textcolor'] = $tc; }

/* =====================================================================
   4.  Inject allowed weekdays for JS
   ===================================================================== */
$PAGE->requires->js_init_code('M.cfg.bookit_allowedweekdays = ['.implode(',', bookit_allowed_weekdays()).'];');

/* =====================================================================
   5.  Log the view event
   ===================================================================== */
$event = course_module_viewed::create(['objectid'=>$moduleinstance->id,'context'=>$context]);
$event->add_record_snapshot('course',$course);
$event->add_record_snapshot('bookit',$moduleinstance);
$event->trigger();

/* =====================================================================
   6.  Standard page setup
   ===================================================================== */
$PAGE->set_url('/mod/bookit/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$PAGE->requires->js (new moodle_url('/mod/bookit/assets/event-calendar.min.js'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/assets/event-calendar.min.css'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/assets/custom-calendar.min.css'), true);

echo $OUTPUT->header();

/* =====================================================================
   7.  Filter bar + Export button markup
   ===================================================================== */
echo html_writer::start_div('bookit-filters d-flex gap-2 mb-3');

/* room select */
echo html_writer::start_tag('select',['id'=>'filter-room','class'=>'form-select w-auto']);
echo html_writer::tag('option', get_string('allrooms', 'mod_bookit'), ['value'=>'']);
foreach ($rooms as $rid=>$rname) { echo html_writer::tag('option', format_string($rname), ['value'=>$rid]); }
echo html_writer::end_tag('select');

/* faculty select */
echo html_writer::start_tag('select',['id'=>'filter-faculty','class'=>'form-select w-auto']);
echo html_writer::tag('option', get_string('allfaculties', 'mod_bookit'), ['value'=>'']);
foreach ($faculties as $fac) { echo html_writer::tag('option', format_string($fac), ['value'=>$fac]); }
echo html_writer::end_tag('select');

/* status select */
echo html_writer::start_tag('select',['id'=>'filter-status','class'=>'form-select w-auto']);
echo html_writer::tag('option', get_string('allstatuses', 'mod_bookit'), ['value'=>'']);
foreach ($statusmap as $scode=>$label) { echo html_writer::tag('option', $label, ['value'=>$scode]); }
echo html_writer::end_tag('select');

echo html_writer::end_div(); 

/* export button (new) */
echo html_writer::tag('button', get_string('exportevents', 'mod_bookit'),
    ['id'=>'bookit-export','class'=>'btn btn-secondary mb-3']);

/* calendar container */
echo html_writer::div('', '', ['id'=>'ec']);

/* =====================================================================
   8.  Export-selection modal
   ===================================================================== */
echo '
<div class="modal fade" id="bookit-export-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">'.get_string('exportevents', 'mod_bookit').'</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>'.get_string('selectevents', 'mod_bookit').'</p>
        <div id="bookit-export-list" class="list-group small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
            .get_string('cancel').'</button>
        <button type="button" class="btn btn-primary" id="bookit-export-confirm">'
            .get_string('export', 'mod_bookit').'</button>
      </div>
    </div>
  </div>
</div>';

/* =====================================================================
   9.  Start AMD calendar
   ===================================================================== */
$PAGE->requires->js_call_amd('mod_bookit/calendar', 'init', [
    $cm->id,
    $eventsource,
    $capabilities,
    current_language(),
    $configcalendar,
]);

echo $OUTPUT->footer();
