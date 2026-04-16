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
 * Examiner overview – filterable & sortable (opens events in a ModalForm).
 *
 * @package     mod_bookit
 * @copyright   2025 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

/* =======================================================================
   0.  Setup, capability checks
   ======================================================================= */
$id = required_param('id', PARAM_INT);            // Course-module id.
$cm = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/bookit:viewownoverview', $context);

/* =======================================================================
   1.  Front-end requirements
   ======================================================================= */
$tableid = 'overview-table';

$PAGE->requires->jquery();

/* ----- live search ---------------------------------------------------- */
$PAGE->requires->js_init_code("
    require(['jquery'], function($) {
        $('#bookit-filter').on('keyup', function () {
            const val = $(this).val().toLowerCase();
            $('#{$tableid} tbody tr').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(val) !== -1);
            });
        });
    });
");

/* ----- sortable columns ---------------------------------------------- */
$PAGE->requires->js_init_code("
    require(['jquery'], function($) {
        const table = $('#{$tableid}');
        table.find('th').each(function(col) {
            let asc = true;
            $(this)
              .css('cursor','pointer')
              .append('<span class=\"sortarrow\"> ▲</span>')
              .on('click', function () {
                  const rows = table.find('tbody tr').get();
                  rows.sort(function(a,b) {
                    const tdA = $(a).children().eq(col);
                    const tdB = $(b).children().eq(col);

                    const sortA = tdA.data('sort');
                    const sortB = tdB.data('sort');

                    if (sortA !== undefined && sortB !== undefined) {
                        return asc ? (sortA - sortB) : (sortB - sortA);
                    }

                    const A = tdA.text().trim().toLowerCase();
                    const B = tdB.text().trim().toLowerCase();

                    const cmp = ($.isNumeric(A) && $.isNumeric(B)) ? (A - B) : A.localeCompare(B);
                    return asc ? cmp : -cmp;
                });
                  $.each(rows, (_, row) => table.children('tbody').append(row));
                  asc = !asc;
                  table.find('th .sortarrow').text('');
                  $(this).find('.sortarrow').text(asc ? ' ▲' : ' ▼');
              });
        });
    });
");

/* ----- inline ModalForm handler -------------------------------------- */
$PAGE->requires->js_call_amd('mod_bookit/event_details_modal', 'init');
$PAGE->requires->js_call_amd('mod_bookit/overview/booking_status_dropdown', 'init');


/* =======================================================================
   2.  Page headings
   ======================================================================= */
$PAGE->set_url('/mod/bookit/overview.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('overview', 'bookit'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

/* =======================================================================
   3.  Fetch examiner’s events
   ======================================================================= */
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_checklist_state_manager;
use mod_bookit\local\manager\event_resource_manager;

global $USER, $DB;
$events = event_manager::get_events_for_examiner($USER->id);

// Fetch master checklist ID directly (no entity = no JS side effects).
$masterrecord = $DB->get_record('bookit_checklist_master', ['isdefault' => 1], 'id', IGNORE_MULTIPLE);
$masterid = $masterrecord ? (int)$masterrecord->id : 0;


/* ----- status → label / colours (via event_manager) ------------------- */
$statuscolors = event_manager::get_booking_status_colors();

/* =======================================================================
   4+5.  Render via Mustache template (no HTML in PHP)
   ======================================================================= */

// Prepare template context.
$canmanage = has_capability('mod/bookit:managebasics', $context);
$templatecontext = [
    'tableid'    => (string)$tableid,
    'canmanage'  => $canmanage,
    'events'     => [],
];

// Precompute checklist progress for all events in a single query.
$progressmap = [];
$resourceprogressmap = [];
if (!empty($events)) {
    $eventids = array_map(fn($ev) => (int)$ev->id, $events);
    if ($masterid > 0) {
        $progressmap = event_checklist_state_manager::get_progress_percent_for_events($eventids, $masterid);
    }
    $resourceprogressmap = event_resource_manager::get_resource_progress_for_events($eventids);
}

foreach ($events as $ev) {
    $room = $ev->room ?: '-';

    $statusbg  = $statuscolors[$ev->bookingstatus]['bg'] ?? '#ffffff';
    $statusfg  = $statuscolors[$ev->bookingstatus]['fg'] ?? '#000000';
    $statustxt = get_string('event_bookingstatus_' . (int)($ev->bookingstatus ?? 0), 'mod_bookit');

    // My role.
    $myrole = '-';

    $roles = [];

    if ($USER->id == $ev->personinchargeid) {
        $roles[] = 'Person in charge';
    }

    if ($USER->id == $ev->usermodified) {
        $roles[] = 'Booking person';
    }

    $otherids = array_filter(explode(',', $ev->otherexaminers ?? ''));
    if (in_array($USER->id, $otherids)) {
        $roles[] = 'Other examiner';
    }

    $supportids = array_filter(explode(',', $ev->supportpersons ?? ''));
    if (in_array($USER->id, $supportids)) {
        $roles[] = 'Support person';
    }

    $myrole = $roles ? implode(', ', $roles) : '-';
    /*
    // 1. Person in charge.
    if ((int)$USER->id === (int)$ev->personinchargeid) {
        $myrole = 'Person in charge';
    }

    // 2. Other examiner.
    $others = array_filter(array_map('intval', explode(',', $ev->otherexaminers ?? '')));
    if ($myrole === '-' && in_array((int)$USER->id, $others, true)) {
        $myrole = 'Other examiner';
    }

    // 3. Booking person.
    if ($myrole === '-' && (int)$USER->id === (int)$ev->usermodified) {
        $myrole = 'Booking person';
    }

    // 4. Support person.
    $support = array_filter(array_map('intval', explode(',', $ev->supportpersons ?? '')));
    if ($myrole === '-' && in_array((int)$USER->id, $support, true)) {
        $myrole = 'Support person';
    }
    */



    $datestr = userdate($ev->starttime, '%d.%m.%Y');
    $canviewchecklist = event_access_manager::can_view_event_checklist($ev, $context, (int)$USER->id);
    $canviewresources = event_access_manager::can_view_event_resources($ev, $context, (int)$USER->id);

    $pic = '-';
    if (!empty($ev->personinchargeid)) {
        $u = core_user::get_user((int)$ev->personinchargeid);
        $pic = $u ? fullname($u) : '-';
    }

    $templatecontext['events'][] = [
        'id' => (string)$ev->id,
        'name' => format_string($ev->name),
        'room' => s($room),
        'personincharge' => s($pic),
        'myrole' => s($myrole),
        'statustext'    => s($statustxt),
        'statusstyle'   => "background-color:$statusbg;color:$statusfg;",
        'bookingstatus' => (int)($ev->bookingstatus ?? 0),
        'canmanage'     => $canmanage,
        'statusoptions' => $canmanage
            ? event_manager::get_booking_status_options((int)($ev->bookingstatus ?? 0))
            : [],
        'datestr' => $datestr,
        'starttime' => (int)$ev->starttime,
        'cmid' => (int)$cm->id,
        'checklistprogress' => $progressmap[(int)$ev->id] ?? 0,
        'checklistprogress_available' => $masterid > 0,
        'haschecklistaction' => $canviewchecklist,
        'checklistlabel' => get_string('checklist', 'mod_bookit'),
        'checklisturl' => (new moodle_url('/mod/bookit/view/event_checklist_view.php', [
            'id' => $cm->id,
            'eventid' => (int)$ev->id,
        ]))->out(false),
        'hasresourcesaction' => $canviewresources,
        'resourceschecklistlabel' => get_string('resources', 'mod_bookit'),
        'resourceschecklisturl' => (new moodle_url('/mod/bookit/view/event_resources.php', [
            'id' => $cm->id,
            'eventid' => (int)$ev->id,
        ]))->out(false),
        'resourcesprogress' => $resourceprogressmap[(int)$ev->id]['percent'] ?? 0,
        'resourcesprogress_available' => ($resourceprogressmap[(int)$ev->id]['total'] ?? 0) > 0,
    ];
}

// Render Mustache.
echo $OUTPUT->render_from_template('mod_bookit/view/examiner_overview', $templatecontext);
echo $OUTPUT->footer();
