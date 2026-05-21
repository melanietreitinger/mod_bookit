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
 * Unit tests for get_possible_starttimes past-date helpers.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\external;

use advanced_testcase;
use mod_bookit\local\install_helper;

/**
 * Unit tests for get_possible_starttimes.
 *
 * @covers \mod_bookit\external\get_possible_starttimes
 */
final class get_possible_starttimes_test extends advanced_testcase {
    /**
     * Past timestamps must be rejected while present and future ones stay valid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function test_is_starttime_in_past_uses_reference_time(): void {
        $referencetime = 1715000000;

        $this->assertTrue(get_possible_starttimes::is_starttime_in_past($referencetime - 1, $referencetime));
        $this->assertFalse(get_possible_starttimes::is_starttime_in_past($referencetime, $referencetime));
        $this->assertFalse(get_possible_starttimes::is_starttime_in_past($referencetime + 3600, $referencetime));
    }

    /**
     * Past-day slots must stay hidden for requesters but remain available to service-team flows.
     *
     * @runInSeparateProcess
     * @return void
     * @throws \dml_exception
     */
    public function test_list_possible_starttimes_respects_allowpast_flag(): void {
        global $DB;

        $this->resetAfterTest(true);
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
        $date = (new \DateTimeImmutable('last friday'))->setTime(0, 0);

        [$blockedslots, $blockedstatus] = get_possible_starttimes::list_possible_starttimes(
            \DateTime::createFromImmutable($date),
            60,
            $roomid,
            null,
            false
        );
        [$allowedslots, $allowedstatus] = get_possible_starttimes::list_possible_starttimes(
            \DateTime::createFromImmutable($date),
            60,
            $roomid,
            null,
            true
        );

        $this->assertSame([], $blockedslots);
        $this->assertSame(0, $blockedstatus);
        $this->assertNotEmpty($allowedslots);
        $this->assertNull($allowedstatus);
    }

    /**
     * Governed room reads must coexist with legacy starttime lookups during rollout.
     *
     * @runInSeparateProcess
     * @return void
     * @throws \dml_exception
     */
    public function test_execute_coexists_with_governed_room_availability_reads(): void {
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

        $date = new \DateTimeImmutable('next monday');
        $daystart = $date->setTime(0, 0);
        $dayend = $date->setTime(23, 59, 59);

        $roomresponse = get_room_availability::execute(
            $roomid,
            $daystart->format('Y-m-d\TH:i:s'),
            $dayend->format('Y-m-d\TH:i:s')
        );

        [$slots, $status] = get_possible_starttimes::list_possible_starttimes(
            \DateTime::createFromImmutable($date->setTime(0, 0)),
            60,
            $roomid,
            null,
            true
        );

        $this->assertSame('ok', $roomresponse['status']);
        $this->assertFalse($roomresponse['denied']);
        $this->assertNotEmpty($roomresponse['entries']);
        $this->assertNotEmpty($slots);
        $this->assertNull($status);
    }
}
