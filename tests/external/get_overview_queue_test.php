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

        $response = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 1, '', '');

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertSame('openrequests', $response['workspace']);
        $this->assertCount(1, $response['items']);
        $this->assertSame($eventid, (int)$response['items'][0]['eventid']);
        $this->assertSame(1, (int)$response['summary']['openrequestcount']);
        $this->assertSame('1 open requests', $response['summary']['workspacecounttext']);
        $this->assertSame(1, (int)$response['paging']['currentpage']);
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

        $response = get_overview_queue::execute($bookit->cmid, 'rejectedrequests', [], [], [], 1, '', '');

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertSame('rejectedrequests', $response['workspace']);
        $this->assertCount(1, $response['items']);
        $this->assertSame($eventid, (int)$response['items'][0]['eventid']);
        $this->assertSame(1, (int)$response['summary']['rejectedrequestcount']);
        $this->assertSame(
            get_string('overview_rejected_request_count', 'mod_bookit', 1),
            $response['summary']['workspacecounttext']
        );
        $this->assertSame(1, (int)$response['paging']['currentpage']);
        $this->assertFalse($response['paging']['adjusted']);
    }
}
