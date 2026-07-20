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
 * Unit tests for overview_queue_read_mapper.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\read;

use advanced_testcase;
use context_module;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;

/**
 * Tests for overview_queue_read_mapper.
 *
 * @covers \mod_bookit\local\read\overview_queue_read_mapper
 */
final class overview_queue_read_mapper_test extends advanced_testcase {
    /**
     * Create a service-team context with managebasics for the given user.
     *
     * @param int $userid
     * @return array{0: context_module, 1: int}
     */
    private function create_service_team_context(int $userid): array {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit service mapper', 'bookitservicemapper', 'manager');
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return [$context, (int)$bookit->cmid];
    }

    /**
     * Persist a minimal event with one workflow history entry.
     *
     * @param int $userid
     * @return \stdClass
     */
    private function create_event_with_history(int $userid): \stdClass {
        global $DB;

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Mapper workflow event',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $userid,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        event_manager::record_booking_history(
            $eventid,
            'created',
            $userid,
            null,
            event_access_manager::BOOKINGSTATUS_NEW,
            ['bookingstatus' => ['from' => null, 'to' => 0]],
            false
        );

        return $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
    }

    /**
     * Mapper status cell HTML includes workflow history expander when entries exist.
     *
     * @return void
     */
    public function test_map_includes_workflow_history_entries_in_status_cell(): void {
        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        [$context, $cmid] = $this->create_service_team_context((int)$serviceuser->id);
        $this->setUser($serviceuser);

        $event = $this->create_event_with_history((int)$serviceuser->id);
        $history = array_values(event_manager::get_booking_history((int)$event->id));
        $latest = $history[0] ?? null;

        $mapped = overview_queue_read_mapper::map(
            $event,
            $context,
            (int)$serviceuser->id,
            $latest,
            'openrequests',
            $cmid
        );

        $this->assertStringContainsString('mod-bookit-history-details', $mapped['statuscellhtml']);
        $this->assertStringContainsString(
            get_string('overview_workflow_history', 'mod_bookit'),
            $mapped['statuscellhtml']
        );
        $this->assertNotEmpty(event_manager::build_overview_workflow_history(
            $event,
            $context,
            (int)$serviceuser->id
        ));
    }

    /**
     * Create a support-on-site context for mapper tests.
     *
     * @param int $userid
     * @return array{0: context_module, 1: int}
     */
    private function create_support_context(int $userid): array {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit support mapper', 'bookit_supportonsite', 'student');
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return [$context, (int)$bookit->cmid];
    }

    /**
     * Support mapper output omits restricted workflow-history content.
     *
     * @return void
     */
    public function test_map_omits_restricted_workflow_history_for_support(): void {
        global $DB;

        $this->resetAfterTest(true);

        $actor = $this->getDataGenerator()->create_user(['firstname' => 'Service', 'lastname' => 'Actor']);
        $supportuser = $this->getDataGenerator()->create_user(['firstname' => 'Steven', 'lastname' => 'Support']);
        [$context, $cmid] = $this->create_support_context((int)$supportuser->id);
        $this->setUser($supportuser);

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Support mapper history event',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => 'secret',
            'supportpersons' => (string)$supportuser->id,
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $actor->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        event_manager::record_booking_history(
            $eventid,
            'updated',
            (int)$actor->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [
                'bookingstatus' => ['from' => 0, 'to' => 1],
                'internalnotes' => ['from' => 'old secret', 'to' => 'secret'],
            ],
            false
        );

        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $history = array_values(event_manager::get_booking_history((int)$event->id));
        $latest = $history[0] ?? null;

        $mapped = overview_queue_read_mapper::map(
            $event,
            $context,
            (int)$supportuser->id,
            $latest,
            'openrequests',
            $cmid
        );

        $this->assertStringNotContainsString('secret', $mapped['statuscellhtml']);
        $this->assertStringNotContainsString('Service Actor', $mapped['statuscellhtml']);
        $this->assertStringContainsString(
            get_string('event_bookingstatus', 'mod_bookit'),
            $mapped['statuscellhtml']
        );
    }

    /**
     * All five workspace tabs expose workflow history details in the status cell.
     *
     * @return void
     */
    public function test_map_status_cell_showdetails_for_all_workspace_tabs(): void {
        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        [$context, $cmid] = $this->create_service_team_context((int)$serviceuser->id);
        $this->setUser($serviceuser);

        $event = $this->create_event_with_history((int)$serviceuser->id);
        $history = array_values(event_manager::get_booking_history((int)$event->id));
        $latest = $history[0] ?? null;

        $workspacetabs = [
            'allrequests',
            'history',
            'openrequests',
            'confirmedrequests',
            'rejectedcancelled',
        ];

        foreach ($workspacetabs as $workspace) {
            $mapped = overview_queue_read_mapper::map(
                $event,
                $context,
                (int)$serviceuser->id,
                $latest,
                $workspace,
                $cmid
            );

            $this->assertStringContainsString('mod-bookit-history-details', $mapped['statuscellhtml'], $workspace);
            $this->assertStringContainsString(
                get_string('overview_workflow_history', 'mod_bookit'),
                $mapped['statuscellhtml'],
                $workspace
            );

            if ($workspace === 'rejectedcancelled') {
                $this->assertSame('rejectedcancelled', $mapped['overviewtab']);
                $this->assertArrayHasKey('hasreactivateaction', $mapped);
                $this->assertArrayHasKey('reactivateactionlabel', $mapped);
                $this->assertArrayHasKey('reactivatetargetstatus', $mapped);
            }
        }
    }

    /**
     * Rejected queue mapper output includes all reactivation payload keys for service team.
     *
     * @return void
     */
    public function test_map_rejected_queue_includes_reactivation_payload_keys(): void {
        global $DB;

        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        [$context, $cmid] = $this->create_service_team_context((int)$serviceuser->id);
        $this->setUser($serviceuser);

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Mapper rejected reactivate',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'personinchargeid' => null,
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

        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $mapped = overview_queue_read_mapper::map(
            $event,
            $context,
            (int)$serviceuser->id,
            null,
            'rejectedcancelled',
            $cmid
        );

        $this->assertTrue($mapped['hasreactivateaction']);
        $this->assertNotEmpty($mapped['reactivateactionlabel']);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$mapped['reactivatetargetstatus']);
        $this->assertSame('rejectedcancelled', $mapped['overviewtab']);
    }
}
