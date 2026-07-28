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
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\install_helper;
use mod_bookit\tests\read_contract_assertions_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../external/read_contract_assertions_trait.php');

/**
 * Unit tests for event_manager helper methods.
 *
 * @covers \mod_bookit\local\manager\event_manager
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * Coverage metadata and PHPMD suppression for the reporting workflow test case.
 *
 * @covers \mod_bookit\local\manager\event_manager
 * @SuppressWarnings(PHPMD)
 */
final class event_manager_test extends advanced_testcase {
    use read_contract_assertions_trait;

// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /**
     * Set up the message providers and default notification configuration for workflow tests.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        message_update_providers('mod_bookit');
        install_helper::ensure_booking_status_notification_defaults();
        set_config('bookingstatus_notify_serviceteam', 1, 'mod_bookit');
        set_config('bookingstatus_notify_bookingperson', 1, 'mod_bookit');
        set_config('bookingstatus_notify_personincharge', 1, 'mod_bookit');
        set_config('bookingstatus_notify_otherexaminers', 1, 'mod_bookit');
        set_config('bookingstatus_service_addresses', '', 'mod_bookit');
    }

    /**
     * Create a Bookit module context for lifecycle notification tests.
     *
     * @return context_module
     */
    private function create_bookit_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Lifecycle notifications',
        ]);
        return context_module::instance($bookit->cmid);
    }

    /**
     * Create a room for event persistence tests.
     *
     * @return int
     */
    private function create_room(): int {
        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        return $generator->create_room([
            'name' => 'Lifecycle notification room',
            'shortname' => 'NTF001',
        ]);
    }

    /**
     * Build a test event entity for lifecycle notification scenarios.
     *
     * @param int $roomid
     * @param int $userid
     * @param array $overrides
     * @return bookit_event
     */
    private function build_bookit_event(int $roomid, int $userid, array $overrides = []): bookit_event {
        return new bookit_event(
            0,
            $overrides['name'] ?? 'Lifecycle notification event',
            $overrides['semester'] ?? 20261,
            $overrides['institutionid'] ?? 1,
            $overrides['starttime'] ?? strtotime('2026-05-08 09:00:00'),
            $overrides['endtime'] ?? strtotime('2026-05-08 11:00:00'),
            $overrides['duration'] ?? 120,
            $overrides['roomid'] ?? $roomid,
            $overrides['participantsamount'] ?? 10,
            $overrides['timecompensation'] ?? 0,
            $overrides['compensationfordisadvantages'] ?? '',
            $overrides['bookingstatus'] ?? event_access_manager::BOOKINGSTATUS_NEW,
            $overrides['personinchargeid'] ?? null,
            $overrides['otherexaminers'] ?? '',
            $overrides['coursetemplate'] ?? 0,
            $overrides['notes'] ?? 'External note',
            $overrides['internalnotes'] ?? 'Internal note',
            $overrides['supportpersons'] ?? '',
            $overrides['extratimebefore'] ?? 0,
            $overrides['extratimeafter'] ?? 0,
            $overrides['refcourseid'] ?? null,
            $overrides['usercreated'] ?? $userid,
            $overrides['usermodified'] ?? $userid,
            time(),
            time(),
            $overrides['resources'] ?? [],
        );
    }

    /**
     * Create a Bookit module context with an observer role assigned to the provided user.
     *
     * @param int $userid
     * @return context_module
     */
    private function create_observer_context_for_user(int $userid): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Observer reporting']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit observer', 'bookitobserver', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewrestrictedobserver', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Insert a minimal event record for tests.
     *
     * @param array $overrides
     * @return int
     */
    private function create_event_record(array $overrides = []): int {
        global $DB;

        $defaults = [
            'name' => 'Test event',
            'semester' => 20261,
            'institutionid' => 1,
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
            'usercreated' => 0,
            'usermodified' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $record = array_merge($defaults, $overrides);
        if (empty($record['usercreated']) && !empty($record['usermodified'])) {
            $record['usercreated'] = $record['usermodified'];
        }

        return (int)$DB->insert_record('bookit_event', (object)$record);
    }

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
     * Booking form semester options must include the current semester for default preselection.
     *
     * @return void
     */
    public function test_current_semester_is_available_for_booking_form_options(): void {
        $referencetime = strtotime('2026-05-15 12:00:00');
        $currentsemester = event_manager::get_current_semester($referencetime);
        $currentyear = (int)date('Y', $referencetime);
        $lookbackyears = 1;
        $lookaheadyears = 1;

        $semesters = [];
        for ($i = -$lookbackyears; $i <= $lookaheadyears; $i++) {
            $semesters[($currentyear + $i) * 10 + 1] = 'summer';
            $semesters[($currentyear + $i) * 10 + 2] = 'winter';
        }

        $this->assertArrayHasKey($currentsemester, $semesters);
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
     * Service-team reporting defaults must start today instead of year start.
     *
     * @return void
     */
    public function test_get_reporting_default_range_service_team_starts_today(): void {
        $reference = strtotime('2026-05-07 10:00:00');
        [$start, $end] = event_manager::get_reporting_default_range($reference, true);

        $this->assertSame(usergetmidnight($reference), $start);
        $this->assertSame(strtotime('2026-12-31 23:59:59'), $end);
    }

    /**
     * Service-team History tab defaults span year start through end of today.
     *
     * @return void
     */
    public function test_get_reporting_default_range_service_team_history_year_to_date(): void {
        $reference = strtotime('2026-05-07 10:00:00');
        [$start, $end] = event_manager::get_reporting_default_range($reference, true, true);

        $this->assertSame(strtotime('2026-01-01 00:00:00'), $start);
        $expectedend = (new \DateTime())->setTimestamp(usergetmidnight($reference))->setTime(23, 59, 59)->getTimestamp();
        $this->assertSame($expectedend, $end);
    }

    /**
     * Non-service-team reporting defaults stay full-year when History flag is set.
     *
     * @return void
     */
    public function test_get_reporting_default_range_non_service_team_unchanged_with_history_flag(): void {
        $reference = strtotime('2026-05-07 10:00:00');
        [$start, $end] = event_manager::get_reporting_default_range($reference, false, true);

        $this->assertSame(strtotime('2026-01-01 00:00:00'), $start);
        $this->assertSame(strtotime('2026-12-31 23:59:59'), $end);
    }

    /**
     * Default reporting status filter excludes terminal statuses.
     *
     * @return void
     */
    public function test_reporting_default_status_filter_excludes_terminal(): void {
        $futurestart = strtotime('+2 days 09:00:00');
        $futureend = strtotime('+2 days 11:00:00');
        $base = ['starttime' => $futurestart, 'endtime' => $futureend];
        $events = [
            (object) array_merge(['id' => 1, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW], $base),
            (object) array_merge(['id' => 2, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS], $base),
            (object) array_merge(['id' => 3, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED], $base),
            (object) array_merge(['id' => 4, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED], $base),
            (object) array_merge(['id' => 5, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED], $base),
        ];

        $filtered = event_manager::filter_overview_events(
            $events,
            ['bookingstatuses' => event_manager::get_reporting_default_booking_status_filter()]
        );

        $this->assertSame([1, 2, 3], array_map(static fn($event): int => (int)$event->id, $filtered));
    }

    /**
     * Overview status resolver returns default triplet when filter param is absent.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_default_when_implicit(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], false);

        $this->assertSame(event_manager::get_reporting_default_booking_status_filter(), $resolved);
    }

    /**
     * Overview status resolver returns default triplet when selection is explicitly empty.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_default_when_empty_explicit(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], true);

        $this->assertSame(event_manager::get_reporting_default_booking_status_filter(), $resolved);
    }

    /**
     * Overview status resolver honours explicit multi-status selection.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_explicit_subset(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids(
            [event_access_manager::BOOKINGSTATUS_NEW, event_access_manager::BOOKINGSTATUS_CANCELED],
            true
        );

        $this->assertSame(
            [event_access_manager::BOOKINGSTATUS_NEW, event_access_manager::BOOKINGSTATUS_CANCELED],
            $resolved
        );
    }

    /**
     * Overview status resolver keeps all five valid statuses when explicitly selected.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_all_five(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids(
            [
                event_access_manager::BOOKINGSTATUS_NEW,
                event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                event_access_manager::BOOKINGSTATUS_CONFIRMED,
                event_access_manager::BOOKINGSTATUS_CANCELED,
                event_access_manager::BOOKINGSTATUS_REJECTED,
            ],
            true
        );

        $this->assertSame(
            [
                event_access_manager::BOOKINGSTATUS_NEW,
                event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                event_access_manager::BOOKINGSTATUS_CONFIRMED,
                event_access_manager::BOOKINGSTATUS_CANCELED,
                event_access_manager::BOOKINGSTATUS_REJECTED,
            ],
            $resolved
        );
    }

    /**
     * Resolved status ids are suitable for overview tab navigation arrays.
     *
     * @return void
     */
    public function test_overview_navigation_params_status_array_for_service_team(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids(
            [event_access_manager::BOOKINGSTATUS_NEW, event_access_manager::BOOKINGSTATUS_IN_PROGRESS],
            true
        );

        $this->assertSame(
            [event_access_manager::BOOKINGSTATUS_NEW, event_access_manager::BOOKINGSTATUS_IN_PROGRESS],
            $resolved
        );
    }

    /**
     * History tab default status filter returns all five booking statuses.
     *
     * @return void
     */
    public function test_get_history_default_booking_status_filter_returns_all_statuses(): void {
        $this->assertSame(
            [
                event_access_manager::BOOKINGSTATUS_NEW,
                event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                event_access_manager::BOOKINGSTATUS_CONFIRMED,
                event_access_manager::BOOKINGSTATUS_CANCELED,
                event_access_manager::BOOKINGSTATUS_REJECTED,
            ],
            event_manager::get_history_default_booking_status_filter()
        );
    }

    /**
     * History tab resolver returns full default when filter param is absent (reporting path).
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_history_implicit(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], false, true, true);

        $this->assertSame(event_manager::get_history_default_booking_status_filter(), $resolved);
    }

    /**
     * Participant History resolver returns empty default when filter param is absent.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_participant_history_implicit(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], false, true, false);

        $this->assertSame([], $resolved);
    }

    /**
     * Participant History resolver returns empty default when selection is explicitly empty.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_participant_history_empty_explicit(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], true, true, false);

        $this->assertSame([], $resolved);
    }

    /**
     * Overview tab resolver keeps triplet default when filter param is absent.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_overview_implicit_unchanged(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], false, false);

        $this->assertSame(event_manager::get_reporting_default_booking_status_filter(), $resolved);
    }

    /**
     * History tab resolver returns full default when selection is explicitly empty (reporting path).
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_history_empty_explicit(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], true, true, true);

        $this->assertSame(event_manager::get_history_default_booking_status_filter(), $resolved);
    }

    /**
     * Overview tab resolver returns triplet when selection is explicitly empty.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_overview_empty_explicit(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], true, false);

        $this->assertSame(event_manager::get_reporting_default_booking_status_filter(), $resolved);
    }

    /**
     * Explicit status selection overrides history default.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_explicit_overrides_history_default(): void {
        $resolved = event_manager::resolve_overview_booking_status_filter_ids(
            [event_access_manager::BOOKINGSTATUS_CONFIRMED],
            true,
            true
        );

        $this->assertSame([event_access_manager::BOOKINGSTATUS_CONFIRMED], $resolved);
    }

    /**
     * History default status filter includes all statuses that History rules allow.
     *
     * @return void
     */
    public function test_filter_overview_events_history_default_includes_all_statuses(): void {
        $paststart = strtotime('-5 days 09:00:00');
        $pastend = strtotime('-5 days 11:00:00');
        $base = ['starttime' => $paststart, 'endtime' => $pastend];
        $events = [
            (object) array_merge(['id' => 1, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW], $base),
            (object) array_merge(['id' => 2, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS], $base),
            (object) array_merge(['id' => 3, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED], $base),
            (object) array_merge(['id' => 4, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED], $base),
            (object) array_merge(['id' => 5, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED], $base),
        ];

        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], false, true, true);
        $this->assertSame(event_manager::get_history_default_booking_status_filter(), $resolved);

        $filtered = event_manager::filter_overview_events(
            $events,
            ['bookingstatuses' => $resolved],
            true
        );

        $this->assertSame([1, 2, 3, 4, 5], array_map(static fn($event): int => (int)$event->id, $filtered));
    }

    /**
     * Participant History with empty status filter does not restrict to terminal statuses only.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_history_empty_status_no_restriction(): void {
        $paststart = strtotime('-5 days 09:00:00');
        $pastend = strtotime('-5 days 11:00:00');
        $futurestart = strtotime('+2 days 09:00:00');
        $futureend = strtotime('+2 days 11:00:00');
        $events = [
            (object) [
                'id' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'starttime' => $futurestart,
                'endtime' => $futureend,
            ],
            (object) [
                'id' => 3,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'starttime' => $paststart,
                'endtime' => $pastend,
            ],
            (object) [
                'id' => 4,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'starttime' => $futurestart,
                'endtime' => $futureend,
            ],
            (object) [
                'id' => 5,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'starttime' => $futurestart,
                'endtime' => $futureend,
            ],
        ];

        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], false, true, false);
        $this->assertSame([], $resolved);

        $filtered = event_manager::filter_overview_events(
            $events,
            ['bookingstatuses' => $resolved],
            true
        );

        $filteredids = array_map(static fn($event): int => (int)$event->id, $filtered);
        $this->assertContains(3, $filteredids);
        $this->assertContains(4, $filteredids);
        $this->assertContains(5, $filteredids);
        $this->assertNotSame([4, 5], $filteredids);
    }

    /**
     * Booker overview default status filter excludes terminal statuses.
     *
     * @return void
     */
    public function test_filter_overview_events_booker_default_status_triplet(): void {
        $futurestart = strtotime('+2 days 09:00:00');
        $futureend = strtotime('+2 days 11:00:00');
        $base = ['starttime' => $futurestart, 'endtime' => $futureend];
        $events = [
            (object) array_merge(['id' => 1, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW], $base),
            (object) array_merge(['id' => 2, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS], $base),
            (object) array_merge(['id' => 3, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED], $base),
            (object) array_merge(['id' => 4, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED], $base),
            (object) array_merge(['id' => 5, 'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED], $base),
        ];

        $resolved = event_manager::resolve_overview_booking_status_filter_ids([], false);
        $filtered = event_manager::filter_overview_events($events, ['bookingstatuses' => $resolved]);

        $this->assertSame([1, 2, 3], array_map(static fn($event): int => (int)$event->id, $filtered));
    }

    /**
     * Reporting query must expose creator full name fields instead of numeric ids only.
     *
     * @return void
     */
    public function test_get_events_for_reporting_includes_createdby_fullname(): void {
        global $DB;

        $this->resetAfterTest(true);
        $creator = $this->getDataGenerator()->create_user([
            'firstname' => 'Casey',
            'lastname' => 'Creator',
        ]);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Reporting']);
        $context = context_module::instance($bookit->cmid);

        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Created-by reporting event',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
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
            'usercreated' => (int)$creator->id,
            'usermodified' => (int)$creator->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $events = event_manager::get_events_for_reporting(
            $context,
            (int)get_admin()->id,
            strtotime('2026-05-01 00:00:00'),
            strtotime('2026-05-31 23:59:59'),
            []
        );
        $event = null;
        foreach ($events as $candidate) {
            if ((int)$candidate->id === $eventid) {
                $event = $candidate;
                break;
            }
        }

        $this->assertNotNull($event);
        $this->assertSame('Casey', $event->creatorfirstname);
        $this->assertSame('Creator', $event->creatorlastname);
        $this->assertSame(0, (int)$event->creatordeleted);
    }

    /**
     * Reporting overview events must be ordered newest start time first.
     *
     * @return void
     */
    public function test_get_events_for_reporting_orders_by_starttime_desc(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Reporting order']);
        $context = context_module::instance($bookit->cmid);

        $common = [
            'semester' => 20261,
            'institutionid' => 1,
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
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
            'usermodified' => (int)get_admin()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $olderid = (int)$DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Older reporting event',
            'starttime' => strtotime('2026-05-10 09:00:00'),
            'endtime' => strtotime('2026-05-10 11:00:00'),
        ]));
        $newerid = (int)$DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Newer reporting event',
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
        ]));

        $events = event_manager::get_events_for_reporting(
            $context,
            (int)get_admin()->id,
            strtotime('2026-05-01 00:00:00'),
            strtotime('2026-05-31 23:59:59'),
            []
        );

        $ids = array_map(static fn($event): int => (int)$event->id, $events);
        $this->assertSame([$newerid, $olderid], array_values(array_intersect($ids, [$newerid, $olderid])));
    }

    /**
     * Terminal queue must include booker-initiated cancellations.
     *
     * @return void
     */
    public function test_rejectedcancelled_workspace_includes_booker_canceled(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();
        $booker = $this->getDataGenerator()->create_user();
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
            'usercreated' => $booker->id,
            'usermodified' => $booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $canceledid = $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Booker canceled',
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
        ]));
        event_manager::record_booking_history(
            (int)$canceledid,
            'status_changed',
            (int)$booker->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $requests = $this->get_workspace_query_result($context, $adminid, 'rejectedcancelled')['events'];
        $ids = array_map(static fn($event): int => (int)$event->id, $requests);

        $this->assertContains((int)$canceledid, $ids);
    }

    /**
     * Terminal queue must exclude service-team-initiated cancellations.
     *
     * @return void
     */
    public function test_rejectedcancelled_workspace_excludes_service_team_canceled(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();
        $booker = $this->getDataGenerator()->create_user();
        $serviceteam = $this->getDataGenerator()->create_user();
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
            'usercreated' => $booker->id,
            'usermodified' => $booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $canceledid = $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Service canceled',
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
        ]));
        event_manager::record_booking_history(
            (int)$canceledid,
            'status_changed',
            (int)$serviceteam->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $requests = $this->get_workspace_query_result($context, $adminid, 'rejectedcancelled')['events'];
        $ids = array_map(static fn($event): int => (int)$event->id, $requests);

        $this->assertNotContains((int)$canceledid, $ids);
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
     * Explicit semester selections must bypass the automatic current-semester default.
     *
     * @return void
     */
    public function test_resolve_effective_semester_filter_ids_preserves_explicit_selection(): void {
        $this->assertSame(
            [20261],
            event_manager::resolve_effective_semester_filter_ids([], false, strtotime('2026-05-07 10:00:00'))
        );
        $this->assertSame(
            [20262],
            event_manager::resolve_effective_semester_filter_ids([20262], true, strtotime('2026-05-07 10:00:00'))
        );
    }

    /**
     * Observer mode must return only accepted events in a neutral reserved projection.
     *
     * @return void
     */
    public function test_get_events_in_timerange_returns_accepted_only_for_observer(): void {
        $this->resetAfterTest(true);
        $observer = $this->getDataGenerator()->create_user();
        $context = $this->create_observer_context_for_user($observer->id);
        $this->setUser($observer);

        $this->create_event_record([
            'name' => 'Observer hidden request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-05-08 08:00:00'),
            'endtime' => strtotime('2026-05-08 09:00:00'),
        ]);
        $acceptedid = $this->create_event_record([
            'name' => 'Observer accepted booking',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'starttime' => strtotime('2026-05-08 10:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ]);

        $events = event_manager::get_events_in_timerange('2026-05-08 00:00', '2026-05-08 23:59', (int)$context->instanceid);

        $this->assertCount(1, $events);
        $this->assert_is_canonical_calendar_event($events[0]);
        $this->assertSame($acceptedid, (int)$events[0]['id']);
        $this->assertSame('Reserved', $events[0]['title']);
        $this->assertSame('reserved_projection', $events[0]['extendedProps']['visibilitymode']);
    }

    /**
     * Governed calendar reads must apply canonical filters and return only the final contract shape.
     *
     * @return void
     */
    public function test_get_governed_calendar_events_applies_filters_and_maps_payload(): void {
        $this->resetAfterTest(true);

        $teacher = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Governed calendar']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit reader', 'bookitreader', 'teacher');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $teacher->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($teacher);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Governed room',
            'shortname' => 'GR-1',
            'location' => 'Campus A',
        ]);

        $visibleid = $this->create_event_record([
            'name' => 'Governed Physics',
            'institutionid' => 1,
            'roomid' => $roomid,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
        ]);
        $this->create_event_record([
            'name' => 'Governed Hidden',
            'institutionid' => 1,
            'roomid' => $roomid,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('2026-05-20 13:00:00'),
            'endtime' => strtotime('2026-05-20 14:00:00'),
        ]);

        $events = event_manager::get_governed_calendar_events(
            $context,
            (int)$teacher->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            [
                'roomids' => [$roomid],
                'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CONFIRMED],
                'search' => 'Physics',
            ]
        );

        $this->assertCount(1, $events);
        $this->assert_is_canonical_calendar_event($events[0]);
        $this->assertSame($visibleid, (int)$events[0]['id']);
        $this->assertSame('full', $events[0]['extendedProps']['visibilitymode']);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CONFIRMED, $events[0]['extendedProps']['bookingstatus']);
    }

    /**
     * Mixed observer/detail assignments must keep the calendar feed on the full
     * projection so privileged users do not receive reserved placeholders.
     *
     * @return void
     */
    public function test_get_events_in_timerange_keeps_full_projection_for_mixed_detail_roles(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $context = $this->create_observer_context_for_user($user->id);

        $detailrole = \create_role('Bookit detail service', 'bookitdetailservicefeed', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $detailrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $detailrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $detailrole, $context->id, true);
        \role_assign($detailrole, $user->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($user);

        $visibleid = $this->create_event_record([
            'name' => 'Admin visible booking',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'starttime' => strtotime('2026-05-09 10:00:00'),
            'endtime' => strtotime('2026-05-09 11:00:00'),
        ]);

        $events = event_manager::get_events_in_timerange('2026-05-09 00:00', '2026-05-09 23:59', (int)$context->instanceid);

        $this->assertCount(1, $events);
        $this->assert_is_canonical_calendar_event($events[0]);
        $this->assertSame($visibleid, (int)$events[0]['id']);
        $this->assertStringContainsString('Admin visible booking', $events[0]['title']);
        $this->assertSame('full', $events[0]['extendedProps']['visibilitymode']);
    }

    /**
     * Semester filtering ignores legacy semester value 0 and matches only explicit positive IDs.
     *
     * @return void
     */
    public function test_filter_overview_events_ignores_legacy_semester_zero(): void {
        $activeevent = (object)[
            'id' => 1,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'starttime' => strtotime('+1 day'),
            'endtime' => strtotime('+1 day +2 hours'),
        ];
        $legacyevent = (object)[
            'id' => 2,
            'semester' => 0,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'starttime' => strtotime('+2 days'),
            'endtime' => strtotime('+2 days +2 hours'),
        ];

        $filtered = event_manager::filter_overview_events([$activeevent, $legacyevent], [
            'semesterids' => [20261],
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame(1, (int)$filtered[0]->id);

        $filteredwithignoredzero = event_manager::filter_overview_events([$activeevent, $legacyevent], [
            'semesterids' => [20261, 0],
        ]);

        $this->assertCount(1, $filteredwithignoredzero);
        $this->assertSame(1, (int)$filteredwithignoredzero[0]->id);

        $legacyonly = event_manager::filter_overview_events([$activeevent, $legacyevent], [
            'semesterids' => [0],
        ]);

        $this->assertCount(2, $legacyonly);
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
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];
        $pastevent = (object)[
            'id' => 12,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
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
            'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CONFIRMED],
            'facultyids' => [1],
            'semesterids' => [20261],
        ], false, $referencetime);
        $history = event_manager::filter_overview_events([$futureevent, $pastevent, $filteredout], [
            'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CONFIRMED],
            'facultyids' => [1],
            'semesterids' => [20261],
        ], true, $referencetime);

        $this->assertCount(1, $active);
        $this->assertSame(11, (int)$active[0]->id);
        $this->assertCount(1, $history);
        $this->assertSame(12, (int)$history[0]->id);
    }

    /**
     * Status transitions preserve booking person in usercreated and set last editor to actor.
     *
     * @return void
     */
    public function test_transition_booking_status_preserves_booking_person_usercreated(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Preserve creator']);
        $context = context_module::instance($bookit->cmid);

        $eventid = $this->create_event_record([
            'name' => 'Preserve booking person',
            'usercreated' => (int)$bookingperson->id,
            'usermodified' => (int)$bookingperson->id,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            (int)$serviceuser->id,
            $context
        );

        $updated = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $this->assertSame((int)$bookingperson->id, (int)$updated->usercreated);
        $this->assertSame((int)$serviceuser->id, (int)$updated->usermodified);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$updated->bookingstatus);
    }

    /**
     * Create a Bookit context with participant capabilities for booking-person visibility tests.
     *
     * @param int[] $participantuserids
     * @return context_module
     */
    private function create_participant_context(array $participantuserids): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Foreign save']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant foreign', 'bookitparticipantforeign', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        foreach ($participantuserids as $userid) {
            \role_assign($roleid, $userid, $context->id);
        }
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Assign a service-team role with form-save capabilities in the module context.
     *
     * @param context_module $context
     * @param int $userid
     * @param string $roleshortname
     * @return void
     */
    private function assign_service_role(
        context_module $context,
        int $userid,
        string $roleshortname = 'bookitserviceforeign'
    ): void {
        global $DB;

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit service foreign', $roleshortname, 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * Foreign form saves must preserve booking person identity and calendar visibility.
     *
     * @param int $actorid
     * @param context_module|null $context
     * @param int|null $bookingpersonid
     * @return void
     */
    private function assert_foreign_save_preserves_booking_person(
        int $actorid,
        ?context_module $context = null,
        ?int $bookingpersonid = null
    ): void {
        global $DB;

        if ($bookingpersonid === null) {
            $bookingperson = $this->getDataGenerator()->create_user();
            $bookingpersonid = (int)$bookingperson->id;
        }
        if ($context === null) {
            $context = $this->create_participant_context([$bookingpersonid]);
        }
        $roomid = $this->create_room();

        $this->setUser($bookingpersonid);
        $event = $this->build_bookit_event($roomid, $bookingpersonid, [
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'name' => 'Foreign save identity',
        ]);
        $saved = event_manager::save_event_with_lifecycle_tracking(
            $event,
            null,
            $bookingpersonid,
            $context
        );

        $before = bookit_event::from_database($saved->id);
        $updated = bookit_event::from_database($saved->id);
        $updated->notes = 'Updated by foreign actor';

        $this->setUser($actorid);
        event_manager::save_event_with_lifecycle_tracking(
            $updated,
            $before,
            $actorid,
            $context
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $saved->id], '*', MUST_EXIST);
        $this->assertSame($bookingpersonid, (int)$persisted->usercreated);
        $this->assertSame($actorid, (int)$persisted->usermodified);

        $this->setUser($bookingpersonid);
        $this->assertTrue(
            event_access_manager::can_user_view_event_in_calendar($persisted, $context, $bookingpersonid)
        );
        $this->assertTrue(
            event_access_manager::can_user_view_event_in_overview($persisted, $context, $bookingpersonid)
        );
    }

    /**
     * Admin foreign saves must not overwrite the booking person stored in usercreated.
     *
     * @return void
     */
    public function test_save_event_preserves_booking_person_usercreated_on_foreign_save(): void {
        $this->resetAfterTest(true);
        $admin = get_admin();
        $this->assert_foreign_save_preserves_booking_person((int)$admin->id);
    }

    /**
     * Service-team foreign saves must not overwrite the booking person stored in usercreated.
     *
     * @return void
     */
    public function test_save_event_preserves_booking_person_usercreated_on_service_team_foreign_save(): void {
        $this->resetAfterTest(true);

        $serviceuser = $this->getDataGenerator()->create_user();
        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context([(int)$bookingperson->id]);
        $this->assign_service_role($context, (int)$serviceuser->id);

        $this->assert_foreign_save_preserves_booking_person(
            (int)$serviceuser->id,
            $context,
            (int)$bookingperson->id
        );
    }

    /**
     * New bookings must store the creating user as booking person in usercreated.
     *
     * @return void
     */
    public function test_save_event_sets_booking_person_usercreated_on_insert(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_bookit_event($roomid, (int)$user->id, ['name' => 'Insert identity']);

        event_manager::save_event_with_lifecycle_tracking($event, null, (int)$user->id, $context);

        $record = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame((int)$user->id, (int)$record->usercreated);
        $this->assertSame((int)$user->id, (int)$record->usermodified);
    }

    /**
     * Booking-person self saves keep usercreated; usermodified stays the booking person on self-save.
     *
     * @return void
     */
    public function test_save_event_preserves_booking_person_usercreated_on_self_save(): void {
        global $DB;

        $this->resetAfterTest(true);
        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context([(int)$bookingperson->id]);
        $roomid = $this->create_room();

        $this->setUser($bookingperson);
        $event = $this->build_bookit_event($roomid, (int)$bookingperson->id, [
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'name' => 'Self save identity',
        ]);
        $saved = event_manager::save_event_with_lifecycle_tracking(
            $event,
            null,
            (int)$bookingperson->id,
            $context
        );

        $before = bookit_event::from_database($saved->id);
        $updated = bookit_event::from_database($saved->id);
        $updated->notes = 'Updated by booking person';

        event_manager::save_event_with_lifecycle_tracking(
            $updated,
            $before,
            (int)$bookingperson->id,
            $context
        );

        $record = $DB->get_record('bookit_event', ['id' => $saved->id], '*', MUST_EXIST);
        $this->assertSame((int)$bookingperson->id, (int)$record->usercreated);
        $this->assertSame((int)$bookingperson->id, (int)$record->usermodified);
    }

    /**
     * Personal overview queries must still return bookings after service-team status transitions.
     *
     * @return void
     */
    public function test_filter_overview_events_keeps_booking_person_events_after_status_transition(): void {
        global $DB;

        $this->resetAfterTest(true);

        $bookingperson = $this->getDataGenerator()->create_user();
        $serviceuser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Overview transition']);
        $context = context_module::instance($bookit->cmid);

        $eventid = $this->create_event_record([
            'name' => 'Overview after transition',
            'usercreated' => (int)$bookingperson->id,
            'usermodified' => (int)$bookingperson->id,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            (int)$serviceuser->id,
            $context
        );

        $personalevents = array_values(event_manager::get_events_for_examiner((int)$bookingperson->id));
        $this->assertCount(1, $personalevents);
        $this->assertSame($eventid, (int)$personalevents[0]->id);

        $filtered = array_values(event_manager::filter_overview_events(
            $personalevents,
            [],
            false,
            strtotime('2026-05-07 10:00:00')
        ));
        $this->assertCount(1, $filtered);
        $this->assertSame($eventid, (int)$filtered[0]->id);
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
            'usercreated' => $user->id,
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
     * Creating a booking through the lifecycle path must send the initial New notification.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_sends_notification_for_initial_creation(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_bookit_event($roomid, (int)$user->id, ['name' => 'Created notification']);

        $sink = $this->redirectMessages();
        event_manager::save_event_with_lifecycle_tracking(
            $event,
            null,
            (int)$user->id,
            $context,
            (int)$context->instanceid
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertSame((string)$user->id, $messages[0]->useridto);
        $this->assertStringContainsString('Created notification', $messages[0]->subject);
    }

    /**
     * Reactivating a canceled request back to New through the lifecycle path must send a notification.
     *
     * @return void
     */
    public function test_save_event_with_lifecycle_tracking_notifies_on_reactivation_to_new(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_bookit_event($roomid, (int)$user->id, [
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'name' => 'Reactivated notification',
        ]);

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, (int)$user->id, $context);

        $beforecancel = bookit_event::from_database($saved->id);
        $canceled = bookit_event::from_database($saved->id);
        $canceled->bookingstatus = event_access_manager::BOOKINGSTATUS_CANCELED;
        event_manager::save_event_with_lifecycle_tracking($canceled, $beforecancel, (int)$user->id, $context);

        $beforerestore = bookit_event::from_database($saved->id);
        $reactivated = bookit_event::from_database($saved->id);
        $reactivated->bookingstatus = event_access_manager::BOOKINGSTATUS_NEW;

        $sink = $this->redirectMessages();
        event_manager::save_event_with_lifecycle_tracking(
            $reactivated,
            $beforerestore,
            (int)$user->id,
            $context,
            (int)$context->instanceid
        );
        $messages = $sink->get_messages();
        $sink->close();

        $history = array_values(event_manager::get_booking_history($saved->id));

        $this->assertCount(1, $messages);
        $this->assertSame((string)$user->id, $messages[0]->useridto);
        $this->assertSame('restored', $history[0]->action);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$history[0]->newstatus);
    }

    /**
     * An empty optional service-team group must not block status delivery to the remaining recipients.
     *
     * @return void
     */
    public function test_transition_booking_status_keeps_delivery_when_service_team_group_is_empty(): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $context = $this->create_bookit_context();
        $roomid = $this->create_room();
        $event = $this->build_bookit_event($roomid, (int)$user->id, ['name' => 'No service team available']);
        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, (int)$user->id, $context);
        $record = event_manager::get_event((int)$saved->id);

        $sink = $this->redirectMessages();
        event_manager::transition_booking_status(
            $record,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            (int)$user->id,
            $context,
            (int)$context->instanceid
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertSame((string)$user->id, $messages[0]->useridto);
    }

    /**
     * Rejected requests remain in the trash queue regardless of whether the requested slot is past or future.
     *
     * @return void
     */
    public function test_rejectedcancelled_workspace_keeps_past_and_future_items(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();
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
            'usercreated' => $user->id,
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
        $pastid = $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Past rejected',
            'starttime' => strtotime('2026-05-01 09:00:00'),
            'endtime' => strtotime('2026-05-01 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]));

        $result = $this->get_workspace_query_result($context, $adminid, 'rejectedcancelled');
        $rejected = $result['events'];
        $this->assertCount(2, $rejected);
        $this->assertSame([$futureid, $pastid], array_map(static fn($event): int => (int)$event->id, $rejected));
        $this->assertSame(2, $result['count']);
        $this->assertSame(2, event_manager::count_rejected_requests());
    }

    /**
     * Accepted requests expose operative accepted bookings outside history.
     *
     * @return void
     */
    public function test_confirmedrequests_workspace_returns_operative_accepted_events(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $referencetime = time();

        $acceptedid = $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Future accepted',
            'starttime' => strtotime('+2 days 09:00'),
            'endtime' => strtotime('+2 days 11:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
        ]));

        $accepted = $this->get_workspace_query_result($context, $adminid, 'confirmedrequests')['events'];
        $this->assertCount(1, $accepted);
        $this->assertSame($acceptedid, (int)$accepted[0]->id);
        $this->assertSame(1, event_manager::count_confirmed_requests($referencetime));
    }

    /**
     * Accepted requests exclude canceled and history bookings.
     *
     * @return void
     */
    public function test_confirmedrequests_workspace_excludes_canceled_and_history_events(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $referencetime = time();

        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Past accepted',
            'starttime' => strtotime('-3 days 09:00'),
            'endtime' => strtotime('-3 days 11:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Canceled accepted',
            'starttime' => strtotime('+2 days 09:00'),
            'endtime' => strtotime('+2 days 11:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
        ]));

        $accepted = $this->get_workspace_query_result($context, $adminid, 'confirmedrequests')['events'];
        $this->assertCount(0, $accepted);
        $this->assertSame(0, event_manager::count_confirmed_requests($referencetime));
    }

    /**
     * Open request counts stay limited to actionable New and In progress bookings.
     *
     * @return void
     */
    public function test_count_open_requests_only_counts_new_and_in_progress(): void {
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'starttime' => strtotime('+2 days 09:00'),
            'endtime' => strtotime('+2 days 11:00'),
        ];

        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'New request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'In progress request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Accepted request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
        ]));

        $this->assertSame(2, event_manager::count_open_requests());
        $this->assertSame(1, event_manager::count_new_requests());
        $this->assertSame(1, event_manager::count_in_progress_requests());
    }

    /**
     * New request counter only includes bookings in status New.
     *
     * @return void
     */
    public function test_count_new_requests_only_counts_new_status(): void {
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'starttime' => strtotime('+2 days 09:00'),
            'endtime' => strtotime('+2 days 11:00'),
        ];

        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'New request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'In progress request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Accepted request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
        ]));

        $this->assertSame(1, event_manager::count_new_requests());
    }

    /**
     * In progress request counter only includes bookings in status In progress.
     *
     * @return void
     */
    public function test_count_in_progress_requests_only_counts_in_progress_status(): void {
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'starttime' => strtotime('+2 days 09:00'),
            'endtime' => strtotime('+2 days 11:00'),
        ];

        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'New request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'In progress request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Accepted request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
        ]));

        $this->assertSame(1, event_manager::count_in_progress_requests());
    }

    /**
     * Open request reads must not include accepted bookings.
     *
     * @return void
     */
    public function test_openrequests_workspace_excludes_accepted_bookings(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];

        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Open request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]));
        $DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Accepted request',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
        ]));

        $open = $this->get_workspace_query_result($context, $adminid, 'openrequests')['events'];
        $this->assertCount(1, $open);
        $this->assertSame('Open request', $open[0]->name);
    }

    /**
     * Open requests must be ordered newest start time first.
     *
     * @return void
     */
    public function test_openrequests_workspace_orders_by_starttime_desc(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ];

        $olderid = (int)$DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Older request',
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ]));
        $newerid = (int)$DB->insert_record('bookit_event', (object)($common + [
            'name' => 'Newer request',
            'starttime' => strtotime('2026-05-10 09:00:00'),
            'endtime' => strtotime('2026-05-10 11:00:00'),
        ]));

        $open = $this->get_workspace_query_result($context, $adminid, 'openrequests')['events'];
        $this->assertCount(2, $open);
        $this->assertSame($newerid, (int)$open[0]->id);
        $this->assertSame($olderid, (int)$open[1]->id);
    }

    /**
     * Rejected requests must leave active overview projections immediately.
     *
     * @return void
     */
    public function test_filter_overview_events_hides_rejected_requests_from_active_projection(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $accepted = (object)[
            'id' => 101,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];
        $rejected = (object)[
            'id' => 102,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'starttime' => strtotime('2026-05-08 12:00:00'),
            'endtime' => strtotime('2026-05-08 14:00:00'),
        ];

        $active = event_manager::filter_overview_events([$accepted, $rejected], [], false, $referencetime);

        $this->assertCount(1, $active);
        $this->assertSame(101, (int)$active[0]->id);
        $this->assertTrue(event_manager::is_hidden_from_active_overview($rejected));

        $explicitrejected = event_manager::filter_overview_events(
            [$accepted, $rejected],
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_REJECTED]],
            false,
            $referencetime
        );
        $this->assertCount(1, $explicitrejected);
        $this->assertSame(102, (int)$explicitrejected[0]->id);
    }

    /**
     * Explicit Canceled selection on the active tab must return canceled rows.
     *
     * @return void
     */
    public function test_filter_overview_events_explicit_canceled_on_active_tab(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $canceled = (object)[
            'id' => 201,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];

        $filtered = event_manager::filter_overview_events(
            [$canceled],
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CANCELED]],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(201, (int)$filtered[0]->id);
    }

    /**
     * Explicit Rejected selection on the active tab must return rejected rows.
     *
     * @return void
     */
    public function test_filter_overview_events_explicit_rejected_on_active_tab(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $rejected = (object)[
            'id' => 202,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'starttime' => strtotime('2026-05-08 12:00:00'),
            'endtime' => strtotime('2026-05-08 14:00:00'),
        ];

        $filtered = event_manager::filter_overview_events(
            [$rejected],
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_REJECTED]],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(202, (int)$filtered[0]->id);
    }

    /**
     * Canceled-only filter must not return active operational statuses.
     *
     * @return void
     */
    public function test_filter_overview_events_canceled_only_excludes_active_statuses(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $events = [
            (object)[
                'id' => 301,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'starttime' => strtotime('2026-05-08 09:00:00'),
                'endtime' => strtotime('2026-05-08 11:00:00'),
            ],
            (object)[
                'id' => 302,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                'starttime' => strtotime('2026-05-08 12:00:00'),
                'endtime' => strtotime('2026-05-08 14:00:00'),
            ],
            (object)[
                'id' => 303,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'starttime' => strtotime('2026-05-08 15:00:00'),
                'endtime' => strtotime('2026-05-08 17:00:00'),
            ],
            (object)[
                'id' => 304,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'starttime' => strtotime('2026-05-08 18:00:00'),
                'endtime' => strtotime('2026-05-08 20:00:00'),
            ],
        ];

        $filtered = event_manager::filter_overview_events(
            $events,
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CANCELED]],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(304, (int)$filtered[0]->id);
    }

    /**
     * Mixed New and Canceled selection returns both types on the active tab.
     *
     * @return void
     */
    public function test_filter_overview_events_new_and_canceled_mixed_on_active(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $newevent = (object)[
            'id' => 311,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
        ];
        $canceled = (object)[
            'id' => 312,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('2026-05-08 12:00:00'),
            'endtime' => strtotime('2026-05-08 14:00:00'),
        ];

        $filtered = event_manager::filter_overview_events(
            [$newevent, $canceled],
            [
                'bookingstatuses' => [
                    event_access_manager::BOOKINGSTATUS_NEW,
                    event_access_manager::BOOKINGSTATUS_CANCELED,
                ],
            ],
            false,
            $referencetime
        );

        $this->assertCount(2, $filtered);
        $this->assertEqualsCanonicalizing([311, 312], array_map(static fn($event): int => (int)$event->id, $filtered));
    }

    /**
     * Rejected-only filter must not return active operational statuses.
     *
     * @return void
     */
    public function test_filter_overview_events_rejected_only_excludes_active_statuses(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $events = [
            (object)[
                'id' => 401,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'starttime' => strtotime('2026-05-08 09:00:00'),
                'endtime' => strtotime('2026-05-08 11:00:00'),
            ],
            (object)[
                'id' => 402,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'starttime' => strtotime('2026-05-08 12:00:00'),
                'endtime' => strtotime('2026-05-08 14:00:00'),
            ],
        ];

        $filtered = event_manager::filter_overview_events(
            $events,
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_REJECTED]],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(402, (int)$filtered[0]->id);
    }

    /**
     * Selecting all five statuses returns active and terminal rows on the active tab.
     *
     * @return void
     */
    public function test_filter_overview_events_all_five_statuses_on_active(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $events = [
            (object)[
                'id' => 501,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'starttime' => strtotime('2026-05-08 09:00:00'),
                'endtime' => strtotime('2026-05-08 10:00:00'),
            ],
            (object)[
                'id' => 502,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                'starttime' => strtotime('2026-05-08 10:00:00'),
                'endtime' => strtotime('2026-05-08 11:00:00'),
            ],
            (object)[
                'id' => 503,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'starttime' => strtotime('2026-05-08 11:00:00'),
                'endtime' => strtotime('2026-05-08 12:00:00'),
            ],
            (object)[
                'id' => 504,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'starttime' => strtotime('2026-05-08 12:00:00'),
                'endtime' => strtotime('2026-05-08 13:00:00'),
            ],
            (object)[
                'id' => 505,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'starttime' => strtotime('2026-05-08 13:00:00'),
                'endtime' => strtotime('2026-05-08 14:00:00'),
            ],
        ];

        $filtered = event_manager::filter_overview_events(
            $events,
            [
                'bookingstatuses' => [
                    event_access_manager::BOOKINGSTATUS_NEW,
                    event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                    event_access_manager::BOOKINGSTATUS_CONFIRMED,
                    event_access_manager::BOOKINGSTATUS_CANCELED,
                    event_access_manager::BOOKINGSTATUS_REJECTED,
                ],
            ],
            false,
            $referencetime
        );

        $this->assertCount(5, $filtered);
        $this->assertEqualsCanonicalizing(
            [501, 502, 503, 504, 505],
            array_map(static fn($event): int => (int)$event->id, $filtered)
        );
    }

    /**
     * Default triplet on the active tab must hide terminal rows.
     *
     * @return void
     */
    public function test_filter_overview_events_default_triplet_hides_terminal_on_active(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $events = [
            (object)[
                'id' => 601,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'starttime' => strtotime('2026-05-08 09:00:00'),
                'endtime' => strtotime('2026-05-08 11:00:00'),
            ],
            (object)[
                'id' => 602,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'starttime' => strtotime('2026-05-08 12:00:00'),
                'endtime' => strtotime('2026-05-08 14:00:00'),
            ],
            (object)[
                'id' => 603,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'starttime' => strtotime('2026-05-08 15:00:00'),
                'endtime' => strtotime('2026-05-08 17:00:00'),
            ],
        ];

        $filtered = event_manager::filter_overview_events(
            $events,
            [
                'bookingstatuses' => [
                    event_access_manager::BOOKINGSTATUS_NEW,
                    event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                    event_access_manager::BOOKINGSTATUS_CONFIRMED,
                ],
            ],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(603, (int)$filtered[0]->id);
    }

    /**
     * Terminal-only selection returns only canceled and rejected rows on the active tab.
     *
     * @return void
     */
    public function test_filter_overview_events_terminal_only_both_on_active(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $events = [
            (object)[
                'id' => 701,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'starttime' => strtotime('2026-05-08 09:00:00'),
                'endtime' => strtotime('2026-05-08 11:00:00'),
            ],
            (object)[
                'id' => 702,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'starttime' => strtotime('2026-05-08 12:00:00'),
                'endtime' => strtotime('2026-05-08 14:00:00'),
            ],
            (object)[
                'id' => 703,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'starttime' => strtotime('2026-05-08 15:00:00'),
                'endtime' => strtotime('2026-05-08 17:00:00'),
            ],
        ];

        $filtered = event_manager::filter_overview_events(
            $events,
            [
                'bookingstatuses' => [
                    event_access_manager::BOOKINGSTATUS_CANCELED,
                    event_access_manager::BOOKINGSTATUS_REJECTED,
                ],
            ],
            false,
            $referencetime
        );

        $this->assertCount(2, $filtered);
        $this->assertEqualsCanonicalizing([702, 703], array_map(static fn($event): int => (int)$event->id, $filtered));
    }

    /**
     * Spec 062 alias: participant explicit Canceled on active tab.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_explicit_canceled_on_active(): void {
        $this->test_filter_overview_events_explicit_canceled_on_active_tab();
    }

    /**
     * Spec 062 alias: participant canceled-only filter excludes active statuses.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_canceled_only_excludes_active(): void {
        $this->test_filter_overview_events_canceled_only_excludes_active_statuses();
    }

    /**
     * Spec 062 alias: participant mixed New and Canceled selection.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_new_and_canceled_mixed(): void {
        $this->test_filter_overview_events_new_and_canceled_mixed_on_active();
    }

    /**
     * Spec 062 alias: participant rejected-only filter excludes active statuses.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_rejected_only_excludes_active(): void {
        $this->test_filter_overview_events_rejected_only_excludes_active_statuses();
    }

    /**
     * Spec 062 alias: participant all five statuses on active tab.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_all_five_statuses_on_active(): void {
        $this->test_filter_overview_events_all_five_statuses_on_active();
    }

    /**
     * Spec 062 alias: participant default triplet hides terminal statuses.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_default_triplet_hides_terminal(): void {
        $this->test_filter_overview_events_default_triplet_hides_terminal_on_active();
    }

    /**
     * Spec 062 alias: participant terminal-only combined filter.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_terminal_only_both(): void {
        $this->test_filter_overview_events_terminal_only_both_on_active();
    }

    /**
     * Examiner fixtures must return canceled rows when only Canceled is selected.
     *
     * @return void
     */
    public function test_filter_overview_events_examiner_explicit_canceled_on_active(): void {
        $referencetime = strtotime('2026-06-01 10:00:00');
        $canceled = (object)[
            'id' => 801,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('2026-06-10 09:00:00'),
            'endtime' => strtotime('2026-06-10 11:00:00'),
            'personinchargeid' => 42,
            'usermodified' => 99,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $filtered = event_manager::filter_overview_events(
            [$canceled],
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CANCELED]],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(801, (int)$filtered[0]->id);
    }

    /**
     * Examiner fixtures must return rejected rows when only Rejected is selected.
     *
     * @return void
     */
    public function test_filter_overview_events_examiner_explicit_rejected_on_active(): void {
        $referencetime = strtotime('2026-06-01 10:00:00');
        $rejected = (object)[
            'id' => 802,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'starttime' => strtotime('2026-06-11 09:00:00'),
            'endtime' => strtotime('2026-06-11 11:00:00'),
            'personinchargeid' => 42,
            'usermodified' => 99,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];

        $filtered = event_manager::filter_overview_events(
            [$rejected],
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_REJECTED]],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(802, (int)$filtered[0]->id);
    }

    /**
     * Explicit Canceled filter must still honour semester constraints.
     *
     * @return void
     */
    public function test_filter_overview_events_participant_canceled_outside_semester_hidden(): void {
        $referencetime = strtotime('2026-06-01 10:00:00');
        $insamesemester = (object)[
            'id' => 901,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('2026-06-10 09:00:00'),
            'endtime' => strtotime('2026-06-10 11:00:00'),
        ];
        $outsidesemester = (object)[
            'id' => 902,
            'semester' => 20262,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('2026-11-10 09:00:00'),
            'endtime' => strtotime('2026-11-10 11:00:00'),
        ];

        $filtered = event_manager::filter_overview_events(
            [$insamesemester, $outsidesemester],
            [
                'bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CANCELED],
                'semesterids' => [20261],
            ],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
        $this->assertSame(901, (int)$filtered[0]->id);
    }

    /**
     * Reset simulation: missing bookingstatusfilter resolves to default triplet.
     *
     * @return void
     */
    public function test_resolve_overview_booking_status_filter_ids_reset_to_triplet(): void {
        $this->test_resolve_overview_booking_status_filter_ids_default_when_implicit();
    }

    /**
     * Reactivated requests move from the rejected queue back to the open queue counts.
     *
     * @return void
     */
    public function test_reactivating_rejected_request_updates_queue_counts(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Bookit queue test']);
        $context = context_module::instance($bookit->cmid);
        $referencetime = strtotime('2026-05-07 10:00:00');

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Reactivatable request',
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
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        $this->assertSame(0, event_manager::count_open_requests());
        $this->assertSame(1, event_manager::count_rejected_requests());

        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$user->id,
            $context
        );

        $this->assertSame(1, event_manager::count_open_requests());
        $this->assertSame(0, event_manager::count_rejected_requests());

        $DB->set_field(
            'bookit_event',
            'bookingstatus',
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            ['id' => $eventid]
        );
        $this->assertSame(1, event_manager::count_open_requests());
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
            'usercreated' => $user->id,
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

    /**
     * Restore requests must resolve to the last valid state stored before cancellation.
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_resolve_requested_booking_status_uses_last_pre_cancel_state(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Restorable event',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'canceled',
            (int)$user->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            ['bookingstatus' => ['from' => 2, 'to' => 3]]
        );

        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $this->assertSame(
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            event_manager::resolve_requested_booking_status($event, event_access_manager::BOOKINGSTATUS_NEW)
        );
    }

    /**
     * Self-cancelled New requests must leave active overview projections immediately.
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_filter_overview_events_hides_self_cancelled_new_requests_from_active_view(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Cancelled request',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        event_manager::record_booking_history(
            $eventid,
            'self_canceled',
            (int)$user->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            ['bookingstatus' => ['from' => 0, 'to' => 3]]
        );

        $visible = event_manager::filter_overview_events([
            (object)[
                'id' => $eventid,
                'semester' => 20261,
                'institutionid' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
                'starttime' => strtotime('2026-05-08 09:00:00'),
                'endtime' => strtotime('2026-05-08 11:00:00'),
            ],
        ], [], false, strtotime('2026-05-07 10:00:00'));

        $this->assertSame([], $visible);
    }

    /**
     * Self-cancelled New requests must keep a distinct history action from service-team cancellations.
     *
     * @return void
     */
    public function test_transition_booking_status_marks_requester_self_cancel_distinctly(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Self cancel']);
        $context = context_module::instance($bookit->cmid);
        $requester = $this->getDataGenerator()->create_user();

        $participantrole = \create_role('Bookit participant', 'bookitparticipant', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $participantrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $participantrole, $context->id, true);
        \role_assign($participantrole, $requester->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($requester);

        $eventid = $this->create_event_record([
            'name' => 'Requester self cancel',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'personinchargeid' => $requester->id,
            'usercreated' => $requester->id,
            'usermodified' => $requester->id,
        ]);
        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);

        event_manager::transition_booking_status(
            $event,
            event_access_manager::BOOKINGSTATUS_CANCELED,
            (int)$requester->id,
            $context
        );

        $history = array_values(event_manager::get_booking_history($eventid));
        $this->assertCount(1, $history);
        $this->assertSame('self_canceled', $history[0]->action);
        $this->assertSame((int)$requester->id, (int)$history[0]->usermodified);
        $this->assertTrue(event_manager::is_hidden_from_active_overview(
            $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST)
        ));
    }

    /**
     * Canceled bookings must be classified as history regardless of their end time.
     *
     * @return void
     */
    public function test_is_event_in_history_treats_canceled_as_history(): void {
        $canceled = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'endtime' => strtotime('+2 days'),
        ];

        $this->assertTrue(event_manager::is_event_in_history($canceled));
    }

    /**
     * Rejected bookings must be classified as history regardless of their end time.
     *
     * @return void
     */
    public function test_is_event_in_history_treats_rejected_as_history(): void {
        $rejected = (object)[
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'endtime' => strtotime('+2 days'),
        ];

        $this->assertTrue(event_manager::is_event_in_history($rejected));
    }

    /**
     * History overview filtering must still include canceled bookings.
     *
     * @return void
     */
    public function test_filter_overview_events_includes_canceled_in_history_view(): void {
        $referencetime = time();
        $canceled = (object)[
            'id' => 21,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('+2 days'),
            'endtime' => strtotime('+2 days +2 hours'),
        ];

        $active = event_manager::filter_overview_events([$canceled], [], false, $referencetime);
        $history = event_manager::filter_overview_events([$canceled], [], true, $referencetime);

        $this->assertCount(0, $active);
        $this->assertCount(1, $history);
        $this->assertSame(21, (int)$history[0]->id);
    }

    /**
     * History overview filtering must include rejected bookings with future end times.
     *
     * @return void
     */
    public function test_filter_overview_events_includes_rejected_in_history_view(): void {
        $referencetime = time();
        $rejected = (object)[
            'id' => 22,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
            'starttime' => strtotime('+2 days'),
            'endtime' => strtotime('+2 days +2 hours'),
        ];

        $active = event_manager::filter_overview_events([$rejected], [], false, $referencetime);
        $history = event_manager::filter_overview_events([$rejected], [], true, $referencetime);

        $this->assertCount(0, $active);
        $this->assertCount(1, $history);
        $this->assertSame(22, (int)$history[0]->id);
    }

    /**
     * Booking status labels resolve through central lang strings (Title Case).
     *
     * @return void
     */
    public function test_get_booking_status_label_uses_title_case_lang_strings(): void {
        $this->resetAfterTest();

        $this->assertSame(get_string('event_bookingstatus_0', 'mod_bookit'), event_manager::get_booking_status_label(0));
        $this->assertSame('New', event_manager::get_booking_status_label(event_access_manager::BOOKINGSTATUS_NEW));
    }

    /**
     * Booking status group keys must follow open / confirmed / closed.
     *
     * @return void
     */
    public function test_get_booking_status_group_key(): void {
        $this->resetAfterTest();

        $this->assertSame('open', event_manager::get_booking_status_group_key(
            event_access_manager::BOOKINGSTATUS_NEW
        ));
        $this->assertSame('open', event_manager::get_booking_status_group_key(
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS
        ));
        $this->assertSame('confirmed', event_manager::get_booking_status_group_key(
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        ));
        $this->assertSame('closed', event_manager::get_booking_status_group_key(
            event_access_manager::BOOKINGSTATUS_CANCELED
        ));
        $this->assertSame('closed', event_manager::get_booking_status_group_key(
            event_access_manager::BOOKINGSTATUS_REJECTED
        ));
    }

    /**
     * Group labels resolve through overview_status_group lang strings.
     *
     * @return void
     */
    public function test_get_booking_status_group_label(): void {
        $this->resetAfterTest();

        $this->assertSame(
            get_string('overview_status_group_open', 'mod_bookit'),
            event_manager::get_booking_status_group_label(event_access_manager::BOOKINGSTATUS_NEW)
        );
        $this->assertSame(
            get_string('overview_status_group_confirmed', 'mod_bookit'),
            event_manager::get_booking_status_group_label(event_access_manager::BOOKINGSTATUS_CONFIRMED)
        );
    }

    /**
     * Booking status definition exposes registry shape and canonical labels.
     *
     * @return void
     */
    public function test_get_booking_status_definition(): void {
        $this->resetAfterTest();

        $definition = event_manager::get_booking_status_definition(
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );

        $this->assertSame(event_access_manager::BOOKINGSTATUS_CONFIRMED, $definition['value']);
        $this->assertSame('Confirmed', $definition['label']);
        $this->assertSame('confirmed', $definition['groupkey']);
        $this->assertNotEmpty($definition['grouplabel']);
        $this->assertArrayHasKey('bg', $definition['colors']);
        $this->assertArrayHasKey('fg', $definition['colors']);
        $this->assertSame('bookit-bookingstatus-2', $definition['cssclass']);
    }

    /**
     * Resource status labels map to canonical booking vocabulary.
     *
     * @return void
     */
    public function test_get_resource_status_label(): void {
        $this->resetAfterTest();

        $this->assertSame('New', event_manager::get_resource_status_label('requested'));
        $this->assertSame('Confirmed', event_manager::get_resource_status_label('confirmed'));
        $this->assertSame('In Progress', event_manager::get_resource_status_label('inprogress'));
        $this->assertSame('Rejected', event_manager::get_resource_status_label('rejected'));
    }

    /**
     * All requests workspace profile exposes assignment filter for service team.
     *
     * @return void
     */
    public function test_get_workspace_filter_profile_allrequests_shows_assignment_and_no_legacy_semester(): void {
        $profile = event_manager::get_workspace_filter_profile('allrequests', true);

        $this->assertTrue($profile['show_assignment_filter']);
        $this->assertArrayNotHasKey('include_legacy_semester_option', $profile);
        $this->assertTrue($profile['show_status_filter']);
    }

    /**
     * Queue workspace tabs expose reporting filters without Booking status.
     *
     * @return void
     */
    public function test_get_workspace_filter_profile_queue_tabs_show_reporting_without_status(): void {
        foreach (['openrequests', 'confirmedrequests', 'rejectedcancelled'] as $tab) {
            $profile = event_manager::get_workspace_filter_profile($tab, true);
            $this->assertTrue($profile['show_reporting_filters'], $tab);
            $this->assertTrue($profile['show_semester_filter'], $tab);
            $this->assertFalse($profile['show_status_filter'], $tab);
            $this->assertFalse($profile['show_assignment_filter'], $tab);
        }
    }

    /**
     * History workspace profile exposes reporting filters without assignment filter.
     *
     * @return void
     */
    public function test_get_workspace_filter_profile_history_exposes_reporting_filters(): void {
        $profile = event_manager::get_workspace_filter_profile('history', true);

        $this->assertArrayNotHasKey('include_legacy_semester_option', $profile);
        $this->assertFalse($profile['show_assignment_filter']);
        $this->assertTrue($profile['show_semester_filter']);
    }

    /**
     * Semester filter options must not expose legacy value 0.
     *
     * @return void
     */
    public function test_get_semester_filter_options_excludes_legacy_zero_key(): void {
        $options = event_manager::get_semester_filter_options(strtotime('2026-05-07 10:00:00'));
        $this->assertArrayNotHasKey(0, $options);
        $this->assertNotEmpty($options);
    }

    /**
     * Explicit empty semester selection must include events from multiple semesters.
     *
     * @return void
     */
    public function test_filter_overview_events_explicit_empty_semester_includes_all_terms(): void {
        $referencetime = strtotime('2026-05-07 10:00:00');
        $summer = (object)[
            'id' => 1001,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-06-10 09:00:00'),
            'endtime' => strtotime('2026-06-10 11:00:00'),
        ];
        $winter = (object)[
            'id' => 1002,
            'semester' => 20262,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => strtotime('2026-11-10 09:00:00'),
            'endtime' => strtotime('2026-11-10 11:00:00'),
        ];

        $filtered = event_manager::filter_overview_events(
            [$summer, $winter],
            [
                'bookingstatuses' => event_manager::get_reporting_default_booking_status_filter(),
                'semesterids' => [],
            ],
            false,
            $referencetime
        );

        $this->assertCount(2, $filtered);
    }

    /**
     * Workspace semester options must not include legacy value 0.
     *
     * @return void
     */
    public function test_reporting_semester_options_allrequests_exclude_legacy_zero(): void {
        $options = event_manager::get_semester_filter_options(strtotime('2026-05-07 10:00:00'));
        $this->assertArrayNotHasKey(0, $options);
    }

    /**
     * Assigned filter keeps events where the user is creator, PIC, other examiner, or support.
     *
     * @return void
     */
    public function test_filter_events_by_assignment_assigned_includes_involvement_roles(): void {
        $userid = 42;
        $ascreator = (object)[
            'id' => 1,
            'usercreated' => 42,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];
        $aspic = (object)[
            'id' => 2,
            'usercreated' => 1,
            'personinchargeid' => 42,
            'otherexaminers' => '',
            'supportpersons' => '',
        ];
        $asother = (object)[
            'id' => 3,
            'usercreated' => 1,
            'personinchargeid' => 0,
            'otherexaminers' => '41,42',
            'supportpersons' => '',
        ];
        $assupport = (object)[
            'id' => 4,
            'usercreated' => 1,
            'personinchargeid' => 0,
            'otherexaminers' => '',
            'supportpersons' => '41,42,43',
        ];
        $uninvolved = (object)[
            'id' => 5,
            'usercreated' => 1,
            'personinchargeid' => 2,
            'otherexaminers' => '99',
            'supportpersons' => '99',
        ];

        $filtered = event_manager::filter_events_by_assignment(
            [$ascreator, $aspic, $asother, $assupport, $uninvolved],
            $userid,
            'assigned'
        );

        $this->assertSame([1, 2, 3, 4], array_map(static fn($event): int => (int)$event->id, $filtered));
    }

    /**
     * Rejected and cancelled service-team default spans the full calendar year.
     *
     * @return void
     */
    public function test_get_reporting_default_range_rejected_full_calendar_year(): void {
        $reference = strtotime('2026-05-07 10:00:00');
        [$start, $end] = event_manager::get_reporting_default_range($reference, true, false, true);

        $this->assertSame(strtotime('2026-01-01 00:00:00'), $start);
        $this->assertSame(strtotime('2026-12-31 23:59:59'), $end);
    }

    /**
     * All requests service default stays today → year-end when rejected flag is false.
     *
     * @return void
     */
    public function test_get_reporting_default_range_allrequests_untouched_by_rejected_flag(): void {
        $reference = strtotime('2026-05-07 10:00:00');
        [$start, $end] = event_manager::get_reporting_default_range($reference, true, false, false);

        $this->assertSame(usergetmidnight($reference), $start);
        $this->assertSame(strtotime('2026-12-31 23:59:59'), $end);
    }

    /**
     * Open-requests query honours report date range while keeping open status membership.
     *
     * @return void
     */
    public function test_openrequests_workspace_query_honours_date_range(): void {
        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();

        $insideid = $this->create_event_record([
            'name' => 'Open inside range',
            'semester' => 20261,
            'starttime' => strtotime('2026-06-10 09:00:00'),
            'endtime' => strtotime('2026-06-10 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]);
        $outsideid = $this->create_event_record([
            'name' => 'Open outside range',
            'semester' => 20261,
            'starttime' => strtotime('2026-08-10 09:00:00'),
            'endtime' => strtotime('2026-08-10 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
        ]);

        $result = $this->get_workspace_query_result(
            $context,
            $adminid,
            'openrequests',
            [],
            strtotime('2026-06-01 00:00:00'),
            strtotime('2026-06-30 23:59:59')
        );
        $ids = array_map(static fn($event): int => (int)$event->id, $result['events']);

        $this->assertContains($insideid, $ids);
        $this->assertNotContains($outsideid, $ids);
    }

    /**
     * Rejected query with full-year defaults excludes out-of-year rejected fixtures.
     *
     * @return void
     */
    public function test_rejectedcancelled_workspace_query_honours_year_range(): void {
        $this->resetAfterTest(true);
        [$context, $adminid] = $this->create_admin_workspace_context();

        $inyear = $this->create_event_record([
            'name' => 'Rejected in 2026',
            'semester' => 20261,
            'starttime' => strtotime('2026-03-10 09:00:00'),
            'endtime' => strtotime('2026-03-10 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]);
        $outyear = $this->create_event_record([
            'name' => 'Rejected in 2025',
            'semester' => 20252,
            'starttime' => strtotime('2025-06-10 09:00:00'),
            'endtime' => strtotime('2025-06-10 11:00:00'),
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
        ]);

        [$start, $end] = event_manager::get_reporting_default_range(
            strtotime('2026-05-07 10:00:00'),
            true,
            false,
            true
        );
        $result = $this->get_workspace_query_result(
            $context,
            $adminid,
            'rejectedcancelled',
            [],
            $start,
            $end
        );
        $ids = array_map(static fn($event): int => (int)$event->id, $result['events']);

        $this->assertContains($inyear, $ids);
        $this->assertNotContains($outyear, $ids);
    }

    /**
     * Service-team All requests defaults to the reporting triplet when implicit.
     *
     * @return void
     */
    public function test_workspace_allrequests_default_status_triplet(): void {
        $this->assertSame(
            event_manager::get_reporting_default_booking_status_filter(),
            event_manager::resolve_overview_booking_status_filter_ids([], false, false, false)
        );
    }

    /**
     * Explicit terminal statuses on All requests must remain selectable.
     *
     * @return void
     */
    public function test_workspace_allrequests_explicit_terminal_status_includes_canceled_rejected(): void {
        $referencetime = strtotime('2026-06-01 10:00:00');
        $canceled = (object)[
            'id' => 1101,
            'semester' => 20261,
            'institutionid' => 1,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            'starttime' => strtotime('2026-06-10 09:00:00'),
            'endtime' => strtotime('2026-06-10 11:00:00'),
        ];

        $filtered = event_manager::filter_overview_events(
            [$canceled],
            ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CANCELED]],
            false,
            $referencetime
        );

        $this->assertCount(1, $filtered);
    }

    /**
     * Service-team History defaults to all statuses and year-to-date range.
     *
     * @return void
     */
    public function test_workspace_history_default_status_and_dates(): void {
        $referencetime = strtotime('2026-06-15 10:00:00');
        $this->assertSame(
            event_manager::get_history_default_booking_status_filter(),
            event_manager::resolve_overview_booking_status_filter_ids([], false, true, true)
        );
        [$start, $end] = event_manager::get_reporting_default_range($referencetime, true, true);
        $this->assertSame('2026-01-01', date('Y-m-d', $start));
        $this->assertSame('2026-06-15', date('Y-m-d', $end));
    }

    /**
     * Service-team All requests date range starts today and ends at year end.
     *
     * @return void
     */
    public function test_workspace_allrequests_default_date_range_today_to_year_end(): void {
        $referencetime = strtotime('2026-06-15 10:00:00');
        [$start, $end] = event_manager::get_reporting_default_range($referencetime, true, false);
        $this->assertSame('2026-06-15', date('Y-m-d', $start));
        $this->assertSame('2026-12-31', date('Y-m-d', $end));
    }

    /**
     * Participant myevents path keeps triplet default after workspace profile exists.
     *
     * @return void
     */
    public function test_participant_myevents_default_status_triplet_unchanged(): void {
        $profile = event_manager::get_workspace_filter_profile('myevents', false);
        $this->assertFalse($profile['show_assignment_filter']);
        $this->assertSame(
            event_manager::get_reporting_default_booking_status_filter(),
            event_manager::resolve_overview_booking_status_filter_ids([], false, false, false)
        );
    }

    /**
     * Open requests workspace table profile exposes queue layout and yellow header.
     *
     * @return void
     */
    public function test_get_workspace_table_profile_openrequests_header_and_columns(): void {
        $profile = event_manager::get_workspace_table_profile('openrequests');

        $this->assertSame('#fff3cd', $profile['headerbackgroundcolor']);
        $this->assertTrue($profile['showdatecolumn']);
        $this->assertFalse($profile['showdatetimecolumn']);
        $this->assertTrue($profile['showtablesearch']);
        $this->assertSame('openrequests', $profile['requesttab']);
    }

    /**
     * All requests and history reporting tabs share blue header and datetime column.
     *
     * @return void
     */
    public function test_get_workspace_table_profile_allrequests_reporting_columns(): void {
        foreach (['allrequests', 'history'] as $tab) {
            $profile = event_manager::get_workspace_table_profile($tab);

            $this->assertSame('#cfe2ff', $profile['headerbackgroundcolor'], $tab);
            $this->assertTrue($profile['showdatetimecolumn'], $tab);
            $this->assertFalse($profile['showdatecolumn'], $tab);
            $this->assertTrue($profile['showcreatedbycolumn'], $tab);
            $this->assertTrue($profile['showprogresscolumn'], $tab);
            $this->assertFalse($profile['showtablesearch'], $tab);
        }
    }

    /**
     * Rejected workspace tab keeps canonical slug for status cell and shows reactivate column.
     *
     * @return void
     */
    public function test_get_workspace_table_profile_rejected_shows_reactivate_column_flag(): void {
        $profile = event_manager::get_workspace_table_profile('rejectedcancelled');

        $this->assertTrue($profile['showreactivatecolumn']);
        $this->assertTrue($profile['showdatecolumn']);
        $this->assertSame('rejectedcancelled', $profile['requesttab']);
        $this->assertSame('#f8d7da', $profile['headerbackgroundcolor']);
    }

    /**
     * Workflow history helper returns structured entries with field changes.
     *
     * @return void
     */
    public function test_build_overview_workflow_history_returns_entries_with_changes(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);
        $user = $this->getDataGenerator()->create_user();
        $roleid = \create_role('Bookit service history', 'bookitservicehistory', 'manager');
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $user->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Workflow overview event',
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
            'usercreated' => $user->id,
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

        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $entries = event_manager::build_overview_workflow_history($event, $context, (int)$user->id);
        $this->assertNotEmpty($entries);
        $this->assertArrayHasKey('summary', $entries[0]);
        $this->assertTrue($entries[0]['haschanges']);
        $this->assertNotEmpty($entries[0]['changes']);
        $this->assertArrayHasKey('text', $entries[0]['changes'][0]);
    }

    /**
     * Support viewers do not receive internal-note history details or actor names.
     *
     * @return void
     */
    public function test_build_overview_workflow_history_omits_restricted_fields_for_support(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);
        $actor = $this->getDataGenerator()->create_user(['firstname' => 'Service', 'lastname' => 'Actor']);
        $support = $this->getDataGenerator()->create_user(['firstname' => 'Steven', 'lastname' => 'Support']);
        $supportrole = \create_role('Bookit support on site', 'bookit_supportonsite', 'student');
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $supportrole, $context->id, true);
        \role_assign($supportrole, $support->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Support history sanitization event',
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
            'supportpersons' => (string)$support->id,
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
        $entries = event_manager::build_overview_workflow_history($event, $context, (int)$support->id);
        $this->assertCount(1, $entries);
        $this->assertStringNotContainsString('secret', $entries[0]['summary']);
        $this->assertStringNotContainsString('Service Actor', $entries[0]['summary']);
        $this->assertStringContainsString(
            get_string('event_bookingstatus', 'mod_bookit'),
            $entries[0]['changes'][0]['text']
        );
    }

    /**
     * All-restricted history entries are omitted for limited viewers.
     *
     * @return void
     */
    public function test_build_overview_workflow_history_omits_all_restricted_entries(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);
        $actor = $this->getDataGenerator()->create_user();
        $booker = $this->getDataGenerator()->create_user();
        $bookerrole = \create_role('Bookit booker history', 'bookitbookerhistory', 'student');
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $bookerrole, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $bookerrole, $context->id, true);
        \role_assign($bookerrole, $booker->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        $eventid = $DB->insert_record('bookit_event', (object)[
            'name' => 'Booker history sanitization event',
            'semester' => 20261,
            'institutionid' => null,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 120,
            'roomid' => null,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            'personinchargeid' => null,
            'otherexaminers' => '',
            'coursetemplate' => null,
            'notes' => '',
            'internalnotes' => '',
            'supportpersons' => '',
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usercreated' => $booker->id,
            'usermodified' => $booker->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        event_manager::record_booking_history(
            $eventid,
            'updated',
            (int)$actor->id,
            null,
            null,
            ['internalnotes' => ['from' => 'hidden', 'to' => 'still hidden']],
            false
        );

        $event = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $entries = event_manager::build_overview_workflow_history($event, $context, (int)$booker->id);
        $this->assertSame([], $entries);
    }

    /**
     * Create a Request-Workspace capable context for governed query tests.
     *
     * @return array{0:context_module,1:int}
     */
    private function create_admin_workspace_context(): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('bookit', $bookit->id);
        return [context_module::instance($cm->id), (int)get_admin()->id];
    }

    /**
     * Execute the canonical request workspace query with an optional SQL page.
     *
     * @param context_module $context
     * @param int $userid
     * @param string $workspace
     * @param array $filters
     * @param int|null $reportstart
     * @param int|null $reportend
     * @param int $offset
     * @param int $limit
     * @return array{events:array,count:int}
     */
    private function get_workspace_query_result(
        context_module $context,
        int $userid,
        string $workspace,
        array $filters = [],
        ?int $reportstart = null,
        ?int $reportend = null,
        int $offset = 0,
        int $limit = 0
    ): array {
        global $DB;

        $query = event_manager::get_request_workspace_query(
            $context,
            $userid,
            $workspace,
            $filters,
            $reportstart,
            $reportend
        );
        $events = $DB->get_records_sql(
            "SELECT {$query['fields']} FROM {$query['from']} WHERE {$query['where']} ORDER BY e.starttime DESC, e.id DESC",
            $query['params'],
            $offset,
            $limit
        );

        return [
            'events' => array_values($events),
            'count' => (int)$DB->count_records_sql($query['countsql'], $query['countparams']),
        ];
    }

    /**
     * Active and historical events are separated entirely by the canonical SQL scope.
     */
    public function test_request_workspace_query_scopes_thirty_mixed_past_and_future_events(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id]);
        $context = context_module::instance($bookit->cmid);
        $now = time();

        for ($i = 1; $i <= 30; $i++) {
            $ispast = $i <= 15;
            $starttime = $ispast ? $now - ($i * HOURSECS) : $now + ($i * HOURSECS);
            $this->create_event_record([
                'name' => sprintf('Mixed SQL scope %02d', $i),
                'semester' => event_manager::get_current_semester($starttime),
                'starttime' => $starttime,
                'endtime' => $starttime + HOURSECS,
                'bookingstatus' => $ispast
                    ? event_access_manager::BOOKINGSTATUS_CANCELED
                    : event_access_manager::BOOKINGSTATUS_NEW,
                'usermodified' => (int)get_admin()->id,
            ]);
        }

        $active = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'allrequests',
            [],
            $now - (2 * DAYSECS),
            $now + (2 * DAYSECS)
        );
        $history = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'history',
            [],
            $now - (2 * DAYSECS),
            $now + (2 * DAYSECS)
        );

        $this->assertSame(15, $active['count']);
        $this->assertCount($active['count'], $active['events']);
        $this->assertSame(15, $history['count']);
        $this->assertCount($history['count'], $history['events']);
        foreach ($active['events'] as $event) {
            $this->assertGreaterThan($now, (int)$event->endtime);
        }
        foreach ($history['events'] as $event) {
            $this->assertLessThanOrEqual($now, (int)$event->endtime);
        }
    }

    /**
     * Support-only viewers receive institutional open requests from the canonical SQL scope.
     *
     * @return void
     */
    public function test_request_workspace_query_returns_items_for_support_viewer(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Support queue viewer test',
        ]);
        $context = context_module::instance($bookit->cmid);

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

        $supportuser = $this->getDataGenerator()->create_user();
        \role_assign($supportroleid, $supportuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($supportuser);

        $DB->insert_record('bookit_event', (object)[
            'name' => 'Support visible open request',
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
            'usermodified' => (int)$supportuser->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $result = $this->get_workspace_query_result(
            $context,
            (int)$supportuser->id,
            'openrequests'
        );

        $this->assertSame(1, $result['count']);
        $this->assertCount(1, $result['events']);
        $this->assertSame('Support visible open request', $result['events'][0]->name);
    }

    /**
     * Support on Site history pagination must stay scoped to assigned read-only rows.
     *
     * @return void
     */
    public function test_request_workspace_query_support_history_is_institution_wide(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Support history pagination',
        ]);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $supportrole = $DB->get_record('role', ['shortname' => 'bookit_supportonsite']);
        $supportroleid = $supportrole
            ? (int)$supportrole->id
            : \create_role('Bookit support on site', 'bookit_supportonsite', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $supportroleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $supportroleid, $context->id, true);

        $supportuser = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        \role_assign($supportroleid, $supportuser->id, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($supportuser);

        $start = strtotime('-40 days 09:00:00');
        for ($i = 1; $i <= 26; $i++) {
            $this->create_event_record([
                'name' => sprintf('Support assigned history %02d', $i),
                'starttime' => $start + ($i * DAYSECS),
                'endtime' => $start + ($i * DAYSECS) + HOURSECS,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'supportpersons' => (string)$supportuser->id,
                'usermodified' => (int)$supportuser->id,
            ]);
        }
        for ($i = 1; $i <= 3; $i++) {
            $this->create_event_record([
                'name' => sprintf('Support hidden history %02d', $i),
                'starttime' => $start + (($i + 30) * DAYSECS),
                'endtime' => $start + (($i + 30) * DAYSECS) + HOURSECS,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'supportpersons' => (string)$otheruser->id,
                'usermodified' => (int)$otheruser->id,
            ]);
        }

        $filters = ['bookingstatuses' => [event_access_manager::BOOKINGSTATUS_CONFIRMED]];
        $pagezero = $this->get_workspace_query_result(
            $context,
            (int)$supportuser->id,
            'history',
            $filters,
            $start,
            strtotime('+1 day 23:59:59'),
            0,
            25
        );
        $pageone = $this->get_workspace_query_result(
            $context,
            (int)$supportuser->id,
            'history',
            $filters,
            $start,
            strtotime('+1 day 23:59:59'),
            25,
            25
        );

        $this->assertSame(29, $pagezero['count']);
        $this->assertCount(25, $pagezero['events']);
        $this->assertCount(4, $pageone['events']);
        $names = array_map(static fn($event): string => $event->name, array_merge(
            $pagezero['events'],
            $pageone['events']
        ));
        $this->assertContains('Support assigned history 01', $names);
        $this->assertContains('Support hidden history 01', $names);
    }

    /**
     * Service-Team bounded reads must preserve assignment filtering and queue status scopes.
     *
     * @return void
     */
    public function test_request_workspace_query_applies_assignment_and_status_scopes(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Service queue visibility',
        ]);
        $context = context_module::instance($bookit->cmid);
        $adminid = (int)get_admin()->id;
        $otheruser = $this->getDataGenerator()->create_user();
        $start = strtotime('+5 days 09:00:00');

        for ($i = 1; $i <= 26; $i++) {
            $this->create_event_record([
                'name' => sprintf('Service assigned request %02d', $i),
                'starttime' => $start + ($i * HOURSECS),
                'endtime' => $start + ($i * HOURSECS) + HOURSECS,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'supportpersons' => (string)$adminid,
                'usermodified' => $adminid,
            ]);
        }
        for ($i = 1; $i <= 2; $i++) {
            $this->create_event_record([
                'name' => sprintf('Service unassigned request %02d', $i),
                'starttime' => $start + (($i + 30) * HOURSECS),
                'endtime' => $start + (($i + 30) * HOURSECS) + HOURSECS,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
                'supportpersons' => (string)$otheruser->id,
                'usermodified' => (int)$otheruser->id,
            ]);
            // Keep creator/support off admin so Spec-092 Assigned-to-me stays at the 26 support fixtures.
            $this->create_event_record([
                'name' => sprintf('Service confirmed request %02d', $i),
                'starttime' => $start + (($i + 40) * HOURSECS),
                'endtime' => $start + (($i + 40) * HOURSECS) + HOURSECS,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
                'supportpersons' => '',
                'usercreated' => (int)$otheruser->id,
                'usermodified' => (int)$otheruser->id,
            ]);
            $this->create_event_record([
                'name' => sprintf('Service rejected request %02d', $i),
                'starttime' => $start + (($i + 50) * HOURSECS),
                'endtime' => $start + (($i + 50) * HOURSECS) + HOURSECS,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_REJECTED,
                'supportpersons' => '',
                'usercreated' => (int)$otheruser->id,
                'usermodified' => (int)$otheruser->id,
            ]);
        }

        $assigned = $this->get_workspace_query_result(
            $context,
            $adminid,
            'allrequests',
            ['assignmentfilter' => 'assigned'],
            $start,
            $start + (60 * HOURSECS),
            25,
            25
        );
        $open = $this->get_workspace_query_result($context, $adminid, 'openrequests');
        $confirmed = $this->get_workspace_query_result($context, $adminid, 'confirmedrequests');
        $rejected = $this->get_workspace_query_result($context, $adminid, 'rejectedcancelled');

        $this->assertSame(26, $assigned['count']);
        $this->assertCount(1, $assigned['events']);
        $this->assertStringContainsString('Service assigned request', $assigned['events'][0]->name);
        $this->assertSame(28, $open['count']);
        foreach ($open['events'] as $event) {
            $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$event->bookingstatus);
        }
        $this->assertSame(2, $confirmed['count']);
        $this->assertSame(2, $rejected['count']);
    }

    /**
     * Governed queue pagination must slice 26 open requests across zero-based pages.
     *
     * @return void
     */
    public function test_request_workspace_query_pages_twenty_six_open_requests(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Queue pagination slice test',
        ]);
        $context = context_module::instance($bookit->cmid);

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Queue slice %02d', $i),
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
                'usermodified' => get_admin()->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $pagezero = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'openrequests',
            [],
            null,
            null,
            0,
            25
        );
        $pageone = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'openrequests',
            [],
            null,
            null,
            25,
            25
        );

        $this->assertSame(26, $pagezero['count']);
        $this->assertCount(25, $pagezero['events']);
        $this->assertCount(1, $pageone['events']);
        $this->assertNotSame($pagezero['events'][0]->name, $pageone['events'][0]->name);
    }

    /**
     * Governed all-requests pagination must slice 26 reporting events across zero-based pages.
     *
     * @return void
     */
    public function test_request_workspace_query_pages_twenty_six_all_requests(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'All requests pagination test',
        ]);
        $context = context_module::instance($bookit->cmid);
        $start = strtotime('+1 day 09:00:00');

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('All requests slice %02d', $i),
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
                'supportpersons' => '',
                'extratimebefore' => 0,
                'extratimeafter' => 0,
                'refcourseid' => null,
                'usermodified' => get_admin()->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $pagezero = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'allrequests',
            [],
            $start,
            $start + (30 * DAYSECS),
            0,
            25
        );
        $pageone = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'allrequests',
            [],
            $start,
            $start + (30 * DAYSECS),
            25,
            25
        );

        $this->assertCount(25, $pagezero['events']);
        $this->assertSame(26, $pagezero['count']);
        $this->assertCount(1, $pageone['events']);
        $this->assertNotSame($pagezero['events'][0]->name, $pageone['events'][0]->name);
    }

    /**
     * Governed history pagination must slice 26 terminal reporting events across zero-based pages.
     *
     * @return void
     */
    public function test_request_workspace_query_pages_twenty_six_history_items(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'History pagination test',
        ]);
        $context = context_module::instance($bookit->cmid);
        $end = strtotime('-1 day 11:00:00');

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('History slice %02d', $i),
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

        $pagezero = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'history',
            [],
            $end - (30 * DAYSECS),
            $end,
            0,
            25
        );
        $pageone = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'history',
            [],
            $end - (30 * DAYSECS),
            $end,
            25,
            25
        );

        $this->assertCount(25, $pagezero['events']);
        $this->assertSame(26, $pagezero['count']);
        $this->assertCount(1, $pageone['events']);
        $this->assertNotSame($pagezero['events'][0]->name, $pageone['events'][0]->name);
    }

    /**
     * Canonical count and data scopes stay identical at empty and multi-page boundaries.
     *
     * @return void
     */
    public function test_request_workspace_query_count_and_data_scope_match_at_boundaries(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $emptybookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Empty paging boundary',
        ]);
        $emptycontext = context_module::instance($emptybookit->cmid);

        $empty = $this->get_workspace_query_result(
            $emptycontext,
            (int)get_admin()->id,
            'openrequests'
        );

        $this->assertSame(0, $empty['count']);
        $this->assertSame([], $empty['events']);

        $fullbookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Full paging boundary',
        ]);
        $fullcontext = context_module::instance($fullbookit->cmid);

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Boundary open %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => strtotime('2026-06-20 09:00:00') + $i,
                'endtime' => strtotime('2026-06-20 11:00:00') + $i,
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
        }

        $firstpage = $this->get_workspace_query_result(
            $fullcontext,
            (int)get_admin()->id,
            'openrequests',
            [],
            null,
            null,
            0,
            25
        );
        $secondpage = $this->get_workspace_query_result(
            $fullcontext,
            (int)get_admin()->id,
            'openrequests',
            [],
            null,
            null,
            25,
            25
        );

        $this->assertSame(26, $firstpage['count']);
        $this->assertCount(25, $firstpage['events']);
        $this->assertSame(26, $secondpage['count']);
        $this->assertCount(1, $secondpage['events']);
    }

    /**
     * Governed confirmed requests pagination must page 26 accepted bookings.
     *
     * @return void
     */
    public function test_request_workspace_query_pages_twenty_six_confirmed_requests(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Confirmed pagination test',
        ]);
        $context = context_module::instance($bookit->cmid);

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Confirmed slice %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => strtotime('+10 days 09:00:00') + $i,
                'endtime' => strtotime('+10 days 11:00:00') + $i,
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
                'usermodified' => get_admin()->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        $pagezero = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'confirmedrequests',
            [],
            null,
            null,
            0,
            25
        );
        $pageone = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'confirmedrequests',
            [],
            null,
            null,
            25,
            25
        );

        $this->assertCount(25, $pagezero['events']);
        $this->assertSame(26, $pagezero['count']);
        $this->assertCount(1, $pageone['events']);
    }

    /**
     * Governed rejected/cancelled pagination must page 26 rejected requests.
     *
     * @return void
     */
    public function test_request_workspace_query_pages_twenty_six_rejected_requests(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Rejected pagination test',
        ]);
        $context = context_module::instance($bookit->cmid);

        for ($i = 1; $i <= 26; $i++) {
            $DB->insert_record('bookit_event', (object)[
                'name' => sprintf('Rejected slice %02d', $i),
                'semester' => 20261,
                'institutionid' => 1,
                'starttime' => strtotime('-10 days 09:00:00') + $i,
                'endtime' => strtotime('-10 days 11:00:00') + $i,
                'duration' => 120,
                'roomid' => null,
                'participantsamount' => 12,
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
        }

        $pagezero = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'rejectedcancelled',
            [],
            null,
            null,
            0,
            25
        );
        $pageone = $this->get_workspace_query_result(
            $context,
            (int)get_admin()->id,
            'rejectedcancelled',
            [],
            null,
            null,
            25,
            25
        );

        $this->assertCount(25, $pagezero['events']);
        $this->assertSame(26, $pagezero['count']);
        $this->assertCount(1, $pageone['events']);
    }
}
