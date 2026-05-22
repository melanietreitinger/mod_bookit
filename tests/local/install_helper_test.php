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
 * Unit tests for the third-pass install helper baseline.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

use advanced_testcase;
use mod_bookit\local\manager\weekplan_manager;
use mod_bookit\local\persistent\room;

/**
 * Unit tests for install_helper.
 *
 * @covers \mod_bookit\local\install_helper
 */
final class install_helper_test extends advanced_testcase {
    /**
     * Fresh-install setup must create the agreed one-room/one-weekplan/one-institution baseline.
     *
     * @return void
     */
    public function test_ensure_fresh_install_baseline_creates_expected_records(): void {
        global $DB;

        $this->resetAfterTest(true);

        $report = install_helper::ensure_fresh_install_baseline();

        $this->assertContains($report['status'], ['success', 'idempotent']);
        $this->assertSame(1, $DB->count_records('bookit_room'));
        $this->assertSame(1, $DB->count_records('bookit_weekplan'));
        $this->assertSame(1, $DB->count_records('bookit_institution'));

        $room = $DB->get_record('bookit_room', [], '*', MUST_EXIST);
        $weekplan = $DB->get_record('bookit_weekplan', [], '*', MUST_EXIST);
        $institution = $DB->get_record('bookit_institution', [], '*', MUST_EXIST);

        $this->assertSame(install_helper::DEFAULT_ROOM_NAME, $room->name);
        $this->assertSame('', (string)$room->shortname);
        $this->assertSame(install_helper::DEFAULT_ROOM_DESCRIPTION, (string)$room->description);
        $this->assertSame('', (string)$room->eventcolor);
        $this->assertSame('', (string)$room->location);
        $this->assertSame(1, (int)$room->active);
        $this->assertSame(0, (int)$room->seats);
        $this->assertSame(room::MODE_FREE, (int)$room->roommode);
        $this->assertSame(room::OVERLAPPING_ALLOW_NONE, (int)$room->preventoverlap);
        $this->assertSame(install_helper::DEFAULT_WEEKPLAN_NAME, $weekplan->name);
        $this->assertSame(install_helper::DEFAULT_INSTITUTION_NAME, $institution->name);
        [, $expectedslots] = weekplan_manager::parse_weekplan(install_helper::DEFAULT_WEEKPLAN_SCHEDULE);
        $actualslots = array_values($DB->get_records('bookit_weekplanslot', ['weekplanid' => (int)$weekplan->id], 'starttime ASC'));
        $this->assertCount(count($expectedslots), $actualslots);
        foreach ($expectedslots as $index => [$expectedstart, $expectedend]) {
            $this->assertSame($expectedstart, (int)$actualslots[$index]->starttime);
            $this->assertSame($expectedend, (int)$actualslots[$index]->endtime);
        }
        $this->assertTrue($DB->record_exists('bookit_weekplan_room', [
            'weekplanid' => $weekplan->id,
            'roomid' => $room->id,
        ]));
    }

    /**
     * Fresh-install setup must become idempotent after the baseline exists.
     *
     * @return void
     */
    public function test_ensure_fresh_install_baseline_is_idempotent(): void {
        $this->resetAfterTest(true);

        install_helper::ensure_fresh_install_baseline();
        $second = install_helper::ensure_fresh_install_baseline();

        $this->assertSame('idempotent', $second['status']);
        $this->assertSame('idempotent', $second['operations']['institution']['status']);
        $this->assertSame('idempotent', $second['operations']['room']['status']);
        $this->assertSame('idempotent', $second['operations']['weekplan']['status']);
    }

    /**
     * Role preset import must report success on first run and idempotence afterwards.
     *
     * @return void
     */
    public function test_import_default_roles_with_report_tracks_outcomes(): void {
        global $DB;

        $this->resetAfterTest(true);

        $first = install_helper::import_default_roles_with_report();
        $this->assertSame('success', $first['status']);
        $this->assertCount(5, $first['imported']);

        foreach ($first['imported'] as $shortname) {
            $this->assertTrue($DB->record_exists('role', ['shortname' => $shortname]));
        }

        $second = install_helper::import_default_roles_with_report();
        $this->assertSame('idempotent', $second['status']);
        $this->assertSame([], $second['imported']);
        $this->assertCount(5, $second['skipped']);
    }

    /**
     * Force re-imports must deduplicate self-assign links so later presets still get imported.
     *
     * @return void
     */
    public function test_import_default_roles_with_report_force_reimport_is_self_assign_safe(): void {
        global $DB;

        $this->resetAfterTest(true);

        install_helper::import_default_roles_with_report();
        $forced = install_helper::import_default_roles_with_report(true);

        $this->assertSame('success', $forced['status']);
        $this->assertSame([], $forced['errors']);
        $this->assertCount(5, $forced['imported']);
        $this->assertContains('bookit_supportonsite', $forced['imported']);

        $serviceteamrole = $DB->get_record('role', ['shortname' => 'bookit_serviceteam'], 'id', MUST_EXIST);
        $this->assertSame(0, $DB->count_records('role_allow_assign', [
            'roleid' => $serviceteamrole->id,
            'allowassign' => $serviceteamrole->id,
        ]));
    }

    /**
     * The demo course setup must enrol every shipped QA demo user into the seeded course context.
     *
     * @return void
     */
    public function test_create_default_course_and_activity_enrols_all_demo_users(): void {
        global $DB;

        $this->resetAfterTest(true);

        install_helper::import_default_roles_with_report();
        install_helper::import_default_users();

        $created = install_helper::create_default_course_and_activity();

        $this->assertTrue($created);

        $course = $DB->get_record('course', ['shortname' => 'BOOKIT-DEMO'], 'id', MUST_EXIST);
        $bookit = $DB->get_record('bookit', ['course' => $course->id], 'id,name', MUST_EXIST);
        $this->assertSame('BookIt Demo', $bookit->name);

        foreach (
            [
            'eva.examiner',
            'bob.booker',
            'susi.serviceteam',
            'steven.support',
            'olaf.observer',
            ] as $username
        ) {
            $userid = $DB->get_field('user', 'id', ['username' => $username], MUST_EXIST);
            $this->assertTrue(
                $DB->record_exists_sql(
                    'SELECT 1
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid
                      WHERE ue.userid = :userid
                        AND e.courseid = :courseid',
                    ['userid' => $userid, 'courseid' => $course->id]
                ),
                'Expected demo user to be enrolled: ' . $username
            );
        }
    }

    /**
     * Fresh installs must keep optional resources and checklist parts disabled until the admin enables them.
     *
     * @return void
     */
    public function test_ensure_optional_part_defaults_uses_requested_default_state(): void {
        $this->resetAfterTest(true);

        install_helper::ensure_optional_part_defaults(false);
        $this->assertFalse(install_helper::is_resources_enabled());
        $this->assertFalse(install_helper::is_checklist_enabled());

        unset_config(install_helper::CONFIG_RESOURCES_ENABLED, 'mod_bookit');
        unset_config(install_helper::CONFIG_CHECKLIST_ENABLED, 'mod_bookit');

        install_helper::ensure_optional_part_defaults(true);
        $this->assertTrue(install_helper::is_resources_enabled());
        $this->assertTrue(install_helper::is_checklist_enabled());
    }

    /**
     * Fresh installs must expose expressive booking-status notification defaults without numeric keys.
     *
     * @return void
     */
    public function test_ensure_booking_status_notification_defaults_exposes_expressive_config_keys(): void {
        $this->resetAfterTest(true);

        install_helper::ensure_booking_status_notification_defaults();

        foreach (install_helper::get_booking_status_notification_statuses() as $statuskey => $statusdata) {
            $this->assertSame(1, (int)get_config('mod_bookit', $statusdata['enabledconfig']));
            $this->assertSame(
                install_helper::get_booking_status_notification_default_subject($statuskey),
                (string)get_config('mod_bookit', $statusdata['subjectconfig'])
            );
            $this->assertSame(
                install_helper::get_booking_status_notification_default_body($statuskey),
                (string)get_config('mod_bookit', $statusdata['bodyconfig'])
            );
        }

        $this->assertEmpty(get_config('mod_bookit', 'bookingstatus_subject_0'));
        $this->assertEmpty(get_config('mod_bookit', 'bookingstatus_body_0'));
        $this->assertEmpty(get_config('mod_bookit', 'bookingstatus_enabled_0'));
    }

    /**
     * Shared config-key helpers must point to the expected storage shape.
     *
     * @return void
     */
    public function test_booking_status_notification_config_key_helpers_use_shared_shape(): void {
        $this->assertSame(
            'bookingstatus_subject_accepted',
            install_helper::get_booking_status_notification_template_config_key('accepted', 'subject')
        );
        $this->assertSame(
            'bookingstatus_body_accepted',
            install_helper::get_booking_status_notification_template_config_key('accepted', 'body')
        );
    }

    /**
     * Blank shared overrides must be removed so the shipped defaults stay visible.
     *
     * @return void
     */
    public function test_ensure_booking_status_notification_defaults_removes_blank_shared_overrides(): void {
        global $DB;

        $this->resetAfterTest(true);

        set_config('bookingstatus_subject_accepted', '', 'mod_bookit');
        set_config('bookingstatus_body_accepted', " \n ", 'mod_bookit');

        install_helper::ensure_booking_status_notification_defaults();

        $this->assertFalse($DB->record_exists('config_plugins', [
            'plugin' => 'mod_bookit',
            'name' => 'bookingstatus_subject_accepted',
        ]));
        $this->assertFalse($DB->record_exists('config_plugins', [
            'plugin' => 'mod_bookit',
            'name' => 'bookingstatus_body_accepted',
        ]));
    }

    /**
     * Existing non-empty shared overrides must remain untouched.
     *
     * @return void
     */
    public function test_ensure_booking_status_notification_defaults_keeps_non_empty_shared_overrides(): void {
        $this->resetAfterTest(true);

        set_config('bookingstatus_subject_rejected', 'Configured rejected subject', 'mod_bookit');
        set_config('bookingstatus_body_rejected', 'Configured rejected body', 'mod_bookit');

        install_helper::ensure_booking_status_notification_defaults();

        $this->assertSame('Configured rejected subject', get_config('mod_bookit', 'bookingstatus_subject_rejected'));
        $this->assertSame('Configured rejected body', get_config('mod_bookit', 'bookingstatus_body_rejected'));
    }
}
