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
 * Privacy provider tests.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\privacy;

use context_system;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;

/**
 * Tests for the BookIt privacy provider.
 *
 * @coversDefaultClass \mod_bookit\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Check that CSV role assignments are included in the system user list.
     *
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context_includes_csv_role_assignments(): void {
        global $DB;

        $this->resetAfterTest();
        $examiner = self::getDataGenerator()->create_user();
        $otheruser = self::getDataGenerator()->create_user();
        $DB->insert_record('bookit_event', $this->create_event_record([
            'otherexaminers' => $examiner->id . ',' . $otheruser->id,
        ]));

        $userlist = new userlist(context_system::instance(), 'mod_bookit');
        provider::get_users_in_context($userlist);

        $this->assertContains((int)$examiner->id, $userlist->get_userids());
        $this->assertContains((int)$otheruser->id, $userlist->get_userids());
    }

    /**
     * Check that users with only the overview sort preference are included in privacy requests.
     *
     * @covers ::get_contexts_for_userid
     * @covers ::get_users_in_context
     */
    public function test_user_preference_is_included_in_privacy_context_and_userlist(): void {
        global $DB;

        $this->resetAfterTest();
        $user = self::getDataGenerator()->create_user();
        set_user_preference('mod_bookit_overview_sort', 'name', $user->id);

        $userlist = new userlist(context_system::instance(), 'mod_bookit');
        provider::get_users_in_context($userlist);
        $this->assertContains((int)$user->id, $userlist->get_userids());

        $contexts = provider::get_contexts_for_userid((int)$user->id);
        $this->assertContainsEquals(context_system::instance()->id, $contexts->get_contextids());

        $contextlist = new approved_contextlist($user, 'mod_bookit', $contexts->get_contextids());
        provider::delete_data_for_user($contextlist);
        $this->assertFalse($DB->record_exists('user_preferences', [
            'userid' => $user->id,
            'name' => 'mod_bookit_overview_sort',
        ]));
    }

    /**
     * Check that deletion removes a user's own booking and anonymises remaining assignments.
     *
     * @covers ::delete_data_for_user
     */
    public function test_delete_data_for_user_removes_and_anonymises_user_data(): void {
        global $DB;

        $this->resetAfterTest();
        $user = self::getDataGenerator()->create_user();
        $otheruser = self::getDataGenerator()->create_user();
        $ownedid = $DB->insert_record('bookit_event', $this->create_event_record([
            'usercreated' => $user->id,
            'usermodified' => $user->id,
        ]));
        $sharedid = $DB->insert_record('bookit_event', $this->create_event_record([
            'usercreated' => $otheruser->id,
            'usermodified' => $user->id,
            'personinchargeid' => $user->id,
            'otherexaminers' => $user->id . ',' . $otheruser->id,
            'supportpersons' => (string)$user->id,
        ]));
        $DB->insert_record('bookit_event_history', (object)[
            'eventid' => $sharedid,
            'action' => 'updated',
            'usermodified' => $user->id,
            'timecreated' => time(),
        ]);

        $contextlist = new approved_contextlist($user, 'mod_bookit', [context_system::instance()->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertFalse($DB->record_exists('bookit_event', ['id' => $ownedid]));
        $shared = $DB->get_record('bookit_event', ['id' => $sharedid], '*', MUST_EXIST);
        $this->assertSame(0, (int)$shared->usermodified);
        $this->assertNull($shared->personinchargeid);
        $this->assertSame((string)$otheruser->id, $shared->otherexaminers);
        $this->assertNull($shared->supportpersons);
        $this->assertFalse($DB->record_exists('bookit_event_history', ['eventid' => $sharedid]));
    }

    /**
     * Create a valid event row with optional overrides.
     *
     * @param array $overrides Additional values.
     * @return \stdClass
     */
    private function create_event_record(array $overrides = []): \stdClass {
        return (object)array_merge([
            'name' => 'Privacy test event',
            'starttime' => time(),
            'endtime' => time() + HOURSECS,
            'bookingstatus' => 0,
            'usercreated' => 0,
            'usermodified' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ], $overrides);
    }
}
