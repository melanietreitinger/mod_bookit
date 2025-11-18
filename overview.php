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
                      const A = $(a).children().eq(col).text().trim().toLowerCase();
                      const B = $(b).children().eq(col).text().trim().toLowerCase();
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
use mod_bookit\local\entity\examiner_event_repository;

global $USER;
$events = examiner_event_repository::get_events_for_examiner($USER->id);


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
   4.  Live-search box
   ======================================================================= */
echo html_writer::div(
    html_writer::label(get_string('search') . ': ', 'bookit-filter', false, ['class' => 'mr-2']) .
    html_writer::empty_tag('input', [
        'type'  => 'text',
        'id'    => 'bookit-filter',
        'class' => 'form-control w-auto d-inline',
    ]),
    'mb-3'
);

/* =======================================================================
   5.  Render table
   ======================================================================= */
echo html_writer::start_tag('table', [
    'id'    => $tableid,
    'class' => 'generaltable table-striped table-hover w-100',
]);

// ... Header row ...
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr', ['style' => 'background-color:#cfe2ff;']);
foreach ([
    'ID',
    'Title',
    'Room',
    'Person in charge',
    'My role',
    'Booking status',
    'Date',
    'Checklist progress',
    'Checklist'
] as $head) {
    echo html_writer::tag('th', $head);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

// ... Body ...
echo html_writer::start_tag('tbody');
foreach ($events as $ev) {
    $room      = $ev->room ?: '-';
    $statusbg  = $colormap[$ev->bookingstatus];
    $statusfg  = $textmap[$ev->bookingstatus] ?? '#000000';
    $statustxt = $statusmap[$ev->bookingstatus];
    $myrole = '-';

    // Person in charge.
    if ($USER->id == $ev->personinchargeid) {
        $myrole = 'Person in charge';
    }
    // Other examiners.
    else if (!empty($ev->otherexaminers)) {
        $others = is_array($ev->otherexaminers)
            ? $ev->otherexaminers
            : array_filter(array_map('intval', explode(',', $ev->otherexaminers)));

        if (in_array($USER->id, $others)) {
            $myrole = 'Other examiner';
        }
    }
    // Booking person.
    else if ($USER->id == $ev->usermodified) {
        $myrole = 'Booking person';
    }

    // Support person.
    else if (!empty($ev->supportpersons)) {
        $support = is_array($ev->supportpersons)
            ? $ev->supportpersons
            : array_filter(array_map('intval', explode(',', $ev->supportpersons)));

        if (in_array($USER->id, $support)) {
            $myrole = 'Support person';
        }
    }


    $date = userdate($ev->starttime, '%d.%m.%Y');

    // Title -> ModalForm trigger.
    $titlelink = html_writer::link(
        '#',
        format_string($ev->name),
        [
            'class'        => 'bookit-event-link',
            'data-eventid' => $ev->id,
            'data-cmid'    => $cm->id,
        ]
    );

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $ev->id);
    echo html_writer::tag('td', $titlelink);
    echo html_writer::tag('td', s($room));

    // Person in charge.
    $pic = $ev->personinchargeid ? fullname(core_user::get_user($ev->personinchargeid)) : '-';
    echo html_writer::tag('td', s($pic));

    // My role.
    echo html_writer::tag('td', s($myrole));

    // Continue with existing status column.
    echo html_writer::tag(
        'td',
        s($statustxt),
        ['style' => "background-color:$statusbg;color:$statusfg;"]
    );

    echo html_writer::tag('td', $date);
    echo html_writer::tag(
        'td',
        html_writer::tag('span', '--', ['class' => 'badge bg-secondary'])
    );
    echo html_writer::tag(
        'td',
        html_writer::link('#', 'Checklist', ['class' => 'btn btn-sm btn-primary'])
    );
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
