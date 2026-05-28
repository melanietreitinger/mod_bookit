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
 * Regression tests for booking-status updates during governed-read rollout.
 *
 * @covers \mod_bookit\external\update_event_booking_status
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_event_booking_status_test extends advanced_testcase {
    /**
     * Booking-status updates must coexist with governed queue refreshes.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_updates_status_and_refreshes_governed_queues(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Workflow queue']);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Queue refresh event',
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

        $before = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 1, '', '');
        $this->assertSame(1, (int)$before['summary']['openrequestcount']);
        $this->assertSame($eventid, (int)$before['items'][0]['eventid']);

        $response = update_event_booking_status::execute(
            $bookit->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'openrequests',
            1
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_ACCEPTED, (int)$response['status']);
        $this->assertSame('openrequests', $response['tab']);
        $this->assertNotEmpty($response['queue']);
        $this->assertSame(0, (int)$response['queue']['summary']['openrequestcount']);
        $this->assertSame([], $response['queue']['items']);

        $after = get_overview_queue::execute($bookit->cmid, 'openrequests', [], [], [], 1, '', '');
        $this->assertSame(0, (int)$after['summary']['openrequestcount']);
        $this->assertSame([], $after['items']);

        $record = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_ACCEPTED, (int)$record->bookingstatus);
    }
}
