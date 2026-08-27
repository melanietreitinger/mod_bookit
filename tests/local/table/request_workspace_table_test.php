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

use advanced_testcase;
use context_module;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use moodle_url;

/**
 * Tests for the request workspace Core SQL table.
 *
 * @covers \mod_bookit\local\table\request_workspace_table
 * @covers \mod_bookit\local\manager\event_manager::get_request_workspace_query
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class request_workspace_table_test extends advanced_testcase {
    /**
     * Create table dependencies.
     *
     * @param string $workspace
     * @return array
     */
    private function create_table(string $workspace = 'openrequests'): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);
        $profile = event_manager::get_workspace_table_profile($workspace);
        $query = event_manager::get_request_workspace_query(
            $context,
            (int)get_admin()->id,
            $workspace,
            [],
            strtotime('-1 year'),
            strtotime('+1 year')
        );
        $table = new request_workspace_table(
            $context,
            (int)get_admin()->id,
            (int)$bookit->cmid,
            $workspace,
            $profile,
            $query,
            new moodle_url('/mod/bookit/overview.php', ['id' => $bookit->cmid, 'tab' => $workspace])
        );
        return [$table, $context, $bookit];
    }

    /**
     * The Core table defaults to newest event with deterministic ID ordering.
     */
    public function test_default_sort_is_starttime_desc_then_id_desc(): void {
        $this->resetAfterTest(true);
        [$table] = $this->create_table();
        $table->setup();

        $this->assertSame('starttime DESC, id DESC', $table->get_sql_sort());
    }

    /**
     * Every globally sortable display column maps to a SQL alias.
     *
     * @dataProvider sortable_column_provider
     * @param string $column
     * @param string $expectedsql
     */
    public function test_global_sort_columns_use_sql_aliases(string $column, string $expectedsql): void {
        $this->resetAfterTest(true);
        [$table] = $this->create_table('allrequests');
        $table->set_sortdata([['sortby' => $column, 'sortorder' => SORT_ASC]]);
        $table->setup();

        $this->assertSame($expectedsql, $table->get_sql_sort());
    }

    /**
     * Sortable display-column provider.
     *
     * @return array
     */
    public static function sortable_column_provider(): array {
        return [
            'id' => ['id', 'id ASC'],
            'date' => ['starttime', 'starttime ASC, id DESC'],
            'title' => ['title', 'title ASC, id DESC'],
            'room' => ['room', 'room ASC, id DESC'],
            'person' => ['personincharge', 'personincharge ASC, id DESC'],
            'creator' => ['createdby', 'createdby ASC, id DESC'],
            'status' => ['bookingstatus', 'bookingstatus ASC, id DESC'],
        ];
    }

    /**
     * Every primary sort uses id descending as its deterministic tie-breaker.
     */
    public function test_user_selected_sort_has_id_desc_tie_breaker(): void {
        $this->resetAfterTest(true);
        [$table] = $this->create_table();
        $table->set_sortdata([['sortby' => 'title', 'sortorder' => SORT_ASC]]);
        $table->setup();

        $this->assertSame('title ASC, id DESC', $table->get_sql_sort());
        $this->assertTrue($table->is_persistent());
        $this->assertSame(request_workspace_table::UNIQUE_ID, 'mod-bookit-request-workspace');
    }

    /**
     * An unavailable shared preference deterministically falls back to the default.
     */
    public function test_unavailable_column_falls_back_to_default_sort(): void {
        $this->resetAfterTest(true);
        [$table] = $this->create_table('openrequests');
        $table->set_sortdata([['sortby' => 'createdby', 'sortorder' => SORT_ASC]]);
        $table->setup();

        $this->assertSame('starttime DESC, id DESC', $table->get_sql_sort());
    }

    /**
     * The governed search is applied identically to count and data before paging.
     */
    public function test_search_is_part_of_data_and_count_scope(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$table, $context] = $this->create_table();
        unset($table);
        $adminid = (int)get_admin()->id;
        foreach (['Needle request', 'Other request'] as $name) {
            $DB->insert_record('bookit_event', (object)[
                'name' => $name,
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => strtotime('+2 days'),
                'endtime' => strtotime('+2 days +1 hour'),
                'duration' => 60,
                'roomid' => null,
                'participantsamount' => 1,

                'compensationfordisadvantages' => '',
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'personinchargeid' => $adminid,
                'otherexaminers' => '',
                'coursetemplate' => null,
                'notes' => '',
                'internalnotes' => '',
                'supportpersons' => '',
                'extratimebefore' => 0,
                'extratimeafter' => 0,
                'refcourseid' => null,
                'usercreated' => 0,
            'usermodified' => $adminid,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $query = event_manager::get_request_workspace_query(
            $context,
            $adminid,
            'openrequests',
            ['search' => 'Needle']
        );
        $count = $DB->count_records_sql($query['countsql'], $query['countparams']);
        $rows = $DB->get_records_sql(
            "SELECT {$query['fields']} FROM {$query['from']} WHERE {$query['where']} ORDER BY e.id DESC",
            $query['params']
        );

        $this->assertSame(1, $count);
        $this->assertCount(1, $rows);
        $this->assertSame('Needle request', reset($rows)->title);
    }

    /**
     * SQL sorting happens before two pages of 25 rows are selected.
     */
    public function test_global_title_sort_pages_fifty_records_without_gaps(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$table, $context] = $this->create_table('allrequests');
        unset($table);
        $adminid = (int)get_admin()->id;
        $start = strtotime('+2 days');
        for ($i = 50; $i >= 1; $i--) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Global title %02d', $i),
                'semester' => event_manager::get_current_semester($start),
                'institutionid' => 1,
                'starttime' => $start + ($i * HOURSECS),
                'endtime' => $start + (($i + 1) * HOURSECS),
                'duration' => 60,
                'roomid' => null,
                'participantsamount' => 1,

                'compensationfordisadvantages' => '',
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'personinchargeid' => $adminid,
                'otherexaminers' => '',
                'coursetemplate' => null,
                'notes' => '',
                'internalnotes' => '',
                'supportpersons' => '',
                'extratimebefore' => 0,
                'extratimeafter' => 0,
                'refcourseid' => null,
                'usercreated' => 0,
            'usermodified' => $adminid,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $query = event_manager::get_request_workspace_query(
            $context,
            $adminid,
            'allrequests',
            [],
            $start,
            $start + (60 * HOURSECS)
        );
        $sql = "SELECT {$query['fields']} FROM {$query['from']} WHERE {$query['where']}
                  ORDER BY e.name ASC, e.id DESC";
        $firstpage = array_values($DB->get_records_sql($sql, $query['params'], 0, 25));
        $secondpage = array_values($DB->get_records_sql($sql, $query['params'], 25, 25));
        $titles = array_map(static fn($row): string => $row->title, array_merge($firstpage, $secondpage));

        $this->assertCount(25, $firstpage);
        $this->assertCount(25, $secondpage);
        $this->assertSame(
            array_map(static fn(int $i): string => sprintf('Global title %02d', $i), range(1, 50)),
            $titles
        );
        $this->assertCount(50, array_unique(array_map(static fn($row): int => (int)$row->id, array_merge(
            $firstpage,
            $secondpage
        ))));
    }

    /**
     * Support keeps the institutional read-only scope across all workspace tabs.
     */
    public function test_support_query_scope_is_institutional_for_all_tabs(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);
        $support = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $roleid = \create_role('Bookit support', 'bookit_supportonsite', 'student');
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $support->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($support);

        $now = time();
        $records = [
            ['name' => 'Institutional open', 'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'starttime' => $now + DAYSECS, 'endtime' => $now + DAYSECS + HOURSECS],
            ['name' => 'Institutional confirmed', 'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'starttime' => $now + DAYSECS, 'endtime' => $now + DAYSECS + HOURSECS],
            ['name' => 'Institutional rejected', 'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'starttime' => $now - DAYSECS, 'endtime' => $now - DAYSECS + HOURSECS],
        ];
        foreach ($records as $record) {
            $DB->insert_record('bookit_event', (object)array_merge($record, [
                'semester' => event_manager::get_current_semester(),
                'institutionid' => 1,
                'duration' => 60,
                'roomid' => null,
                'participantsamount' => 1,

                'compensationfordisadvantages' => '',
                'personinchargeid' => $other->id,
                'otherexaminers' => '',
                'coursetemplate' => null,
                'notes' => '',
                'internalnotes' => '',
                'supportpersons' => '',
                'extratimebefore' => 0,
                'extratimeafter' => 0,
                'refcourseid' => null,
                'usercreated' => 0,
            'usermodified' => $other->id,
                'timecreated' => $now,
                'timemodified' => $now,
            ]));
        }

        foreach (['allrequests', 'openrequests', 'confirmedrequests', 'rejectedcancelled', 'history'] as $workspace) {
            $query = event_manager::get_request_workspace_query(
                $context,
                (int)$support->id,
                $workspace,
                [],
                $now - (2 * DAYSECS),
                $now + (2 * DAYSECS)
            );
            $this->assertGreaterThan(
                0,
                $DB->count_records_sql($query['countsql'], $query['countparams']),
                $workspace
            );
        }
    }

    /**
     * Status content is inner markup because Core owns the table cell.
     */
    public function test_status_renderer_does_not_return_nested_td(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$table] = $this->create_table();
        $adminid = (int)get_admin()->id;
        $id = $DB->insert_record('bookit_event', (object)[
            'name' => 'Status cell',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('+2 days'),
            'endtime' => strtotime('+2 days +1 hour'),
            'duration' => 60,
            'roomid' => null,
            'participantsamount' => 1,

            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $adminid,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usercreated' => 0,
            'usermodified' => $adminid,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $row = $DB->get_record('bookit_event', ['id' => $id], '*', MUST_EXIST);
        $html = $table->col_bookingstatus($row);

        $this->assertStringNotContainsString('<td', $html);
        $this->assertStringContainsString('mod-bookit-status', $html);
    }
}
