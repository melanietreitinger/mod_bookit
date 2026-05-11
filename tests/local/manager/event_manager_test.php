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
 * Unit tests for event_manager reporting helpers.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use context_module;

/**
 * Unit tests for event_manager helper methods.
 *
 * @covers \mod_bookit\local\manager\event_manager
 */
final class event_manager_test extends advanced_testcase {
    /**
     * Winter terms must bridge the year boundary correctly.
     *
     * @return void
     */
    public function test_get_current_semester_handles_winter_bridge(): void {
        $this->assertSame(20252, event_manager::get_current_semester(strtotime('2026-01-15 12:00:00')));
        $this->assertSame(20261, event_manager::get_current_semester(strtotime('2026-05-15 12:00:00')));
        $this->assertSame(20262, event_manager::get_current_semester(strtotime('2026-11-15 12:00:00')));
    }

    /**
     * Reporting defaults must span the full current year.
     *
     * @return void
     */
    public function test_get_reporting_default_range_returns_full_year(): void {
        [$start, $end] = event_manager::get_reporting_default_range(strtotime('2026-05-07 10:00:00'));

        $this->assertSame(strtotime('2026-01-01 00:00:00'), $start);
        $this->assertSame(strtotime('2026-12-31 23:59:59'), $end);
    }

    /**
     * Reporting defaults must apply the real current semester on first load.
     *
     * @return void
     */
    public function test_get_reporting_default_semester_ids_returns_current_semester(): void {
        $this->assertSame([20261], event_manager::get_reporting_default_semester_ids(strtotime('2026-05-07 10:00:00')));
    }

    /**
     * Semester filtering must exclude legacy semester bookings unless the explicit legacy option is selected.
     *
     * @return void
     */
    public function test_filter_overview_events_handles_legacy_semester_explicitly(): void {
        $activeevent = (object)[
            'id' => 1,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('+1 day'),
            'endtime' => strtotime('+1 day +2 hours'),
        ];
        $legacyevent = (object)[
            'id' => 2,
            'semester' => 0,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('+2 days'),
            'endtime' => strtotime('+2 days +2 hours'),
        ];

        $filtered = event_manager::filter_overview_events([$activeevent, $legacyevent], [
            'semesterids' => [20261],
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame(1, (int)$filtered[0]->id);

        $filteredwithlegacy = event_manager::filter_overview_events([$activeevent, $legacyevent], [
            'semesterids' => [20261, 0],
        ]);

        $this->assertCount(2, $filteredwithlegacy);
    }

    /**
     * History filtering must separate past and upcoming bookings while keeping additional filters aligned.
     *
     * @return void
     */
    public function test_filter_overview_events_splits_history_and_applies_personal_filters(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $futureevent = (object)[
            'id' => 11,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];
        $pastevent = (object)[
            'id' => 12,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
            'starttime' => strtotime('2026-05-01 09:00:00'),
            'endtime' => strtotime('2026-05-01 11:00:00'),
        ];
        $filteredout = (object)[
            'id' => 13,
            'semester' => 20262,
            'institutionid' => 2,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-05-09 09:00:00'),
            'endtime' => strtotime('2026-05-09 11:00:00'),
        ];

        $active = event_manager::filter_overview_events([$futureevent, $pastevent, $filteredout], [
            'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_ACCEPTED],
            'facultyids' => [1],
            'semesterids' => [20261],
        ], false, $referencetime);
        $history = event_manager::filter_overview_events([$futureevent, $pastevent, $filteredout], [
            'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_ACCEPTED],
            'facultyids' => [1],
            'semesterids' => [20261],
        ], true, $referencetime);

        $this->assertCount(1, $active);
        $this->assertSame(11, (int)$active[0]->id);
        $this->assertCount(1, $history);
        $this->assertSame(12, (int)$history[0]->id);
    }

    /**
     * Workflow history entries are persisted and returned newest-first.
     *
     * @return void
     */
    public function test_record_booking_history_persists_entries(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'History test event',
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
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        event_manager::record_booking_history(
            $eventid,
            'rejected',
            (int)$user->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            event_access_manager::BOOKINGSTATUS_REJECTED,
            ['bookingstatus' => ['from' => 1, 'to' => 4]],
            false
        );

        $history = array_values(event_manager::get_booking_history($eventid));
        $this->assertCount(1, $history);
        $this->assertSame('rejected', $history[0]->action);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$history[0]->oldstatus);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_REJECTED, (int)$history[0]->newstatus);
        $this->assertSame((int)$user->id, (int)$history[0]->usermodified);
        $this->assertSame(
            ['bookingstatus' => ['from' => 1, 'to' => 4]],
            json_decode($history[0]->changedfields, true)
        );
    }

    /**
     * Rejected requests stay discoverable only until the requested slot has passed.
     *
     * @return void
     */
    public function test_get_rejected_requests_excludes_past_items(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $common = [
            'semester' => 20261,
            'institutionid' => null,
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $futureid = $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Future rejected',
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Past rejected',
            'starttime' => strtotime('2026-05-01 09:00:00'),
            'endtime' => strtotime('2026-05-01 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]));

        $rejected = array_values(event_manager::get_rejected_requests(strtotime('2026-05-07 10:00:00')));
        $this->assertCount(1, $rejected);
        $this->assertSame($futureid, (int)$rejected[0]->id);
        $this->assertSame(1, event_manager::count_rejected_requests(strtotime('2026-05-07 10:00:00')));
    }

    /**
     * Status transitions keep workflow history and audit events aligned.
     *
     * @return void
     */
    public function test_transition_booking_status_records_history_and_triggers_audit_event(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit test']);
        $context = context_module::instance($bookit->cmid);

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Transition event',
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
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $sink = $this->redirectEvents();
        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$user->id,
            $context
        );
        $events = $sink->get_events();
        $sink->close();

        $updated = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$updated->bookingstatus);

        $history = array_values(event_manager::get_booking_history($eventid));
        $this->assertCount(1, $history);
        $this->assertSame('reactivated', $history[0]->action);
        $this->assertSame(1, (int)$history[0]->recoverymarker);
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\mod_bookit\event\booking_reactivated::class, $events[0]);
    }
}
