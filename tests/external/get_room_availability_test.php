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
use mod_bookit\local\install_helper;

/**
 * External contract tests for the governed room-availability read.
 *
 * @covers \mod_bookit\external\get_room_availability
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_room_availability_test extends advanced_testcase {
    /**
     * Room availability must expose slot/blocker entries for the requested room and time range.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_execute_returns_room_entries(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        install_helper::ensure_fresh_install_baseline();
        install_helper::ensure_optional_part_defaults(false);
        install_helper::ensure_booking_status_notification_defaults();

        $roomid = (int)$DB->get_field_sql(
            'SELECT id
               FROM {bookit_room}
              WHERE ' . $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name'),
            ['name' => install_helper::DEFAULT_ROOM_NAME],
            MUST_EXIST
        );
        $DB->set_field('bookit_weekplan_room', 'starttime', 0, ['roomid' => $roomid]);

        $response = get_room_availability::execute($roomid, '2026-05-20T00:00:00', '2026-05-27T00:00:00');

        $this->assertSame('ok', $response['status']);
        $this->assertFalse($response['denied']);
        $this->assertNotEmpty($response['entries']);
        $this->assertContains($response['entries'][0]['type'], ['slot', 'blocker', 'reservation']);
        $this->assertSame($roomid, (int)$response['resourceid']);
    }
}
