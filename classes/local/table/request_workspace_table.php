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

namespace mod_bookit\local\table;

use context_module;
use core_table\sql_table;
use html_writer;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_checklist_state_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\event_resource_manager;
use mod_bookit\output\booking_status_cell;
use moodle_url;
use stdClass;

/**
 * Moodle Core SQL table for the governed request workspace.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class request_workspace_table extends sql_table {
    /** Shared Core preference identity across all workspace tabs. */
    public const UNIQUE_ID = 'mod-bookit-request-workspace';

    /** @var context_module */
    private context_module $context;

    /** @var int */
    private int $userid;

    /** @var int */
    private int $cmid;

    /** @var string */
    private string $workspace;

    /** @var array */
    private array $profile;

    /** @var string[] */
    private array $availablecolumns = [];

    /**
     * Constructor.
     *
     * @param context_module $context
     * @param int $userid
     * @param int $cmid
     * @param string $workspace
     * @param array $profile
     * @param array $query
     * @param moodle_url $baseurl
     */
    public function __construct(
        context_module $context,
        int $userid,
        int $cmid,
        string $workspace,
        array $profile,
        array $query,
        moodle_url $baseurl
    ) {
        parent::__construct(self::UNIQUE_ID);
        $this->context = $context;
        $this->userid = $userid;
        $this->cmid = $cmid;
        $this->workspace = $workspace;
        $this->profile = $profile;

        $columns = ['id'];
        $headers = [get_string('overview_column_id', 'mod_bookit')];
        if (!empty($profile['showdatetimecolumn'])) {
            $columns[] = 'starttime';
            $headers[] = get_string('overview_column_datetime', 'mod_bookit');
        }
        $columns = array_merge($columns, ['title', 'room', 'personincharge', 'myrole']);
        $headers = array_merge($headers, [
            get_string('overview_column_title', 'mod_bookit'),
            get_string('overview_column_room', 'mod_bookit'),
            get_string('overview_column_personincharge', 'mod_bookit'),
            get_string('overview_column_myrole', 'mod_bookit'),
        ]);
        if (!empty($profile['showcreatedbycolumn'])) {
            $columns[] = 'createdby';
            $headers[] = get_string('event_usercreated', 'mod_bookit');
        }
        $columns[] = 'bookingstatus';
        $headers[] = get_string('overview_column_bookingstatus', 'mod_bookit');
        if (!empty($profile['showprogresscolumn'])) {
            $columns[] = 'progress';
            $headers[] = get_string('overview_column_progress', 'mod_bookit');
        }
        if (!empty($profile['showchecklistcolumn'])) {
            $columns[] = 'checklist';
            $headers[] = get_string('overview_column_checklist', 'mod_bookit');
        }
        if (!empty($profile['showresourcescolumn'])) {
            $columns[] = 'resources';
            $headers[] = get_string('overview_column_resources', 'mod_bookit');
        }
        if (!empty($profile['showreactivatecolumn'])) {
            $columns[] = 'reactivate';
            $headers[] = get_string('overview_reactivate_column', 'mod_bookit');
        }
        if (!empty($profile['showdatecolumn'])) {
            $columns[] = 'starttime';
            $headers[] = get_string('overview_column_date', 'mod_bookit');
        }

        $this->define_columns($columns);
        $this->availablecolumns = $columns;
        $this->define_headers($headers);
        foreach (['myrole', 'progress', 'checklist', 'resources', 'reactivate'] as $column) {
            $this->no_sorting($column);
        }
        $this->sortable(true, 'starttime', SORT_DESC);
        $this->collapsible(false);
        $this->is_persistent(true);
        $this->define_baseurl($baseurl);
        $this->set_attribute('id', (string)($profile['tableid'] ?? 'overview-table'));
        $classes = 'generaltable table-striped table-hover w-100';
        if (!empty($profile['tablecssclass'])) {
            $classes .= ' ' . $profile['tablecssclass'];
        }
        $this->set_attribute('class', $classes);
        $this->set_sql($query['fields'], $query['from'], $query['where'], $query['params']);
        $this->set_count_sql($query['countsql'], $query['countparams']);
    }

    /**
     * Replace an unavailable shared Core preference with the table default.
     */
    public function setup() {
        $requestedsort = optional_param('tsort', '', PARAM_ALPHANUMEXT);
        $persistedsort = self::get_sort_for_table(self::UNIQUE_ID);
        $persistedcolumn = '';
        if (preg_match('/^([a-z][a-z0-9_]*)/i', $persistedsort, $matches)) {
            $persistedcolumn = $matches[1];
        }
        if (
            ($requestedsort !== '' && !in_array($requestedsort, $this->availablecolumns, true))
            || ($requestedsort === '' && $persistedcolumn !== ''
                && !in_array($persistedcolumn, $this->availablecolumns, true))
        ) {
            $this->set_sortdata([[
                'sortby' => 'starttime',
                'sortorder' => SORT_DESC,
            ]]);
        }
        parent::setup();
    }

    /**
     * Return one deterministic primary sort plus the event-id tie-breaker.
     *
     * Core still owns validation, links, direction, and persistence; this override only guarantees
     * stable paging for every valid primary column.
     *
     * @return string
     */
    public function get_sql_sort(): string {
        $sortcolumns = $this->get_sort_columns();
        if (!$sortcolumns) {
            return 'starttime DESC, id DESC';
        }

        $column = (string)array_key_first($sortcolumns);
        $direction = reset($sortcolumns) === SORT_DESC ? 'DESC' : 'ASC';
        $sort = $column . ' ' . $direction;
        if ($column !== 'id') {
            $sort .= ', id DESC';
        }
        return $sort;
    }

    /**
     * Render the workspace-specific empty message through the Core table path exactly once.
     */
    public function print_nothing_to_display(): void {
        global $OUTPUT;

        echo $this->get_dynamic_table_html_start();
        echo $this->render_reset_button();
        $this->print_initials_bar();
        echo $OUTPUT->notification(
            get_string((string)$this->profile['emptymessage'], 'mod_bookit'),
            'info',
            false
        );
        echo $this->get_dynamic_table_html_end();
    }

    /**
     * Render title.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_title($row): string {
        $title = format_string((string)$row->title);
        if (!event_access_manager::can_user_view_event_details($row, $this->context, $this->userid)) {
            return $title;
        }
        return html_writer::link('#', $title, [
            'class' => 'bookit-event-link',
            'data-eventid' => (int)$row->id,
            'data-cmid' => $this->cmid,
            'data-modal-footer-mode' => event_access_manager::get_event_modal_footer_mode(
                $row,
                $this->context,
                $this->userid
            ),
        ]);
    }

    /**
     * Render start time according to the active profile.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_starttime($row): string {
        $format = !empty($this->profile['showdatetimecolumn'])
            ? get_string('strftimedatetime', 'langconfig')
            : '%d.%m.%Y';
        return userdate((int)$row->starttime, $format);
    }

    /**
     * Render room.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_room($row): string {
        return s(trim((string)$row->room) !== '' ? (string)$row->room : '-');
    }

    /**
     * Render person in charge.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_personincharge($row): string {
        return s(trim((string)$row->personincharge) !== '' ? (string)$row->personincharge : '-');
    }

    /**
     * Render viewer roles.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_myrole($row): string {
        $labels = [];
        foreach (event_access_manager::get_user_roles_for_event($row, $this->userid) as $role) {
            $stringkey = match ($role) {
                'personincharge' => 'overview_role_personincharge',
                'bookingperson' => 'overview_role_bookingperson',
                'otherexaminer' => 'overview_role_otherexaminer',
                'supportperson' => 'overview_role_supportperson',
                default => null,
            };
            if ($stringkey !== null) {
                $labels[] = get_string($stringkey, 'mod_bookit');
            }
        }
        return s($labels ? implode(', ', $labels) : '-');
    }

    /**
     * Render creator.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_createdby($row): string {
        if (!empty($row->creatordeleted)) {
            return get_string('deleteduser', 'moodle');
        }
        return s(trim((string)$row->createdby) !== '' ? (string)$row->createdby : '-');
    }

    /**
     * Render status inner content inside the td owned by Core Table.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_bookingstatus($row): string {
        global $PAGE;

        $latest = event_manager::get_latest_booking_history_entries([(int)$row->id]);
        $statuscell = booking_status_cell::for_booking_overview_row(
            $row,
            $this->context,
            $this->userid,
            $this->cmid,
            has_capability('mod/bookit:managebasics', $this->context, $this->userid),
            true,
            $this->workspace,
            event_manager::build_overview_workflow_latest_summary(
                $latest[(int)$row->id] ?? null,
                $row,
                $this->context,
                $this->userid
            ),
            event_manager::build_overview_workflow_history($row, $this->context, $this->userid)
        );
        $renderer = $PAGE->get_renderer('mod_bookit');
        $data = $statuscell->export_for_template($renderer);
        return $renderer->render_from_template('mod_bookit/components/booking_status_cell_inner', $data);
    }

    /**
     * Render combined progress.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_progress($row): string {
        global $DB;

        $parts = [];
        $master = $DB->get_record('bookit_checklist_master', ['isdefault' => 1], 'id', IGNORE_MULTIPLE);
        if ($master && event_access_manager::can_view_event_checklist($row, $this->context, $this->userid)) {
            $progress = event_checklist_state_manager::get_progress_percent_for_events([(int)$row->id], (int)$master->id);
            $parts[] = get_string('overview_column_checklist', 'mod_bookit') . ': ' . ($progress[(int)$row->id] ?? 0) . '%';
        }
        if (event_access_manager::can_view_event_resources($row, $this->context, $this->userid)) {
            $progress = event_resource_manager::get_resource_progress_for_events([(int)$row->id]);
            if (($progress[(int)$row->id]['total'] ?? 0) > 0) {
                $parts[] = get_string('overview_column_resources', 'mod_bookit') . ': '
                    . ($progress[(int)$row->id]['percent'] ?? 0) . '%';
            }
        }
        return $parts ? implode(html_writer::empty_tag('br'), array_map('s', $parts)) : '-';
    }

    /**
     * Render checklist action.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_checklist($row): string {
        if (!event_access_manager::can_view_event_checklist($row, $this->context, $this->userid)) {
            return '-';
        }
        return html_writer::link(new moodle_url('/mod/bookit/view/event_checklist_view.php', [
            'id' => $this->cmid,
            'eventid' => (int)$row->id,
        ]), get_string('checklist', 'mod_bookit'), ['class' => 'btn btn-sm btn-primary']);
    }

    /**
     * Render resources action.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_resources($row): string {
        if (!event_access_manager::can_view_event_resources($row, $this->context, $this->userid)) {
            return '-';
        }
        return html_writer::link(new moodle_url('/mod/bookit/view/event_resources.php', [
            'id' => $this->cmid,
            'eventid' => (int)$row->id,
        ]), get_string('resources', 'mod_bookit'), ['class' => 'btn btn-sm btn-primary']);
    }

    /**
     * Render reactivation action.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_reactivate($row): string {
        $allowed = event_access_manager::can_manage_open_requests($this->context)
            && (($this->workspace === 'history'
                    && event_access_manager::can_reactivate_from_history($row, $this->context))
                || ($this->workspace === 'rejectedcancelled'
                    && event_access_manager::can_restore_terminal_request($row, $this->context)));
        if (!$allowed) {
            return '-';
        }
        return html_writer::tag('button', get_string('bookingstatus_action_reactivate', 'mod_bookit'), [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-primary',
            'data-action' => 'reactivate-booking-from-overview',
            'data-eventid' => (int)$row->id,
            'data-cmid' => $this->cmid,
            'data-status' => event_access_manager::BOOKINGSTATUS_NEW,
            'data-tab' => $this->workspace,
        ]);
    }

    /**
     * Add row status classes.
     *
     * @param stdClass $row
     * @return string
     */
    public function get_row_class($row): string {
        $classes = 'mod-bookit-status-row mod-bookit-status-row-'
            . event_manager::get_booking_status_group_key((int)$row->bookingstatus);
        if (!empty($this->profile['rowcssclass'])) {
            $classes .= ' ' . $this->profile['rowcssclass'];
        }
        return $classes;
    }
}
