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
 * Prints an instance of mod_bookit.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\event\course_module_viewed;
use mod_bookit\local\manager\resource_manager;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$b = optional_param('b', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('bookit', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('bookit', ['id' => $b], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bookit', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

// Helper data for the filter <select>s  (WORK IN PROGRESS)
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

// Log view event of calendar.
$event = course_module_viewed::create([
        'objectid' => $moduleinstance->id,
        'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('bookit', $moduleinstance);

// JavaScript – filter communication + Export‑modal logic (WORK IN PROGRESS)
$PAGE->requires->jquery();

/* -------- send filter changes to the AMD calendar -------------------- */
$PAGE->requires->js_init_code("
    (function() {
        function pushFilters() {
            const p = {};
            const r = $('#filter-room').val();
            const f = $('#filter-faculty').val();
            const s = $('#filter-status').val();
            if (r) p.room    = r;
            if (f) p.faculty = f;
            if (s) p.status  = s;
            window.currentFilterParams = p;
            if (window.bookitCalendarUpdate) { window.bookitCalendarUpdate(p); }
        }
        $('#filter-room, #filter-faculty, #filter-status').on('change', pushFilters);
    })();
");

/* -------- Export modal ------------------------------------------------ */
$PAGE->requires->js_init_code("
require(['jquery'], function($) {

    /* open modal & load events --------------------------------------- */
    $('#bookit-export').on('click', function () {

        // build query string with current filters (but ALL dates)
        const qs = { id: {$cm->id}, start:'1970-01-01T00:00', end:'2100-01-01T00:00' };
        if (window.currentFilterParams) { Object.assign(qs, window.currentFilterParams); }

        // show spinner while loading
        const list = $('#bookit-export-list');
        list.html('<div class=\"text-center p-3\"><i class=\"fa fa-spinner fa-spin\"></i></div>');
        $('#bookit-export-modal').modal('show');

        // load events JSON
        $.getJSON(M.cfg.wwwroot + '/mod/bookit/events.php', qs, function(data){
            list.empty();
            if (!data.length) {
                list.append('<div class=\"text-muted\">".get_string('noevents', 'mod_bookit')."</div>');
                return;
            }

           data.forEach(function (e) {
            var roomTxt   = (e.location || e.room || \"\").trim();
            var faculty   = (e.department || \"\").trim();
            var statusMap = {0:\"New\",1:\"In progress\",2:\"Accepted\",3:\"Cancelled\",4:\"Rejected\"};
            var statusTxt = statusMap[e.bookingstatus] || \"\";

            var dateTxt   = e.start.substr(0,16).replace(\"T\",\" \");
            var metaLine  = roomTxt ? roomTxt + \" \" + dateTxt : dateTxt;

            var row = $(\"<label class=\\\"list-group-item d-flex gap-2 align-items-start\\\" \" +
                    \"data-room=\\\"\"+roomTxt.toLowerCase()+\"\\\" \" +
                    \"data-faculty=\\\"\"+faculty.toLowerCase()+\"\\\" \" +
                    \"data-status=\\\"\"+statusTxt.toLowerCase()+\"\\\">\" +
                        \"<input class=\\\"form-check-input mt-1\\\" type=\\\"checkbox\\\" value=\\\"\"+e.id+\"\\\">\" +
                        \"<span>\"+e.title+\" <small class=\\\"text-muted\\\">(\"+metaLine+\")</small></span>\" +
                    \"</label>\");
            list.append(row);
        });
        filterExportList();

        });
    });

    /* check‑all / uncheck‑all buttons -------------------------------- */
    $('#bookit-check-all').on('click', function () {
        // check only those check‑boxes whose row is currently visible
        $('#bookit-export-list label:visible input').prop('checked', true);
    });

    $('#bookit-uncheck-all').on('click', function () {
        // uncheck only the boxes of visible rows (keeps hidden‑row state intact)
        $('#bookit-export-list label:visible input').prop('checked', false);
    });

    /* ---------- live search inside modal -------------------------------- */
function filterExportList() {
    const val = $('#bookit-modal-search').val().toLowerCase().trim();
    console.log('[BookIT] filterExportList(), query = \"' + val + '\"');

    $('#bookit-export-list label').each(function () {
        const \$row = $(this);                           // \$ = escaped for PHP
        const show = \$row.text().toLowerCase().includes(val);

        // keep Bootstrap’s flex layout when visible, or switch to d-none when hidden
        \$row.toggleClass('d-flex',  show)
             .toggleClass('d-none', !show);
    });
}

    /* fire on every keystroke in the search box ------------------------- */
    $('#bookit-modal-search').on('input', function () {
        console.log('[BookIT] input event from search box');
        filterExportList();
    });

    /* Confirm‑Export → redirect to export_events.php ----------------- */
    $('#bookit-export-confirm').on('click', function () {
        const ids = $('#bookit-export-list input:checked').map(function(){return this.value;}).get();
        if (!ids.length) { alert('".get_string('chooseevent', 'mod_bookit')."'); return; }

        const qs = new URLSearchParams({id: {$cm->id}});
        if (window.currentFilterParams) { Object.entries(window.currentFilterParams).forEach(([k,v])=>qs.append(k,v)); }
        ids.forEach(id => qs.append('ids[]', id));

        window.location = M.cfg.wwwroot + '/mod/bookit/export_events.php?' + qs.toString();
        $('#bookit-export-modal').modal('hide');
    });
});
");

//Calendar feed URL & caps passed to AMD module 
$eventsource = (new moodle_url('/mod/bookit/events.php', ['id' => $cm->id]))->out(false);
$capabilities   = [
    'addevent' => has_capability('mod/bookit:addevent', $modulecontext),
];

//Minor change to main: Handles edge cases better now
$configcalendar = [];
$tc = get_config('mod_bookit', 'textcolor');
if ($tc !== false && $tc !== null && $tc !== '') {
    $configcalendar['textcolor'] = $tc;
}


// Inject allowed weekdays for JS (NEW FEATURE)
$PAGE->requires->js_init_code('M.cfg.bookit_allowedweekdays = ['.implode(',', bookit_allowed_weekdays()).'];');

// Log the view event (WORK IN PROGRESS)
$event = course_module_viewed::create(['objectid'=>$moduleinstance->id,'context'=>$modulecontext]);
$event->add_record_snapshot('course',$course);
$event->add_record_snapshot('bookit',$moduleinstance);
$event->trigger();

// Set page settings.
$PAGE->set_url('/mod/bookit/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->requires->js(new moodle_url('/mod/bookit/assets/event-calendar.min.js'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/assets/event-calendar.min.css'), true);
$PAGE->requires->css(new moodle_url('/mod/bookit/assets/custom-calendar.min.css'), true);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Page Output
echo $OUTPUT->header();

//NEW FEATURE: Filter bar + Export button
echo html_writer::start_div('bookit-filters d-flex gap-2 mb-3');

/* room select */
echo html_writer::start_tag('select',['id'=>'filter-room','class'=>'form-select w-auto']);
echo html_writer::tag('option', get_string('allrooms', 'mod_bookit'), ['value'=>'']);
foreach ($rooms as $rid=>$rname) {
    echo html_writer::tag('option', format_string($rname), ['value'=>$rid]);
}
echo html_writer::end_tag('select');

/* faculty select */
echo html_writer::start_tag('select',['id'=>'filter-faculty','class'=>'form-select w-auto']);
echo html_writer::tag('option', get_string('allfaculties', 'mod_bookit'), ['value'=>'']);
foreach ($faculties as $fac) {
    echo html_writer::tag('option', format_string($fac), ['value'=>$fac]);
}
echo html_writer::end_tag('select');

/* status select */
echo html_writer::start_tag('select',['id'=>'filter-status','class'=>'form-select w-auto']);
echo html_writer::tag('option', get_string('allstatuses', 'mod_bookit'), ['value'=>'']);
foreach ($statusmap as $scode=>$label) {
    echo html_writer::tag('option', $label, ['value'=>$scode]);
}
echo html_writer::end_tag('select');

echo html_writer::end_div(); // .bookit-filters

/* export button */
echo html_writer::tag('button', get_string('exportevents', 'mod_bookit'),
    ['id'=>'bookit-export','class'=>'btn btn-secondary mb-3']);

/* calendar */
echo html_writer::div('', '', ['id'=>'ec']);

//Export‑selection modal (NEW FEATURE)
echo '
<div class="modal fade" id="bookit-export-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">'.get_string('exportevents', 'mod_bookit').'</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">

        <!-- searchbar ------------------------------------------------>
    <div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
        <label for="bookit-modal-search" class="mb-0">
        '.get_string('search').':
        </label>

        <input type="text" id="bookit-modal-search"
            class="form-control w-auto d-inline">
    </div>



        <!-- check/uncheck buttons --------------------------------------->
        <div class="mb-2">
          <button type="button" class="btn btn-sm btn-light mr-1" id="bookit-check-all">'
            .get_string('selectall').'</button>
          <button type="button" class="btn btn-sm btn-light"       id="bookit-uncheck-all">'
            .get_string('deselectall').'</button>
        </div>

        <!-- list of events --------------------------------------------->
        <div id="bookit-export-list" class="list-group small"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">'
            .get_string('cancel').'</button>
        <button type="button" class="btn btn-primary" id="bookit-export-confirm">'
            .get_string('export', 'mod_bookit').'</button>
      </div>
    </div>
  </div>
</div>';


//Initialise AMD calendar (from original file)
$PAGE->requires->js_call_amd('mod_bookit/calendar', 'init',
        [
                $cm->id,
                $eventsource,
                $capabilities,
                current_language(),
                $configcalendar,
        ]);

echo $OUTPUT->footer();
