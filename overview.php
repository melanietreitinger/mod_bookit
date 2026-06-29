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

use mod_bookit\local\tabs;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_checklist_state_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\event_resource_manager;
use mod_bookit\output\booking_status_cell;

/* =======================================================================
   0.  Setup, capability checks
   ======================================================================= */
$id = required_param('id', PARAM_INT);            // Course-module id.
$cm = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$course  = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
$isobserverrestricted = event_access_manager::is_observer_restricted_mode($context);
if (!$isobserverrestricted && !has_capability('mod/bookit:viewownoverview', $context)) {
    require_capability('mod/bookit:viewownoverview', $context);
}

global $USER, $DB;
$tab = optional_param('tab', 'myevents', PARAM_ALPHA);
$canmanage = has_capability('mod/bookit:managebasics', $context);
$canmanageopenrequests = event_access_manager::can_manage_open_requests($context);
$showreportfilters = $canmanageopenrequests || $isobserverrestricted;
$checklistenabled = event_access_manager::is_checklist_enabled();
$resourcesenabled = event_access_manager::is_resources_enabled();
$hasexplicitstatusfilter = array_key_exists('bookingstatusfilter', $_GET);
$rawstatusfilter = optional_param_array('bookingstatusfilter', [], PARAM_INT);
$selectedstatuses = event_manager::resolve_overview_booking_status_filter_ids(
    $rawstatusfilter,
    $hasexplicitstatusfilter
);
$selectedfacultyid = optional_param('facultyid', 0, PARAM_INT);
$selectedsemesterids = optional_param_array('semesterids', [], PARAM_INT);
$queuepage = max(1, optional_param('queuepage', 1, PARAM_INT));
$queueperpage = 25;
$isinrequestworkspace = $canmanageopenrequests && in_array($tab, ['openrequests', 'acceptedrequests', 'rejectedrequests'], true);
$requestworkspacemode = match ($tab) {
    'rejectedrequests' => 'rejectedrequests',
    'acceptedrequests' => 'acceptedrequests',
    default => 'openrequests',
};
$currenttab = match (true) {
    $isobserverrestricted => 'myevents',
    $isinrequestworkspace => 'openrequests',
    $tab === 'history' => 'history',
    default => 'myevents',
};
$tableid = match (true) {
    $isinrequestworkspace && $requestworkspacemode === 'rejectedrequests' => 'rejected-requests-table',
    $isinrequestworkspace && $requestworkspacemode === 'acceptedrequests' => 'accepted-requests-table',
    $isinrequestworkspace => 'open-requests-table',
    default => 'overview-table',
};

[$defaultreportstart, $defaultreportend] = event_manager::get_reporting_default_range(
    null,
    $showreportfilters && $canmanageopenrequests
);
$defaultreportstartvalue = date('Y-m-d', $defaultreportstart);
$defaultreportendvalue = date('Y-m-d', $defaultreportend);
$reportstartvalue = optional_param('reportstart', $defaultreportstartvalue, PARAM_TEXT);
$reportendvalue = optional_param('reportend', $defaultreportendvalue, PARAM_TEXT);
$hasexplicitsemesterfilter = array_key_exists('semesterids', $_GET);
if ($showreportfilters) {
    $selectedsemesterids = event_manager::resolve_effective_semester_filter_ids(
        $selectedsemesterids,
        $hasexplicitsemesterfilter
    );
}
$overviewfilters = [
    'bookingstatuses' => $selectedstatuses,
    'facultyids' => $selectedfacultyid > 0 ? [$selectedfacultyid] : [],
    'semesterids' => $selectedsemesterids,
];
$overviewnavigationparams = [];
if ($hasexplicitstatusfilter) {
    $overviewnavigationparams['bookingstatusfilter'] = $selectedstatuses;
}
if ($selectedfacultyid > 0) {
    $overviewnavigationparams['facultyid'] = $selectedfacultyid;
}
if (!empty($selectedsemesterids) || $hasexplicitsemesterfilter) {
    $overviewnavigationparams['semesterids'] = $selectedsemesterids;
}
if ($showreportfilters) {
    $overviewnavigationparams['reportstart'] = $reportstartvalue;
    $overviewnavigationparams['reportend'] = $reportendvalue;
}
$requestnavigationparams = $overviewnavigationparams;
if ($queuepage > 1) {
    $requestnavigationparams['queuepage'] = $queuepage;
}

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
    $overviewnavigationparams['reportstart'] = $reportstartvalue;
    $overviewnavigationparams['reportend'] = $reportendvalue;
    $requestnavigationparams['reportstart'] = $reportstartvalue;
    $requestnavigationparams['reportend'] = $reportendvalue;
}

$buildoverviewurl = static function (string $targettab) use ($cm, $overviewnavigationparams): string {
    return tabs::build_overview_url((int)$cm->id, $targettab, $overviewnavigationparams);
};

$overviewtabrow = tabs::get_overview_inner_tabrow(
    (int)$cm->id,
    $overviewnavigationparams,
    !$isobserverrestricted,
    $canmanageopenrequests
);
$requestworkspacetabrow = $canmanageopenrequests
    ? tabs::get_request_workspace_tabrow((int)$cm->id, $requestnavigationparams)
    : [];

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
$PAGE->requires->js_init_code('window.bookitOverviewReadConfig = ' . json_encode([
    'methodname' => 'mod_bookit_get_overview_queue',
    'cmid' => (int)$cm->id,
    'workspace' => $currenttab === 'openrequests' ? $requestworkspacemode : $currenttab,
    'bookingstatuses' => $overviewfilters['bookingstatuses'],
    'facultyids' => $overviewfilters['facultyids'],
    'semesterids' => $overviewfilters['semesterids'],
    'page' => $queuepage,
    'reportstart' => $reportstartvalue,
    'reportend' => $reportendvalue,
    'openrequestsempty' => get_string('overview_open_requests_empty', 'mod_bookit'),
    'acceptedrequestsempty' => get_string('overview_accepted_requests_empty', 'mod_bookit'),
    'rejectedrequestsempty' => get_string('overview_rejected_requests_empty', 'mod_bookit'),
]));


/* =======================================================================
   2.  Page headings
   ======================================================================= */
$PAGE->set_url('/mod/bookit/overview.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('overview', 'mod_bookit'));
$PAGE->set_heading($course->fullname);
if ($currenttab === 'openrequests') {
    $PAGE->set_secondary_active_tab('bookitopenrequests');
} else {
    $PAGE->set_secondary_active_tab('bookitoverview');
}

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
        $selectedsemesterids
    )
    : event_manager::get_events_for_examiner($USER->id);
$events = event_manager::filter_overview_events(
    $events,
    $overviewfilters,
    $currenttab === 'history'
);
$openrequests = $canmanageopenrequests ? event_manager::get_open_requests() : [];
$openrequestcount = count($openrequests);
$rejectedrequests = $canmanageopenrequests ? event_manager::get_rejected_requests() : [];
$rejectedrequestcount = count($rejectedrequests);
$acceptedrequests = $canmanageopenrequests ? event_manager::get_accepted_requests() : [];
$acceptedrequestcount = count($acceptedrequests);
$requestqueuecount = match ($requestworkspacemode) {
    'rejectedrequests' => $rejectedrequestcount,
    'acceptedrequests' => $acceptedrequestcount,
    default => $openrequestcount,
};
$requesttotalpages = max(1, (int)ceil($requestqueuecount / $queueperpage));
$queuepage = min($queuepage, $requesttotalpages);
if ($requestworkspacemode === 'rejectedrequests') {
    $rejectedrequests = array_values(array_slice($rejectedrequests, ($queuepage - 1) * $queueperpage, $queueperpage));
} else if ($requestworkspacemode === 'acceptedrequests') {
    $acceptedrequests = array_values(array_slice($acceptedrequests, ($queuepage - 1) * $queueperpage, $queueperpage));
} else {
    $openrequests = array_values(array_slice($openrequests, ($queuepage - 1) * $queueperpage, $queueperpage));
}
$requestpaginghtml = '';
if ($isinrequestworkspace && $requestqueuecount > $queueperpage) {
    $requestpaginghtml = $OUTPUT->render(new paging_bar(
        $requestqueuecount,
        max(0, $queuepage - 1),
        $queueperpage,
        new moodle_url('/mod/bookit/overview.php', [
            'id' => $cm->id,
            'tab' => $requestworkspacemode,
        ])
    ));
}
$semesteroptions = [];
foreach (event_manager::get_semester_filter_options() as $value => $label) {
    $semesteroptions[] = [
        'value' => (string)$value,
        'label' => $label,
        'selected' => in_array((int)$value, $selectedsemesterids, true),
    ];
}
$facultyoptions = [[
    'value' => '0',
    'label' => get_string('overview_filter_all_faculties', 'mod_bookit'),
    'selected' => $selectedfacultyid === 0,
]];
foreach (event_manager::get_faculties() as $value => $label) {
    $facultyoptions[] = [
        'value' => (string)$value,
        'label' => $label,
        'selected' => (int)$value === $selectedfacultyid,
    ];
}
$statusfilteroptions = [];
foreach ([0, 1, 2, 3, 4] as $statusvalue) {
    $statusfilteroptions[] = [
        'value' => (string)$statusvalue,
        'label' => event_manager::get_booking_status_label($statusvalue),
        'selected' => in_array($statusvalue, $selectedstatuses, true),
    ];
}

// Fetch master checklist ID directly (no entity = no JS side effects).
$masterrecord = $DB->get_record('bookit_checklist_master', ['isdefault' => 1], 'id', IGNORE_MULTIPLE);
$masterid = $masterrecord ? (int)$masterrecord->id : 0;


/* =======================================================================
   4+5.  Render via Mustache template (no HTML in PHP)
   ======================================================================= */

$templatecontext = [
    'cmid' => $cm->id,
    'tableid' => (string)$tableid,
    'canmanage' => $canmanage,
    'showidcolumn' => $canmanageopenrequests,
    'showmyeventssection' => $currenttab !== 'openrequests',
    'showhistorytab' => !$isobserverrestricted,
    'showoverviewnavigation' => count($overviewtabrow) > 1,
    'overviewtabtree' => count($overviewtabrow) > 1
        ? html_writer::tag(
            'nav',
            $OUTPUT->tabtree($overviewtabrow, $currenttab),
            [
                'class' => 'mod-bookit-overview-inner-tabs',
                'aria-label' => get_string('overview', 'mod_bookit'),
            ]
        )
        : '',
    'showrequestworkspacesection' => $currenttab === 'openrequests',
    'showrequestworkspacetabs' => count($requestworkspacetabrow) > 1,
    'requestworkspacetabtree' => count($requestworkspacetabrow) > 1
        ? html_writer::tag(
            'nav',
            $OUTPUT->tabtree($requestworkspacetabrow, $requestworkspacemode),
            [
                'class' => 'mod-bookit-request-workspace-tabs',
                'aria-label' => get_string('overview_request_workspace_switch', 'mod_bookit'),
            ]
        )
        : '',
    'showopenrequestworkspace' => $currenttab === 'openrequests' && $requestworkspacemode === 'openrequests',
    'showacceptedrequestworkspace' => $currenttab === 'openrequests' && $requestworkspacemode === 'acceptedrequests',
    'showrejectedrequestworkspace' => $currenttab === 'openrequests' && $requestworkspacemode === 'rejectedrequests',
    'requestworkspacetitle' => get_string('overview_request_workspace', 'mod_bookit'),
    'requestworkspacehelp' => get_string('overview_request_workspace_help', 'mod_bookit'),
    'requestqueueswitchhelp' => get_string('overview_request_workspace_switch_help', 'mod_bookit'),
    'historyactive' => $currenttab === 'history',
    'sectiontitle' => $currenttab === 'history'
        ? get_string('overview_history', 'mod_bookit')
        : ($showreportfilters ? get_string('overview_all_events', 'mod_bookit') : get_string('overview_my_events', 'mod_bookit')),
    'openrequeststitle' => get_string('overview_open_requests', 'mod_bookit'),
    'openrequestshelp' => get_string('overview_open_requests_help', 'mod_bookit'),
    'openrequestsempty' => get_string('overview_open_requests_empty', 'mod_bookit'),
    'rejectedrequeststitle' => get_string('overview_rejected_requests', 'mod_bookit'),
    'rejectedrequestshelp' => get_string('overview_rejected_requests_help', 'mod_bookit'),
    'rejectedrequestsempty' => get_string('overview_rejected_requests_empty', 'mod_bookit'),
    'acceptedrequeststitle' => get_string('overview_accepted_requests', 'mod_bookit'),
    'acceptedrequestshelp' => get_string('overview_accepted_requests_help', 'mod_bookit'),
    'acceptedrequestsempty' => get_string('overview_accepted_requests_empty', 'mod_bookit'),
    'requestqueuecurrenttitle' => match ($requestworkspacemode) {
        'rejectedrequests' => get_string('overview_rejected_requests', 'mod_bookit'),
        'acceptedrequests' => get_string('overview_accepted_requests', 'mod_bookit'),
        default => get_string('overview_open_requests', 'mod_bookit'),
    },
    'requestqueuecurrenthelp' => match ($requestworkspacemode) {
        'rejectedrequests' => get_string('overview_rejected_requests_help', 'mod_bookit'),
        'acceptedrequests' => get_string('overview_accepted_requests_help', 'mod_bookit'),
        default => get_string('overview_open_requests_help', 'mod_bookit'),
    },
    'requestqueuecounttext' => match ($requestworkspacemode) {
        'rejectedrequests' => get_string('overview_rejected_request_count', 'mod_bookit', $rejectedrequestcount),
        'acceptedrequests' => get_string('overview_accepted_request_count', 'mod_bookit', $acceptedrequestcount),
        default => get_string('overview_open_request_count', 'mod_bookit', $openrequestcount),
    },
    'requestpaginghtml' => $requestpaginghtml,
    'showoverviewfilters' => $currenttab !== 'openrequests',
    'showreportfilters' => $showreportfilters && $currenttab !== 'openrequests',
    'showcreatedbycolumn' => $showreportfilters && $canmanageopenrequests && $currenttab !== 'openrequests',
    'createdbycolumnlabel' => get_string('event_usermodified', 'mod_bookit'),
    'showprogresscolumn' => $checklistenabled || $resourcesenabled,
    'showchecklistcolumn' => $checklistenabled,
    'showresourcescolumn' => $resourcesenabled,
    'showcancelcolumn' => !$canmanage && !$isobserverrestricted && $currenttab === 'myevents',
    'showreactivatecolumn' => $currenttab === 'history'
        || ($isinrequestworkspace && $requestworkspacemode === 'rejectedrequests'),
    'overviewcancelcolumnlabel' => get_string('overview_cancel_column', 'mod_bookit'),
    'overviewreactivatecolumnlabel' => get_string('overview_cancel_column', 'mod_bookit'),
    'overviewcolumndatetime' => get_string('overview_column_datetime', 'mod_bookit'),
    'reportinghelp' => $currenttab === 'history'
        ? get_string('overview_history_help', 'mod_bookit')
        : get_string('overview_reporting_help', 'mod_bookit'),
    'reportstartvalue' => $reportstartvalue,
    'reportendvalue' => $reportendvalue,
    'reportstartlabel' => get_string('overview_filter_startdate', 'mod_bookit'),
    'reportendlabel' => get_string('overview_filter_enddate', 'mod_bookit'),
    'statusfilterlabel' => get_string('overview_filter_status', 'mod_bookit'),
    'facultyfilterlabel' => get_string('overview_filter_faculty', 'mod_bookit'),
    'semesterfilterlabel' => get_string('select_semester', 'mod_bookit'),
    'reportapplylabel' => get_string('overview_apply_filters', 'mod_bookit'),
    'reportresetlabel' => get_string('overview_reset_filters', 'mod_bookit'),
    'reportreseturl' => (new moodle_url('/mod/bookit/overview.php', ['id' => $cm->id, 'tab' => $currenttab]))->out(false),
    'facultyoptions' => $facultyoptions,
    'statusfilteroptions' => $statusfilteroptions,
    'semesteroptions' => $semesteroptions,
    'hasevents' => false,
    'eventcounttext' => get_string('overview_count', 'mod_bookit', 0),
    'noeventsmessage' => $isobserverrestricted
        ? get_string('observer_empty_state', 'mod_bookit')
        : get_string('overview_no_results', 'mod_bookit'),
    'hasopenrequests' => !empty($openrequests),
    'hasacceptedrequests' => !empty($acceptedrequests),
    'hasrejectedrequests' => !empty($rejectedrequests),
    'events' => [],
    'openrequests' => [],
    'acceptedrequests' => [],
    'rejectedrequests' => [],
];

// Precompute checklist progress for all events in a single query.
$progressmap = [];
$resourceprogressmap = [];
if (!empty($events)) {
    $eventids = array_map(fn($ev) => (int)$ev->id, $events);
    if ($checklistenabled && $masterid > 0) {
        $progressmap = event_checklist_state_manager::get_progress_percent_for_events($eventids, $masterid);
    }
    if ($resourcesenabled) {
        $resourceprogressmap = event_resource_manager::get_resource_progress_for_events($eventids);
    }
}
$historyeventids = array_unique(array_merge(
    array_map(static fn($ev): int => (int)$ev->id, $events),
    array_map(static fn($ev): int => (int)$ev->id, $openrequests),
    array_map(static fn($ev): int => (int)$ev->id, $rejectedrequests),
));
$latesthistorymap = event_manager::get_latest_booking_history_entries($historyeventids);
$historyfieldlabels = [
    'bookingstatus' => 'event_bookingstatus',
    'institutionid' => 'event_department',
    'internalnotes' => 'event_internalnotes',
    'name' => 'event_name',
    'notes' => 'event_notes',
    'otherexaminers' => 'event_otherexaminers',
    'participantsamount' => 'event_students',
    'personinchargeid' => 'event_personincharge',
    'roomid' => 'event_room',
    'starttime' => 'event_start',
];
$resolvehistoryfieldlabel = static function (string $field) use ($historyfieldlabels): string {
    if (!empty($historyfieldlabels[$field])) {
        return get_string($historyfieldlabels[$field], 'mod_bookit');
    }

    return ucfirst(str_replace('_', ' ', $field));
};
$formathistoryvalue = static function (string $field, $value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    return match ($field) {
        'bookingstatus' => event_manager::get_booking_status_label((int)$value),
        'starttime', 'endtime' => userdate((int)$value, get_string('strftimedatetime', 'langconfig')),
        default => is_scalar($value) ? (string)$value : '-',
    };
};
$buildhistorydetails = static function (int $eventid) use ($resolvehistoryfieldlabel, $formathistoryvalue): array {
    $entries = [];
    foreach (array_values(event_manager::get_booking_history($eventid, 10)) as $entry) {
        $actorname = trim(($entry->firstname ?? '') . ' ' . ($entry->lastname ?? ''));
        $summary = get_string('history_action_' . $entry->action, 'mod_bookit') . ' · '
            . userdate((int)$entry->timecreated, get_string('strftimedatetime', 'langconfig'));
        if ($actorname !== '') {
            $summary .= ' · ' . $actorname;
        }

        $changes = [];
        $rawchangedfields = json_decode((string)($entry->changedfields ?? ''), true);
        if (is_array($rawchangedfields)) {
            foreach ($rawchangedfields as $field => $change) {
                $changes[] = [
                    'text' => get_string('overview_workflow_history_change', 'mod_bookit', (object)[
                        'field' => $resolvehistoryfieldlabel((string)$field),
                        'from' => $formathistoryvalue((string)$field, $change['from'] ?? null),
                        'to' => $formathistoryvalue((string)$field, $change['to'] ?? null),
                    ]),
                ];
            }
        }

        $entries[] = [
            'summary' => s($summary),
            'haschanges' => !empty($changes),
            'changes' => $changes,
            'hasrecoverymarker' => !empty($entry->recoverymarker),
            'recoverymarkertext' => get_string('overview_workflow_history_recovery', 'mod_bookit'),
        ];
    }

    return $entries;
};

$prepareeventrow = function (
    stdClass $ev,
    bool $isrequestworkspaceitem = false,
    string $requesttab = 'openrequests'
) use (
    $USER,
    $context,
    $cm,
    $masterid,
    $canmanage,
    $checklistenabled,
    $isobserverrestricted,
    $progressmap,
    $resourcesenabled,
    $resourceprogressmap,
    $latesthistorymap,
    $buildhistorydetails,
    $currenttab,
    $PAGE
): array {
    $room = $ev->room ?: '-';
    $isreservedprojection = $isobserverrestricted;

    $statusgroupkey = event_manager::get_booking_status_group_key((int)($ev->bookingstatus ?? 0));

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
    if ($isreservedprojection) {
        $myrole = '-';
    }

    $datestr = userdate($ev->starttime, '%d.%m.%Y');
    $datetimestr = userdate((int)$ev->starttime, get_string('strftimedatetime', 'langconfig'));
    $canviewchecklist = event_access_manager::can_view_event_checklist($ev, $context, (int)$USER->id);
    $canviewresources = event_access_manager::can_view_event_resources($ev, $context, (int)$USER->id);

    $pic = '-';
    if (!empty($ev->personinchargeid)) {
        $u = core_user::get_user((int)$ev->personinchargeid);
        $pic = $u ? fullname($u) : '-';
    }
    if ($isreservedprojection) {
        $pic = '-';
    }

    $caneventdetails = !$isreservedprojection
        && event_access_manager::can_user_view_event_details($ev, $context, (int)$USER->id);
    $latesthistory = $latesthistorymap[(int)$ev->id] ?? null;
    $latesthistorysummary = '';
    if ($latesthistory) {
        $actorname = trim(($latesthistory->firstname ?? '') . ' ' . ($latesthistory->lastname ?? ''));
        $actionlabel = get_string('history_action_' . $latesthistory->action, 'mod_bookit');
        $latesthistorysummary = $actionlabel . ' · '
            . userdate((int)$latesthistory->timecreated, get_string('strftimedatetime', 'langconfig'));
        if ($actorname !== '') {
            $latesthistorysummary .= ' · ' . $actorname;
        }
    }
    $historydetails = $isrequestworkspaceitem ? $buildhistorydetails((int)$ev->id) : [];

    $createdby = '-';
    if (!empty($ev->usermodified)) {
        if (!empty($ev->creatordeleted)) {
            $createdby = get_string('deleteduser', 'moodle');
        } else if (!empty($ev->creatorfirstname) || !empty($ev->creatorlastname)) {
            $createdby = trim(($ev->creatorfirstname ?? '') . ' ' . ($ev->creatorlastname ?? ''));
        } else {
            $creator = core_user::get_user((int)$ev->usermodified);
            $createdby = $creator ? fullname($creator) : (string)$ev->usermodified;
        }
    }

    $statuscell = booking_status_cell::for_booking_overview_row(
        $ev,
        $context,
        (int)$USER->id,
        (int)$cm->id,
        $canmanage,
        $isrequestworkspaceitem,
        $requesttab,
        $latesthistorysummary,
        $historydetails
    );
    $bookitrenderer = $PAGE->get_renderer('mod_bookit');
    $statuscellhtml = $bookitrenderer->render($statuscell);

    return [
        'id' => (string)$ev->id,
        'name' => $isreservedprojection
            ? get_string('event_reserved', 'mod_bookit')
            : format_string($ev->name),
        'is_reserved_projection' => $isreservedprojection,
        'caneventdetails' => $caneventdetails,
        'room' => s($room),
        'personincharge' => s($pic),
        'myrole' => s($myrole),
        'createdby' => s($createdby),
        'statusgroupkey' => $statusgroupkey,
        'modalfootermode' => $caneventdetails
            ? event_access_manager::get_event_modal_footer_mode($ev, $context, (int)$USER->id)
            : '',
        'statuscellhtml' => $statuscellhtml,
        'datestr' => $datestr,
        'datetimestr' => $datetimestr,
        'starttime' => (int)$ev->starttime,
        'cmid' => (int)$cm->id,
        'checklistprogress' => $progressmap[(int)$ev->id] ?? 0,
        'checklistprogress_available' => $checklistenabled && $masterid > 0,
        'haschecklistaction' => $checklistenabled && !$isreservedprojection && $canviewchecklist,
        'checklistlabel' => get_string('checklist', 'mod_bookit'),
        'checklisturl' => (new moodle_url('/mod/bookit/view/event_checklist_view.php', [
            'id' => $cm->id,
            'eventid' => (int)$ev->id,
        ]))->out(false),
        'hasresourcesaction' => $resourcesenabled && !$isreservedprojection && $canviewresources,
        'resourceschecklistlabel' => get_string('resources', 'mod_bookit'),
        'resourceschecklisturl' => (new moodle_url('/mod/bookit/view/event_resources.php', [
            'id' => $cm->id,
            'eventid' => (int)$ev->id,
        ]))->out(false),
        'resourcesprogress' => $resourceprogressmap[(int)$ev->id]['percent'] ?? 0,
        'resourcesprogress_available' => $resourcesenabled && (($resourceprogressmap[(int)$ev->id]['total'] ?? 0) > 0),
        'hascancelaction' => !$isrequestworkspaceitem
            && !$canmanage
            && !$isreservedprojection
            && event_access_manager::can_participant_overview_cancel($ev, $context, (int)$USER->id),
        'cancelactionlabel' => get_string('overview_cancel_booking', 'mod_bookit'),
        'canceltargetstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
        'hasreactivateaction' => ($requesttab === 'history'
                && event_access_manager::can_reactivate_from_history($ev, $context))
            || ($requesttab === 'rejectedrequests'
                && event_access_manager::can_restore_terminal_request($ev, $context)),
        'reactivateactionlabel' => get_string('bookingstatus_action_reactivate', 'mod_bookit'),
        'reactivatetargetstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        'overviewtab' => $isrequestworkspaceitem
            ? $requesttab
            : ($currenttab === 'history' ? 'history' : 'myevents'),
    ];
};

foreach ($events as $ev) {
    $canviewevent = $currenttab === 'history'
        ? event_access_manager::can_user_view_event_in_history($ev, $context, (int)$USER->id)
        : event_access_manager::can_user_view_event_in_overview($ev, $context, (int)$USER->id);
    if ($canviewevent) {
        $templatecontext['events'][] = $prepareeventrow(
            $ev,
            false,
            $currenttab === 'history' ? 'history' : 'myevents'
        );
    }
}

$templatecontext['hasevents'] = !empty($templatecontext['events']);
$templatecontext['eventcounttext'] = get_string('overview_count', 'mod_bookit', count($templatecontext['events']));

foreach ($openrequests as $ev) {
    $templatecontext['openrequests'][] = $prepareeventrow($ev, true, 'openrequests');
}

foreach ($acceptedrequests as $ev) {
    $templatecontext['acceptedrequests'][] = $prepareeventrow($ev, true, 'acceptedrequests');
}

foreach ($rejectedrequests as $ev) {
    $templatecontext['rejectedrequests'][] = $prepareeventrow($ev, true, 'rejectedrequests');
}

$templatecontext['hasopenrequests'] = !empty($templatecontext['openrequests']);
$templatecontext['hasacceptedrequests'] = !empty($templatecontext['acceptedrequests']);
$templatecontext['hasrejectedrequests'] = !empty($templatecontext['rejectedrequests']);
$templatecontext['requestqueuecounttext'] = match ($requestworkspacemode) {
    'rejectedrequests' => get_string('overview_rejected_request_count', 'mod_bookit', $rejectedrequestcount),
    'acceptedrequests' => get_string('overview_accepted_request_count', 'mod_bookit', $acceptedrequestcount),
    default => get_string('overview_open_request_count', 'mod_bookit', $openrequestcount),
};

// Render Mustache.
echo $OUTPUT->render_from_template('mod_bookit/view/examiner_overview', $templatecontext);
echo $OUTPUT->footer();
