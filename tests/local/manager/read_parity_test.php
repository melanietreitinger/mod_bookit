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

namespace mod_bookit\local\manager;

use advanced_testcase;
use context_module;

/**
 * Parity checks for legacy-vs-governed read payloads.
 *
 * @covers \mod_bookit\local\manager\event_manager
 * @covers \mod_bookit\local\manager\event_access_manager
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class read_parity_test extends advanced_testcase {
    /**
     * The governed calendar read must preserve the visible event ids of the legacy manager projection.
     *
     * @return void
     */
    public function test_governed_calendar_read_preserves_legacy_event_ids(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Parity']);
        $context = context_module::instance($bookit->cmid);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $roomid = $generator->create_room([
            'name' => 'Parity room',
            'shortname' => 'PR-1',
        ]);

        $firstid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Parity first',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 09:00:00'),
            'endtime' => strtotime('2026-05-20 11:00:00'),
            'duration' => 120,
            'roomid' => $roomid,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
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
        $secondid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => 'Parity second',
            'semester' => 20261,
            'institutionid' => 1,
            'starttime' => strtotime('2026-05-20 12:00:00'),
            'endtime' => strtotime('2026-05-20 13:00:00'),
            'duration' => 60,
            'roomid' => $roomid,
            'participantsamount' => 10,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_ACCEPTED,
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

        $legacy = event_manager::get_events_in_timerange('2026-05-20 00:00', '2026-05-20 23:59', (int)$context->instanceid);
        $governed = event_manager::get_governed_calendar_events(
            $context,
            (int)get_admin()->id,
            '2026-05-20 00:00',
            '2026-05-20 23:59',
            []
        );

        $legacyids = array_map(static fn(array $event): int => (int)$event['id'], $legacy);
        $governedids = array_map(static fn(array $event): int => (int)$event['eventid'], $governed);

        $this->assertSame([$firstid, $secondid], $legacyids);
        $this->assertSame($legacyids, $governedids);
    }
}
