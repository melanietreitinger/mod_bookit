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

namespace mod_bookit\external;

use advanced_testcase;
use core_external\external_api;
use mod_bookit\local\manager\event_access_manager;

/**
 * External contract tests for the governed overview queue read.
 *
 * @covers \mod_bookit\external\get_overview_queue
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_overview_queue_test extends advanced_testcase {
    /**
     * Open-requests reads must expose the current queue and summary counters for service-team users.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_returns_open_request_queue_for_service_team(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Overview queue']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit service queue', 'bookitservicequeue', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($serviceuser);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Overview request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $serviceuser->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 0, '', '');

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertSame('openrequests', $response['workspace']);
        $this->assertCount(1, $response['items']);
        $this->assertSame($eventid, (int)$response['items'][0]['eventid']);
        $this->assertSame(1, (int)$response['summary']['openrequestcount']);
        $this->assertSame(1, (int)$response['summary']['count']);
        $this->assertSame(0, (int)$response['paging']['currentpage']);
        $this->assertFalse($response['paging']['adjusted']);
    }

    /**
     * Rejected-request reads must keep the queue summary aligned with the rejected workspace.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_returns_rejected_request_queue_for_service_team(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Rejected queue']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit rejected queue', 'bookitrejectedqueue', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($serviceuser);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-22 09:00:00'),
            'endtime' => strtotime('2026-05-22 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 9,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $serviceuser->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = get_overview_queue::execute($bookit->cmid, 'rejectedcancelled', [], [], [], 0, '', '');

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertSame('rejectedcancelled', $response['workspace']);
        $this->assertCount(1, $response['items']);
        $this->assertSame($eventid, (int)$response['items'][0]['eventid']);
        $this->assertSame(1, (int)$response['summary']['rejectedrequestcount']);
        $this->assertSame(1, (int)$response['summary']['count']);
        $this->assertSame(0, (int)$response['paging']['currentpage']);
        $this->assertFalse($response['paging']['adjusted']);
    }

    /**
     * Accepted-request reads must expose accepted bookings in the dedicated workspace.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_returns_accepted_request_queue_for_service_team(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Accepted queue']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit accepted queue', 'bookitacceptedqueue', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($serviceuser);

        $starttime = strtotime('+3 days 09:00');
        $endtime = strtotime('+3 days 11:00');
        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Accepted request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $serviceuser->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = get_overview_queue::execute($bookit->cmid, 'confirmedrequests', [], [], [], 0, '', '');

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertSame('confirmedrequests', $response['workspace']);
        $this->assertCount(1, $response['items']);
        $this->assertSame($eventid, (int)$response['items'][0]['eventid']);
        $this->assertSame(1, (int)$response['summary']['confirmedrequestcount']);
        $this->assertSame(1, (int)$response['summary']['count']);
    }

    /**
     * Open-requests reads must not include accepted bookings after acceptance.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_open_queue_excludes_accepted_bookings(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Open excludes accepted']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit open excludes accepted', 'bookitopenexcludesaccepted', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($serviceuser);

        $starttime = strtotime('+3 days 09:00');
        $endtime = strtotime('+3 days 11:00');
        $DB->insert_record('bookit_event', (object)[
            'name' => 'Accepted only',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $serviceuser->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 0, '', '');

        $this->assertSame('openrequests', $response['workspace']);
        $this->assertCount(0, $response['items']);
        $this->assertSame(0, (int)$response['summary']['openrequestcount']);
    }

    /**
     * Paginated queue fragments must emit Moodle page links for page two.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_paging_html_uses_page_param(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Paging queue']);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod/bookit');
        $roleid = \create_role('Bookit paging queue', 'bookitpagingqueue', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($serviceuser);

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Paging request %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => strtotime('2026-05-20 09:00:00') + $i,
                'endtime' => strtotime('2026-05-20 11:00:00') + $i,
                'duration' => 120,
                'roomid' => null,
                'participantsamount' => 12,
                'timecompensation' => 0,
                'compensationfordisadvantages' => '',
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'personinchargeid' => 0,
                'otherexaminers' => '',
                'coursetemplate' => null,
                'notes' => '',
                'internalnotes' => '',
                'supportpersons' => '',
                'extratimebefore' => 0,
                'extratimeafter' => 0,
                'refcourseid' => null,
                'usermodified' => $serviceuser->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $pageone = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 0, '', '');
        $pagetwo = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 1, '', '');

        $this->assertCount(25, $pageone['items']);
        $this->assertCount(1, $pagetwo['items']);
        $this->assertSame(1, (int)$pagetwo['paging']['currentpage']);
        $this->assertStringContainsString('page=1', $pageone['fragments']['paginghtml']);
        $this->assertStringContainsString('page=0', $pagetwo['fragments']['paginghtml']);
        $this->assertStringNotContainsString('queuepage=', $pageone['fragments']['paginghtml']);
    }

    /**
     * Confirmed and rejected/cancelled queue fragments must use page links and full counts.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_queue_paging_html_uses_page_param_for_confirmed_and_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Paging queue variants',
        ]);
        $context = \context_module::instance($bookit->cmid);
        $this->getDataGenerator()->enrol_user($serviceuser->id, $course->id);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit paging variants', 'bookitpagingvariants', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $serviceuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($serviceuser);

        foreach (
            [
            'confirmedrequests' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'rejectedcancelled' => event_access_manager::BOOKINGSTATUS_REJECTED,
            ] as $workspace => $status
        ) {
            for ($i = 1; $i <= 26; $i++) {
                $DB->insert_record('bookit_event', (object)[
                    'name' => sprintf('%s paging %02d', $workspace, $i),
                    'semester' => 20261,
                    'institutionid' => 1,
                    'starttime' => strtotime('+10 days 09:00:00') + $i,
                    'endtime' => strtotime('+10 days 11:00:00') + $i,
                    'duration' => 120,
                    'roomid' => null,
                    'participantsamount' => 12,
                    'timecompensation' => 0,
                    'compensationfordisadvantages' => '',
                    'bookingstatus' => $status,
                    'personinchargeid' => 0,
                    'otherexaminers' => '',
                    'coursetemplate' => null,
                    'notes' => '',
                    'internalnotes' => '',
                    'supportpersons' => '',
                    'extratimebefore' => 0,
                    'extratimeafter' => 0,
                    'refcourseid' => null,
                    'usermodified' => $serviceuser->id,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ]);
            }

            $pageone = get_overview_queue::execute($bookit->cmid, $workspace, [], [], [], 0, '', '');
            $pagetwo = get_overview_queue::execute($bookit->cmid, $workspace, [], [], [], 1, '', '');

            $this->assertCount(25, $pageone['items'], $workspace);
            $this->assertCount(1, $pagetwo['items'], $workspace);
            $this->assertSame(26, (int)$pageone['paging']['totalcount'], $workspace);
            $this->assertSame(1, (int)$pagetwo['paging']['currentpage'], $workspace);
            $this->assertStringContainsString('page=1', $pageone['fragments']['paginghtml'], $workspace);
            $this->assertStringContainsString('page=0', $pagetwo['fragments']['paginghtml'], $workspace);
        }
    }

    /**
     * All-requests fragments must use page links and preserve reporting filters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_allrequests_paging_html_uses_page_param(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'All requests paging']);
        $start = strtotime('+1 day 09:00:00');

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('All paging request %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => $start + ($i * DAYSECS),
                'endtime' => $start + ($i * DAYSECS) + HOURSECS,
                'duration' => 60,
                'roomid' => null,
                'participantsamount' => 12,
                'timecompensation' => 0,
                'compensationfordisadvantages' => '',
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'personinchargeid' => 0,
                'otherexaminers' => '',
                'coursetemplate' => null,
                'notes' => '',
                'internalnotes' => '',
                'supportpersons' => (string)get_admin()->id,
                'extratimebefore' => 0,
                'extratimeafter' => 0,
                'refcourseid' => null,
                'usermodified' => get_admin()->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $pageone = get_overview_queue::execute(
            $bookit->cmid,
            'allrequests',
            [],
            [],
            [20261],
            0,
            date('Y-m-d', $start),
            date('Y-m-d', $start + (30 * DAYSECS)),
            'assigned'
        );
        $pagetwo = get_overview_queue::execute(
            $bookit->cmid,
            'allrequests',
            [],
            [],
            [20261],
            1,
            date('Y-m-d', $start),
            date('Y-m-d', $start + (30 * DAYSECS)),
            'assigned'
        );

        $this->assertCount(25, $pageone['items']);
        $this->assertCount(1, $pagetwo['items']);
        $this->assertSame(1, (int)$pagetwo['paging']['currentpage']);
        $this->assertStringContainsString('page=1', $pageone['fragments']['paginghtml']);
        $this->assertStringContainsString('semesterids', $pageone['fragments']['paginghtml']);
        $this->assertStringContainsString('assignmentfilter=assigned', $pageone['fragments']['paginghtml']);
        $this->assertStringNotContainsString('queuepage=', $pageone['fragments']['paginghtml']);
    }

    /**
     * History fragments must use page links for terminal reporting results.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_history_paging_html_uses_page_param(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'History paging']);
        $end = strtotime('-1 day 11:00:00');

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('History paging request %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => $end - ($i * DAYSECS) - HOURSECS,
                'endtime' => $end - ($i * DAYSECS),
                'duration' => 60,
                'roomid' => null,
                'participantsamount' => 12,
                'timecompensation' => 0,
                'compensationfordisadvantages' => '',
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'personinchargeid' => 0,
                'otherexaminers' => '',
                'coursetemplate' => null,
                'notes' => '',
                'internalnotes' => '',
                'supportpersons' => '',
                'extratimebefore' => 0,
                'extratimeafter' => 0,
                'refcourseid' => null,
                'usermodified' => get_admin()->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $pageone = get_overview_queue::execute(
            $bookit->cmid,
            'history',
            [],
            [],
            [20261],
            0,
            date('Y-m-d', $end - (30 * DAYSECS)),
            date('Y-m-d', $end)
        );
        $pagetwo = get_overview_queue::execute(
            $bookit->cmid,
            'history',
            [],
            [],
            [20261],
            1,
            date('Y-m-d', $end - (30 * DAYSECS)),
            date('Y-m-d', $end)
        );

        $this->assertCount(25, $pageone['items']);
        $this->assertCount(1, $pagetwo['items']);
        $this->assertSame(1, (int)$pagetwo['paging']['currentpage']);
        $this->assertStringContainsString('page=1', $pageone['fragments']['paginghtml']);
        $this->assertStringNotContainsString('queuepage=', $pageone['fragments']['paginghtml']);
    }

    /**
     * Return cleaning must preserve reactivation fields for rejected queue items.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_return_clean_retains_reactivate_fields_for_rejected_queue(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Rejected return clean']);

        $DB->insert_record('bookit_event', (object)[
            'name' => 'Rejected return clean item',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-22 09:00:00'),
            'endtime' => strtotime('2026-05-22 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 9,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = get_overview_queue::execute($bookit->cmid, 'rejectedcancelled', [], [], [], 0, '', '');
        $cleaned = external_api::clean_returnvalue(get_overview_queue::execute_returns(), $response);

        $this->assertCount(1, $cleaned['items']);
        $item = $cleaned['items'][0];
        $this->assertArrayHasKey('hasreactivateaction', $item);
        $this->assertArrayHasKey('reactivateactionlabel', $item);
        $this->assertArrayHasKey('reactivatetargetstatus', $item);
        $this->assertArrayHasKey('overviewtab', $item);
        $this->assertTrue($item['hasreactivateaction']);
        $this->assertSame('rejectedcancelled', $item['overviewtab']);
    }

    /**
     * Open queue items without reactivation still declare all four reactivation fields.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_return_clean_declares_reactivate_fields_when_action_unavailable(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Open return clean']);

        $DB->insert_record('bookit_event', (object)[
            'name' => 'Open return clean item',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 12,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 0, '', '');
        $cleaned = external_api::clean_returnvalue(get_overview_queue::execute_returns(), $response);

        $item = $cleaned['items'][0];
        $this->assertArrayHasKey('hasreactivateaction', $item);
        $this->assertArrayHasKey('reactivateactionlabel', $item);
        $this->assertArrayHasKey('reactivatetargetstatus', $item);
        $this->assertArrayHasKey('overviewtab', $item);
        $this->assertFalse($item['hasreactivateaction']);
    }

    /**
     * Support viewers receive reactivation fields but cannot reactivate queue items.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_support_return_clean_declares_reactivate_fields_without_action(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Support return clean']);
        $context = \context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $supportrole = $DB->get_record('role', ['shortname' => 'bookit_supportonsite']);
        if (!$supportrole) {
            $supportroleid = \create_role('Bookit support on site', 'bookit_supportonsite', 'student');
        } else {
            $supportroleid = (int)$supportrole->id;
        }
        \assign_capability('mod/bookit:view', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $supportroleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        $supportuser = $this->getDataGenerator()->create_user(['username' => 'steven.support']);
        \role_assign($supportroleid, $supportuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->getDataGenerator()->enrol_user($supportuser->id, $course->id, 'student');
        $this->setUser($supportuser);

        $DB->insert_record('bookit_event', (object)[
            'name' => 'Support rejected read-only',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-22 09:00:00'),
            'endtime' => strtotime('2026-05-22 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 9,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => (int)$supportuser->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $response = get_overview_queue::execute($bookit->cmid, 'rejectedcancelled', [], [], [], 0, '', '');
        $cleaned = external_api::clean_returnvalue(get_overview_queue::execute_returns(), $response);

        $item = $cleaned['items'][0];
        $this->assertArrayHasKey('hasreactivateaction', $item);
        $this->assertArrayHasKey('reactivateactionlabel', $item);
        $this->assertArrayHasKey('reactivatetargetstatus', $item);
        $this->assertArrayHasKey('overviewtab', $item);
        $this->assertFalse($item['hasreactivateaction']);
    }
}
