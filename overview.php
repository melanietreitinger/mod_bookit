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

global $USER;
$events = event_manager::get_events_for_examiner($USER->id);


/* ----- status → label / colours -------------------------------------- */
$statusmap = [
    0 => 'New',
    1 => 'In progress',
    2 => 'Accepted',
    3 => 'Cancelled',
    4 => 'Rejected',
];
$colormap = [
    0 => '#d3d3d3',
    1 => '#fff3cd',
    2 => '#d4edda',
    3 => '#343a40',
    4 => '#f8d7da',
];
$textmap  = [3 => '#ffffff'];

/* =======================================================================
   4+5.  Render via Mustache template (no HTML in PHP)
   ======================================================================= */

// Build display maps.
$statusmap = [
    0 => 'New',
    1 => 'In progress',
    2 => 'Accepted',
    3 => 'Cancelled',
    4 => 'Rejected',
];
$colormap = [
    0 => '#d3d3d3',
    1 => '#fff3cd',
    2 => '#d4edda',
    3 => '#343a40',
    4 => '#f8d7da',
];
$textmap  = [3 => '#ffffff'];

// Prepare template context.
$templatecontext = [
    'tableid' => (string)$tableid,
    'events'  => [],
];

foreach ($events as $ev) {
    $room = $ev->room ?: '-';

    $statusbg  = $colormap[$ev->bookingstatus] ?? '#ffffff';
    $statusfg  = $textmap[$ev->bookingstatus] ?? '#000000';
    $statustxt = $statusmap[$ev->bookingstatus] ?? '-';

    // My role.
    $myrole = '-';

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

    $datestr = userdate($ev->starttime, '%d.%m.%Y');

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
        'statustext' => s($statustxt),
        'statusstyle' => "background-color:$statusbg;color:$statusfg;",
        'datestr' => $datestr,
        'starttime' => (int)$ev->starttime,
        'cmid' => (int)$cm->id,
        'checklistprogress' => '--',
        'checklistlabel' => 'Checklist',
    ];
}

// Render Mustache.
echo $OUTPUT->render_from_template('mod_bookit/overview/examiner_overview', $templatecontext);
echo $OUTPUT->footer();
