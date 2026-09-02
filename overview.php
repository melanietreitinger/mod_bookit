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
use mod_bookit\local\table\request_workspace_table;
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
$canmanage = has_capability('mod/bookit:managebasics', $context);
$canmanageopenrequests = event_access_manager::can_manage_open_requests($context);
$canviewrequestworkspace = event_access_manager::can_view_request_workspace($context);
$defaulttab = tabs::get_default_overview_tab($canviewrequestworkspace);
$tab = optional_param('tab', $defaulttab, PARAM_ALPHANUMEXT);
if ($canviewrequestworkspace) {
    $workspacetab = tabs::validate_overview_tab($tab, true);
    $currenttab = $workspacetab;
} else {
    $workspacetab = null;
    $currenttab = $isobserverrestricted
        ? 'myevents'
        : tabs::validate_overview_tab($tab, false);
}
$isqueuetab = $canviewrequestworkspace && in_array($workspacetab, ['openrequests', 'confirmedrequests', 'rejectedcancelled'], true);
$isreportingworkspacetab = $canviewrequestworkspace && in_array($workspacetab, ['allrequests', 'history'], true);
$isinrequestworkspace = $isqueuetab;
$requestworkspacemode = $isqueuetab ? $workspacetab : 'openrequests';
$activetabparam = $canviewrequestworkspace ? $workspacetab : ($currenttab === 'history' ? 'history' : 'myevents');
$historyactive = $activetabparam === 'history';
$filterprofile = $canviewrequestworkspace
    ? event_manager::get_workspace_filter_profile($workspacetab, true)
    : event_manager::get_workspace_filter_profile('myevents', false);
$tableprofile = $canviewrequestworkspace
    ? event_manager::get_workspace_table_profile($workspacetab)
    : [];
$showreportfilters = $canviewrequestworkspace || $isobserverrestricted;
$checklistenabled = event_access_manager::is_checklist_enabled();
$resourcesenabled = event_access_manager::is_resources_enabled();
$hasexplicitstatusfilter = array_key_exists('bookingstatusfilter', $_GET);
$rawstatusfilter = optional_param_array('bookingstatusfilter', [], PARAM_INT);
$selectedfacultyid = optional_param('facultyid', 0, PARAM_INT);
$selectedsemesterids = optional_param_array('semesterids', [], PARAM_INT);
$search = optional_param('search', '', PARAM_RAW_TRIMMED);
$isrejectedcancelledtab = $canviewrequestworkspace && $workspacetab === 'rejectedcancelled';
$selectedstatuses = event_manager::resolve_overview_booking_status_filter_ids(
    $rawstatusfilter,
    $hasexplicitstatusfilter,
    $historyactive,
    $historyactive && $canviewrequestworkspace,
    $isrejectedcancelledtab
);
$tableid = $canviewrequestworkspace
    ? (string)($tableprofile['tableid'] ?? 'overview-table')
    : match (true) {
        $historyactive => 'overview-table',
        default => 'overview-table',
    };

[$defaultreportstart, $defaultreportend] = event_manager::get_reporting_default_range(
    null,
    $showreportfilters && $canviewrequestworkspace,
    $historyactive,
    $canviewrequestworkspace && $workspacetab === 'rejectedcancelled'
);
$defaultreportstartvalue = date('Y-m-d', $defaultreportstart);
$defaultreportendvalue = date('Y-m-d', $defaultreportend);
$hasexplicitreportstart = array_key_exists('reportstart', $_GET);
$hasexplicitreportend = array_key_exists('reportend', $_GET);
$reportstartvalue = optional_param('reportstart', $defaultreportstartvalue, PARAM_TEXT);
$reportendvalue = optional_param('reportend', $defaultreportendvalue, PARAM_TEXT);
$hasexplicitsemesterfilter = array_key_exists('semesterids', $_GET);
$assignmentfilter = 'all';
if (!empty($filterprofile['show_assignment_filter'])) {
    $assignmentfilter = optional_param('assignmentfilter', 'all', PARAM_ALPHA);
    if (!in_array($assignmentfilter, ['all', 'assigned'], true)) {
        $assignmentfilter = 'all';
    }
}
if ($showreportfilters) {
    $selectedsemesterids = event_manager::resolve_effective_semester_filter_ids(
        $selectedsemesterids,
        $hasexplicitsemesterfilter
    );

    $semesterrange = event_manager::get_semester_date_range($selectedsemesterids);

    if ($semesterrange !== null) {
        if (!$hasexplicitreportstart) {
            $defaultreportstart = $semesterrange[0];
            $reportstartvalue = date('Y-m-d', $defaultreportstart);
        }

        if (!$hasexplicitreportend) {
            $defaultreportend = $semesterrange[1];
            $reportendvalue = date('Y-m-d', $defaultreportend);
        }
    }
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
if ($hasexplicitreportstart) {
    $overviewnavigationparams['reportstart'] = $reportstartvalue;
}
if ($hasexplicitreportend) {
    $overviewnavigationparams['reportend'] = $reportendvalue;
}
if (!empty($filterprofile['show_assignment_filter']) && array_key_exists('assignmentfilter', $_GET)) {
    $overviewnavigationparams['assignmentfilter'] = $assignmentfilter;
}
if ($search !== '') {
    $overviewnavigationparams['search'] = $search;
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
}

$buildoverviewurl = static function (string $targettab) use ($cm, $overviewnavigationparams): string {
    return tabs::build_overview_url((int)$cm->id, $targettab, $overviewnavigationparams);
};

$overviewtabrow = !$canviewrequestworkspace
    ? tabs::get_overview_inner_tabrow(
        (int)$cm->id,
        $overviewnavigationparams,
        !$isobserverrestricted,
        false
    )
    : [];
$requestworkspacetabrow = $canviewrequestworkspace
    ? tabs::get_request_workspace_tabrow((int)$cm->id, [])
    : [];

/* =======================================================================
   1.  Front-end requirements
   ======================================================================= */
$PAGE->requires->css(new moodle_url('/mod/bookit/styles.css'));
$PAGE->requires->jquery();

if (!$canviewrequestworkspace) {
    // Store the selected sort order as a Moodle user preference.
    $sortpreferencekey = 'mod_bookit_overview_sort';
    $sortpreference = json_decode(
        (string)get_user_preferences($sortpreferencekey, ''),
        true
    );

    $allowedsortcolumns = [
        'starttime',
        'title',
        'room',
        'personincharge',
        'myrole',
    ];

    // Fall back to the default: newest bookings first.
    if (
        !is_array($sortpreference)
        || !in_array($sortpreference['column'] ?? '', $allowedsortcolumns, true)
        || !in_array($sortpreference['direction'] ?? '', ['asc', 'desc'], true)
    ) {
        $sortpreference = [
            'column' => 'starttime',
            'direction' => 'desc',
        ];
    }

    $initialsortcolumn = $sortpreference['column'];
    $initialsortdirection = $sortpreference['direction'];

  // Initialise sorting with the saved or default user preference.
$PAGE->requires->js_call_amd(
    'mod_bookit/overview/my_booked_events_sort',
    'init',
    [
        $tableid,
        $sortpreferencekey,
        $initialsortcolumn,
        $initialsortdirection,
    ]
);
}

/* ----- inline ModalForm handler -------------------------------------- */
$PAGE->requires->js_call_amd('mod_bookit/event_details_modal', 'init');
$PAGE->requires->js_call_amd('mod_bookit/overview/booking_status_dropdown', 'init');
$PAGE->requires->js_call_amd('mod_bookit/semester_date_sync', 'init');

if (!$isobserverrestricted) {
    $calendarreadconfig = [
        'methodname' => 'mod_bookit_get_calendar_events',
        'cmid' => (int)$cm->id,
    ];
    $PAGE->requires->js_call_amd('mod_bookit/export_modal', 'init', [$calendarreadconfig]);
}
/* =======================================================================
   2.  Page headings
   ======================================================================= */
$PAGE->set_url('/mod/bookit/overview.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('overview', 'mod_bookit'));
$PAGE->set_heading($course->fullname);
if ($canviewrequestworkspace) {
    $PAGE->set_secondary_active_tab('bookitrequestworkspace');
} else {
    $PAGE->set_secondary_active_tab('bookitoverview');
}

/* =======================================================================
   3.  Fetch examiner’s events
   ======================================================================= */
if ($canviewrequestworkspace) {
    $overviewfilters['assignmentfilter'] = $assignmentfilter;
    $overviewfilters['search'] = $search;
    $workspacequery = event_manager::get_request_workspace_query(
        $context,
        (int)$USER->id,
        (string)$workspacetab,
        $overviewfilters,
        $reportstarttimestamp,
        $reportendtimestamp
    );
    $workspacecount = (int)$DB->count_records_sql($workspacequery['countsql'], $workspacequery['countparams']);
    $workspacebaseurl = new moodle_url(tabs::build_overview_url(
        (int)$cm->id,
        (string)$workspacetab,
        $overviewnavigationparams
    ));
    $workspacetable = new request_workspace_table(
        $context,
        (int)$USER->id,
        (int)$cm->id,
        (string)$workspacetab,
        array_merge($tableprofile, [
            'showprogresscolumn' => !empty($tableprofile['showprogresscolumn'])
                && ($checklistenabled || $resourcesenabled),
            'showchecklistcolumn' => !empty($tableprofile['showchecklistcolumn']) && $checklistenabled,
            'showresourcescolumn' => !empty($tableprofile['showresourcescolumn']) && $resourcesenabled,
        ]),
        $workspacequery,
        $workspacebaseurl
    );
    $events = [];
} else if ($showreportfilters) {
    $overviewfilters['assignmentfilter'] = $assignmentfilter;
    $events = event_manager::get_events_for_reporting(
        $context,
        (int)$USER->id,
        $reportstarttimestamp,
        $reportendtimestamp,
        $selectedsemesterids
    );
    $events = event_manager::filter_overview_events(
        $events,
        $overviewfilters,
        $currenttab === 'history'
    );
    $events = event_manager::filter_events_by_assignment($events, (int)$USER->id, $assignmentfilter);
} else {
    $overviewfilters['assignmentfilter'] = $assignmentfilter;
    $events = event_manager::get_events_for_examiner($USER->id);
    $events = event_manager::filter_overview_events(
        $events,
        $overviewfilters,
        $currenttab === 'history'
    );
    $events = event_manager::filter_events_by_assignment($events, (int)$USER->id, $assignmentfilter);
}
$openrequests = [];
$rejectedcancelledqueue = [];
$confirmedrequests = [];
$semesteroptionssource = event_manager::get_semester_filter_options();
$semesteroptions = [];
foreach ($semesteroptionssource as $value => $label) {
    $semesteroptions[] = [
        'value' => (string)$value,
        'label' => $label,
        'selected' => in_array((int)$value, $selectedsemesterids, true),
    ];
}
$assignmentfilteroptions = [
    [
        'value' => 'all',
        'label' => get_string('overview_filter_assignment_all', 'mod_bookit'),
        'selected' => $assignmentfilter === 'all',
    ],
    [
        'value' => 'assigned',
        'label' => get_string('overview_filter_assignment_assigned', 'mod_bookit'),
        'selected' => $assignmentfilter === 'assigned',
    ],
];
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
$statusoptionids = $isrejectedcancelledtab
    ? [
        event_access_manager::BOOKINGSTATUS_CANCELED,
        event_access_manager::BOOKINGSTATUS_REJECTED,
    ]
    : [0, 1, 2, 3, 4];
foreach ($statusoptionids as $statusvalue) {
    $statusfilteroptions[] = [
        'value' => (string)$statusvalue,
        'label' => event_manager::get_booking_status_label($statusvalue),
        'selected' => in_array($statusvalue, $selectedstatuses, true),
    ];
}

// Fetch master checklist ID directly (no entity = no JS side effects).
$masterrecord = $DB->get_record('bookit_checklist_master', ['isdefault' => 1], 'id', IGNORE_MULTIPLE);
$masterid = $masterrecord ? (int)$masterrecord->id : 0;

echo $OUTPUT->header();

$coretablehtml = '';
if ($canviewrequestworkspace) {
    ob_start();
    $workspacetable->out(25, false);
    $coretablehtml = (string)ob_get_clean();
}

/* =======================================================================
   4+5.  Render via Mustache template (no HTML in PHP)
   ======================================================================= */

$templatecontext = [
    'cmid' => $cm->id,
    'tableid' => (string)$tableid,
    'canmanage' => $canmanage,
    'showidcolumn' => $canviewrequestworkspace
        ? !empty($tableprofile['showidcolumn'])
        : false,
    'showworkspacetable' => $canviewrequestworkspace,
    'workspacetableprofile' => array_merge($tableprofile, [
        'showprogresscolumn' => !empty($tableprofile['showprogresscolumn'])
            && ($checklistenabled || $resourcesenabled),
        'showchecklistcolumn' => !empty($tableprofile['showchecklistcolumn']) && $checklistenabled,
        'showresourcescolumn' => !empty($tableprofile['showresourcescolumn']) && $resourcesenabled,
    ]),
    'coretablehtml' => $coretablehtml,
    'showmyeventssection' => !$canviewrequestworkspace,
    'showexportevents' => !$isobserverrestricted
        && ($canviewrequestworkspace || $currenttab === 'myevents'),
    'showhistorytab' => !$isobserverrestricted,
    'showoverviewnavigation' => !$canviewrequestworkspace && count($overviewtabrow) > 1,
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
    'showrequestworkspacesection' => false,
    'showrequestworkspacetabs' => $canviewrequestworkspace && count($requestworkspacetabrow) > 1,
    'requestworkspacetabtree' => $canviewrequestworkspace && count($requestworkspacetabrow) > 1
        ? html_writer::tag(
            'nav',
            $OUTPUT->tabtree($requestworkspacetabrow, $workspacetab),
            [
                'class' => 'mod-bookit-request-workspace-tabs',
                'aria-label' => get_string('overview_request_workspace_switch', 'mod_bookit'),
            ]
        )
        : '',
    'showopenrequestworkspace' => false,
    'showconfirmedrequestworkspace' => false,
    'showrejectedcancelledworkspace' => false,
    'requestworkspacetitle' => get_string('overview_request_workspace', 'mod_bookit'),
    'activetabparam' => $activetabparam,
    'historyactive' => $historyactive,
    'sectiontitle' => match (true) {
        $canviewrequestworkspace && $workspacetab === 'allrequests' => get_string('overview_all_requests', 'mod_bookit'),
        $historyactive => get_string('overview_history', 'mod_bookit'),
        $showreportfilters => get_string('overview_all_events', 'mod_bookit'),
        default => get_string('overview_my_events', 'mod_bookit'),
    },
    'openrequestsempty' => get_string('overview_open_requests_empty', 'mod_bookit'),
    'rejectedcancelledempty' => get_string('overview_rejected_requests_empty', 'mod_bookit'),
    'confirmedrequestsempty' => get_string('overview_confirmed_requests_empty', 'mod_bookit'),
    'searchvalue' => $search,
    'searchaction' => (new moodle_url('/mod/bookit/overview.php'))->out(false),
    'serversearch' => $canviewrequestworkspace,
    'showoverviewfilters' => !$canviewrequestworkspace || !empty($filterprofile['show_reporting_filters']),
    'showstatusfilter' => !$canviewrequestworkspace || !empty($filterprofile['show_status_filter']),
    'showsemesterfilter' => !$canviewrequestworkspace || !empty($filterprofile['show_semester_filter']),
    'showassignmentfilter' => !empty($filterprofile['show_assignment_filter']),
    'showreportfilters' => $showreportfilters
        && (!$canviewrequestworkspace || !empty($filterprofile['show_reporting_filters'])),
    'showcreatedbycolumn' => $canviewrequestworkspace
        ? !empty($tableprofile['showcreatedbycolumn'])
        : ($showreportfilters && $isreportingworkspacetab),
    'createdbycolumnlabel' => get_string('event_usercreated', 'mod_bookit'),
    'showprogresscolumn' => $canviewrequestworkspace
        ? (!empty($tableprofile['showprogresscolumn']) && ($checklistenabled || $resourcesenabled))
        : ($checklistenabled || $resourcesenabled),
    'showchecklistcolumn' => $canviewrequestworkspace
        ? (!empty($tableprofile['showchecklistcolumn']) && $checklistenabled)
        : $checklistenabled,
    'showresourcescolumn' => $canviewrequestworkspace
        ? (!empty($tableprofile['showresourcescolumn']) && $resourcesenabled)
        : $resourcesenabled,
    'showcancelcolumn' => !$canviewrequestworkspace
        && !$isobserverrestricted
        && $currenttab === 'myevents',
    'showreactivatecolumn' => $canmanageopenrequests
        ? !empty($tableprofile['showreactivatecolumn'])
        : false,
    'overviewcancelcolumnlabel' => get_string('overview_cancel_column', 'mod_bookit'),
    'overviewreactivatecolumnlabel' => get_string('overview_reactivate_column', 'mod_bookit'),
    'overviewcolumndatetime' => get_string('overview_column_datetime', 'mod_bookit'),
    'overviewcolumnid' => get_string('overview_column_id', 'mod_bookit'),
    'overviewcolumntitle' => get_string('overview_column_title', 'mod_bookit'),
    'overviewcolumnroom' => get_string('overview_column_room', 'mod_bookit'),
    'overviewcolumnpersonincharge' => get_string('overview_column_personincharge', 'mod_bookit'),
    'overviewcolumnmyrole' => get_string('overview_column_myrole', 'mod_bookit'),
    'overviewcolumnbookingstatus' => get_string('overview_column_bookingstatus', 'mod_bookit'),
    'overviewcolumnprogress' => get_string('overview_column_progress', 'mod_bookit'),
    'overviewcolumnchecklist' => get_string('overview_column_checklist', 'mod_bookit'),
    'overviewcolumnresources' => get_string('overview_column_resources', 'mod_bookit'),
    'overviewcolumndate' => get_string('overview_column_date', 'mod_bookit'),
    'reportstartvalue' => $reportstartvalue,
    'reportendvalue' => $reportendvalue,
    'reportstartlabel' => get_string('overview_filter_startdate', 'mod_bookit'),
    'reportendlabel' => get_string('overview_filter_enddate', 'mod_bookit'),
    'statusfilterlabel' => get_string('overview_filter_status', 'mod_bookit'),
    'facultyfilterlabel' => get_string('overview_filter_faculty', 'mod_bookit'),
    'semesterfilterlabel' => get_string('select_semester', 'mod_bookit'),
    'assignmentfilterlabel' => get_string('overview_filter_assignment', 'mod_bookit'),
    'assignmentfilteroptions' => $assignmentfilteroptions,
    'reportapplylabel' => get_string('overview_apply_filters', 'mod_bookit'),
    'reportresetlabel' => get_string('overview_reset_filters', 'mod_bookit'),
    'reportreseturl' => (new moodle_url('/mod/bookit/overview.php', ['id' => $cm->id, 'tab' => $activetabparam]))->out(false),
    'facultyoptions' => $facultyoptions,
    'statusfilteroptions' => $statusfilteroptions,
    'semesteroptions' => $semesteroptions,
    'hasevents' => false,
    'eventcounttext' => get_string(
        'overview_count',
        'mod_bookit',
        $canviewrequestworkspace ? $workspacecount : 0
    ),
    'noeventsmessage' => $isobserverrestricted
        ? get_string('observer_empty_state', 'mod_bookit')
        : get_string('overview_no_results', 'mod_bookit'),
    'hasopenrequests' => false,
    'hasconfirmedrequests' => false,
    'hasrejectedcancelled' => false,
    'events' => [],
    'openrequests' => [],
    'confirmedrequests' => [],
    'rejectedcancelledqueue' => [],
];

// Precompute checklist progress for displayed events in a single query.
$displayevents = $canviewrequestworkspace ? [] : $events;
$progressmap = [];
$resourceprogressmap = [];
if (!empty($displayevents)) {
    $eventids = array_map(static fn($ev): int => (int)$ev->id, $displayevents);
    if ($checklistenabled && $masterid > 0) {
        $progressmap = event_checklist_state_manager::get_progress_percent_for_events($eventids, $masterid);
    }
    if ($resourcesenabled) {
        $resourceprogressmap = event_resource_manager::get_resource_progress_for_events($eventids);
    }
}
$historyeventids = array_map(static fn($ev): int => (int)$ev->id, $displayevents);
$latesthistorymap = event_manager::get_latest_booking_history_entries($historyeventids);

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
    $currenttab,
    $workspacetab,
    $canviewrequestworkspace,
    $canmanageopenrequests,
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
    $latesthistorysummary = event_manager::build_overview_workflow_latest_summary(
        $latesthistory,
        $ev,
        $context,
        (int)$USER->id
    );
    $historydetails = $isrequestworkspaceitem
        ? event_manager::build_overview_workflow_history($ev, $context, (int)$USER->id)
        : [];

    $createdby = '-';
    if (!empty($ev->usercreated)) {
        if (!empty($ev->creatordeleted)) {
            $createdby = get_string('deleteduser', 'moodle');
        } else if (!empty($ev->creatorfirstname) || !empty($ev->creatorlastname)) {
            $createdby = trim(($ev->creatorfirstname ?? '') . ' ' . ($ev->creatorlastname ?? ''));
        } else {
            $creator = core_user::get_user((int)$ev->usercreated);
            $createdby = $creator ? fullname($creator) : (string)$ev->usercreated;
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
        'checklistprogress_available' => $checklistenabled && $masterid > 0 && $canviewchecklist,
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
        'resourcesprogress_available' => $resourcesenabled && $canviewresources
            && (($resourceprogressmap[(int)$ev->id]['total'] ?? 0) > 0),
        'hascancelaction' => !$isrequestworkspaceitem
            && !$canmanage
            && !$isreservedprojection
            && event_access_manager::can_participant_overview_cancel($ev, $context, (int)$USER->id),
        'cancelactionlabel' => get_string('overview_cancel_booking', 'mod_bookit'),
        'canceltargetstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
        'hasreactivateaction' => $canmanageopenrequests && (
            ($requesttab === 'history'
                && event_access_manager::can_reactivate_from_history($ev, $context))
            || ($requesttab === 'rejectedcancelled'
                && event_access_manager::can_restore_terminal_request($ev, $context))
        ),
        'reactivateactionlabel' => get_string('bookingstatus_action_reactivate', 'mod_bookit'),
        'reactivatetargetstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        'overviewtab' => $isrequestworkspaceitem
            ? ($canviewrequestworkspace && $workspacetab !== null
                ? $workspacetab
                : $requesttab)
            : ($currenttab === 'history' ? 'history' : 'myevents'),
    ];
};

$workspacerequesttab = $canviewrequestworkspace
    ? $workspacetab
    : 'openrequests';

if (!$canviewrequestworkspace) {
    foreach ($displayevents as $ev) {
        $canviewevent = $historyactive
            ? event_access_manager::can_user_view_event_in_history($ev, $context, (int)$USER->id)
            : event_access_manager::can_user_view_event_in_overview($ev, $context, (int)$USER->id);
        if ($canviewevent) {
            $templatecontext['events'][] = $prepareeventrow(
                $ev,
                false,
                $historyactive ? 'history' : 'myevents'
            );
        }
    }
    $templatecontext['hasevents'] = !empty($templatecontext['events']);
    $templatecontext['eventcounttext'] = get_string('overview_count', 'mod_bookit', count($templatecontext['events']));
}

// Render Mustache.
echo $OUTPUT->render_from_template('mod_bookit/view/examiner_overview', $templatecontext);
echo $OUTPUT->footer();
