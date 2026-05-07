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

use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_checklist_state_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\event_resource_manager;

/* =======================================================================
   0.  Setup, capability checks
   ======================================================================= */
$id = required_param('id', PARAM_INT);            // Course-module id.
$cm = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/bookit:viewownoverview', $context);

global $USER, $DB;
$tab = optional_param('tab', 'myevents', PARAM_ALPHA);
$canmanage = has_capability('mod/bookit:managebasics', $context);
$canmanageopenrequests = event_access_manager::can_manage_open_requests($context);
$showreportfilters = $canmanageopenrequests;
$currenttab = ($tab === 'openrequests' && $canmanageopenrequests) ? 'openrequests' : 'myevents';
$tableid = $currenttab === 'openrequests' ? 'open-requests-table' : 'overview-table';

[$defaultreportstart, $defaultreportend] = event_manager::get_reporting_default_range();
$defaultreportstartvalue = date('Y-m-d', $defaultreportstart);
$defaultreportendvalue = date('Y-m-d', $defaultreportend);
$reportstartvalue = optional_param('reportstart', $defaultreportstartvalue, PARAM_TEXT);
$reportendvalue = optional_param('reportend', $defaultreportendvalue, PARAM_TEXT);
$selectedsemesterids = optional_param_array('semesterids', [], PARAM_INT);
$hasexplicitreportfilters = $showreportfilters && (
    array_key_exists('reportstart', $_GET)
    || array_key_exists('reportend', $_GET)
    || array_key_exists('semesterids', $_GET)
);
if ($showreportfilters && empty($selectedsemesterids)) {
    $selectedsemesterids = [event_manager::get_current_semester()];
}
$appliedsemesterids = $hasexplicitreportfilters ? $selectedsemesterids : [];

$parsetimestamp = static function (string $value, int $fallback, bool $endofday = false): int {
    $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value . ($endofday ? ' 23:59:59' : ' 00:00:00'));
    if (!$date) {
        return $fallback;
    }
    return $date->getTimestamp();
};

$reportstarttimestamp = $parsetimestamp($reportstartvalue, $defaultreportstart);
$reportendtimestamp = $parsetimestamp($reportendvalue, $defaultreportend, true);
if ($reportendtimestamp < $reportstarttimestamp) {
    [$reportstarttimestamp, $reportendtimestamp] = [$reportendtimestamp, $reportstarttimestamp];
    [$reportstartvalue, $reportendvalue] = [$reportendvalue, $reportstartvalue];
}

/* =======================================================================
   1.  Front-end requirements
   ======================================================================= */
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
$PAGE->set_title(get_string('overview', 'mod_bookit'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

/* =======================================================================
   3.  Fetch examiner’s events
   ======================================================================= */
$events = $showreportfilters
    ? event_manager::get_events_for_reporting(
        $context,
        (int)$USER->id,
        $reportstarttimestamp,
        $reportendtimestamp,
        $appliedsemesterids
    )
    : event_manager::get_events_for_examiner($USER->id);
$openrequests = $canmanageopenrequests ? event_manager::get_open_requests() : [];
$openrequestcount = $canmanageopenrequests ? event_manager::count_open_requests() : 0;
$semesteroptions = [];
if ($showreportfilters) {
    foreach (event_manager::get_semester_filter_options() as $value => $label) {
        $semesteroptions[] = [
            'value' => (string)$value,
            'label' => $label,
            'selected' => in_array((int)$value, $selectedsemesterids, true),
        ];
    }
}

// Fetch master checklist ID directly (no entity = no JS side effects).
$masterrecord = $DB->get_record('bookit_checklist_master', ['isdefault' => 1], 'id', IGNORE_MULTIPLE);
$masterid = $masterrecord ? (int)$masterrecord->id : 0;


/* ----- status → label / colours (via event_manager) ------------------- */
$statuscolors = event_manager::get_booking_status_colors();

/* =======================================================================
   4+5.  Render via Mustache template (no HTML in PHP)
   ======================================================================= */

$templatecontext = [
    'cmid' => $cm->id,
    'tableid' => (string)$tableid,
    'canmanage' => $canmanage,
    'showopenrequestsnav' => $canmanageopenrequests,
    'showmyeventssection' => $currenttab === 'myevents',
    'showopenrequestssection' => $currenttab === 'openrequests',
    'myeventsactive' => $currenttab === 'myevents',
    'openrequestsactive' => $currenttab === 'openrequests',
    'myeventsurl' => (new moodle_url('/mod/bookit/overview.php', ['id' => $cm->id, 'tab' => 'myevents']))->out(false),
    'openrequestsurl' => (new moodle_url('/mod/bookit/overview.php', ['id' => $cm->id, 'tab' => 'openrequests']))->out(false),
    'myeventstitle' => $showreportfilters
        ? get_string('overview_all_events', 'mod_bookit')
        : get_string('overview_my_events', 'mod_bookit'),
    'openrequeststitle' => get_string('overview_open_requests', 'mod_bookit'),
    'openrequestshelp' => get_string('overview_open_requests_help', 'mod_bookit'),
    'openrequestsempty' => get_string('overview_open_requests_empty', 'mod_bookit'),
    'openrequestcount' => $openrequestcount,
    'openrequestcounttext' => get_string('overview_open_request_count', 'mod_bookit', $openrequestcount),
    'showreportfilters' => $showreportfilters,
    'reportinghelp' => get_string('overview_reporting_help', 'mod_bookit'),
    'reportstartvalue' => $reportstartvalue,
    'reportendvalue' => $reportendvalue,
    'reportstartlabel' => get_string('overview_filter_startdate', 'mod_bookit'),
    'reportendlabel' => get_string('overview_filter_enddate', 'mod_bookit'),
    'reportapplylabel' => get_string('overview_apply_filters', 'mod_bookit'),
    'reportresetlabel' => get_string('overview_reset_filters', 'mod_bookit'),
    'reportreseturl' => (new moodle_url('/mod/bookit/overview.php', ['id' => $cm->id, 'tab' => 'myevents']))->out(false),
    'semesteroptions' => $semesteroptions,
    'hasevents' => false,
    'eventcounttext' => get_string('overview_count', 'mod_bookit', 0),
    'noeventsmessage' => get_string('overview_no_results', 'mod_bookit'),
    'hasopenrequests' => !empty($openrequests),
    'events' => [],
    'openrequests' => [],
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

$prepareeventrow = function (
    stdClass $ev,
    bool $isopenrequest = false
) use (
    $USER,
    $context,
    $cm,
    $masterid,
    $canmanage,
    $statuscolors,
    $progressmap,
    $resourceprogressmap
): array {
    $room = $ev->room ?: '-';

    $statusbg  = $statuscolors[$ev->bookingstatus]['bg'] ?? '#ffffff';
    $statusfg  = $statuscolors[$ev->bookingstatus]['fg'] ?? '#000000';
    $statustxt = get_string('event_bookingstatus_' . (int)($ev->bookingstatus ?? 0), 'mod_bookit');
    $statusclass = event_manager::get_booking_status_class((int)($ev->bookingstatus ?? 0));
    $statusgroupkey = match ((int)($ev->bookingstatus ?? 0)) {
        event_access_manager::BOOKINGSTATUS_NEW,
        event_access_manager::BOOKINGSTATUS_IN_PROGRESS => 'open',
        event_access_manager::BOOKINGSTATUS_ACCEPTED => 'confirmed',
        default => 'closed',
    };
    $statusgrouptext = get_string('overview_status_group_' . $statusgroupkey, 'mod_bookit');

    // My role.
    $myrole = '-';

    $roles = [];

    foreach (event_access_manager::get_user_roles_for_event($ev, (int)$USER->id) as $role) {
        if ($role === 'personincharge') {
            $roles[] = get_string('overview_role_personincharge', 'mod_bookit');
        } else if ($role === 'bookingperson') {
            $roles[] = get_string('overview_role_bookingperson', 'mod_bookit');
        } else if ($role === 'otherexaminer') {
            $roles[] = get_string('overview_role_otherexaminer', 'mod_bookit');
        } else if ($role === 'supportperson') {
            $roles[] = get_string('overview_role_supportperson', 'mod_bookit');
        }
    }

    $myrole = $roles ? implode(', ', $roles) : '-';

    $datestr = userdate($ev->starttime, '%d.%m.%Y');
    $canviewchecklist = event_access_manager::can_view_event_checklist($ev, $context, (int)$USER->id);
    $canviewresources = event_access_manager::can_view_event_resources($ev, $context, (int)$USER->id);

    $pic = '-';
    if (!empty($ev->personinchargeid)) {
        $u = core_user::get_user((int)$ev->personinchargeid);
        $pic = $u ? fullname($u) : '-';
    }

    $actions = [];
    if ($isopenrequest) {
        foreach (event_manager::get_booking_status_transition_options((int)($ev->bookingstatus ?? 0)) as $option) {
            $btnclass = 'btn-outline-secondary';
            if ((int)$option['value'] === event_access_manager::BOOKINGSTATUS_IN_PROGRESS) {
                $btnclass = 'btn-warning';
            } else if ((int)$option['value'] === event_access_manager::BOOKINGSTATUS_ACCEPTED) {
                $btnclass = 'btn-success';
            } else if ((int)$option['value'] === event_access_manager::BOOKINGSTATUS_CANCELED) {
                $btnclass = 'btn-dark';
            } else if ((int)$option['value'] === event_access_manager::BOOKINGSTATUS_REJECTED) {
                $btnclass = 'btn-danger';
            }

            $actions[] = [
                'value' => (int)$option['value'],
                'eventid' => (int)$ev->id,
                'cmid' => (int)$cm->id,
                'label' => match ((int)$option['value']) {
                    event_access_manager::BOOKINGSTATUS_IN_PROGRESS => get_string('bookingstatus_action_inprogress', 'mod_bookit'),
                    event_access_manager::BOOKINGSTATUS_ACCEPTED => get_string('bookingstatus_action_accept', 'mod_bookit'),
                    event_access_manager::BOOKINGSTATUS_CANCELED => get_string('bookingstatus_action_cancel', 'mod_bookit'),
                    event_access_manager::BOOKINGSTATUS_REJECTED => get_string('bookingstatus_action_reject', 'mod_bookit'),
                    default => $option['label'],
                },
                'btnclass' => $btnclass,
            ];
        }
    }

    $caneventdetails = event_access_manager::can_user_view_event_details($ev, $context, (int)$USER->id);

    return [
        'id' => (string)$ev->id,
        'name' => format_string($ev->name),
        'caneventdetails' => $caneventdetails,
        'room' => s($room),
        'personincharge' => s($pic),
        'myrole' => s($myrole),
        'statustext' => s($statustxt),
        'statusstyle' => "background-color:$statusbg;color:$statusfg;",
        'statusclass' => $statusclass,
        'statusgroupkey' => $statusgroupkey,
        'statusgrouptext' => s($statusgrouptext),
        'bookingstatus' => (int)($ev->bookingstatus ?? 0),
        'canmanage'     => $canmanage,
        'statusoptions' => $canmanage
            ? event_manager::get_booking_status_options((int)($ev->bookingstatus ?? 0))
            : [],
        'hasactions' => !empty($actions),
        'actions' => $actions,
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
};

foreach ($events as $ev) {
    if (event_access_manager::can_user_view_event_in_overview($ev, $context, (int)$USER->id)) {
        $templatecontext['events'][] = $prepareeventrow($ev);
    }
}

$templatecontext['hasevents'] = !empty($templatecontext['events']);
$templatecontext['eventcounttext'] = get_string('overview_count', 'mod_bookit', count($templatecontext['events']));

foreach ($openrequests as $ev) {
    $templatecontext['openrequests'][] = $prepareeventrow($ev, true);
}

// Render Mustache.
echo $OUTPUT->render_from_template('mod_bookit/view/examiner_overview', $templatecontext);
echo $OUTPUT->footer();
