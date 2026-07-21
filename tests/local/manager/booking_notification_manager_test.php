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
 * Unit tests for booking_notification_manager.
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
use mod_bookit\local\install_helper;

/**
 * Unit tests for booking notifications.
 *
 * @covers \mod_bookit\local\manager\booking_notification_manager
 */
final class booking_notification_manager_test extends advanced_testcase {
    /** @var int Real course-module ID used in notification tests. */
    private int $cmid = 0;

    /** @var context_module Real module context used for capability-based recipient resolution. */
    private context_module $context;

    /**
     * Set up message providers, defaults, and a real BookIt context.
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

        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Booking notification test',
        ]);
        $this->cmid = (int)$bookit->cmid;
        $this->context = context_module::instance($this->cmid);
    }

    /**
     * All configured booking participants must receive the status notification.
     *
     * @return void
     */
    public function test_notify_status_changed_sends_to_booking_roles(): void {
        $booker = $this->getDataGenerator()->create_user();
        $personincharge = $this->getDataGenerator()->create_user();
        $otherexaminer = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id, $personincharge->id, (string)$otherexaminer->id);

        $sink = $this->redirectMessages();
        $sent = booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertSame(3, $sent);
        $this->assertCount(3, $messages);
        $this->assert_message_recipients($messages, [$booker->id, $personincharge->id, $otherexaminer->id]);
        $this->assertSame('mod_bookit', $messages[0]->component);
        $this->assertSame('bookit_booking_status_changed', $messages[0]->eventtype);
    }

    /**
     * Notifications must still reach the booking person after an admin foreign form save.
     *
     * @return void
     */
    public function test_notify_status_changed_reaches_booking_person_after_foreign_form_save(): void {
        global $DB;

        $booker = $this->getDataGenerator()->create_user();
        $admin = get_admin();
        $eventid = $this->create_test_event((int)$booker->id, null, null, 'Foreign notification event');

        $before = \mod_bookit\local\entity\bookit_event::from_database($eventid);
        $updated = \mod_bookit\local\entity\bookit_event::from_database($eventid);
        $updated->notes = 'Foreign save before notification';

        event_manager::save_event_with_lifecycle_tracking(
            $updated,
            $before,
            (int)$admin->id,
            $this->context
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $eventid], '*', MUST_EXIST);
        $this->assertSame((int)$booker->id, (int)$persisted->usermodified);

        $sink = $this->redirectMessages();
        $sent = booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertGreaterThanOrEqual(1, $sent);
        $this->assert_message_recipients($messages, [$booker->id]);
    }

    /**
     * Disabled recipient toggles must reduce the recipient set without affecting others.
     *
     * @return void
     */
    public function test_notify_status_changed_respects_recipient_settings(): void {
        $booker = $this->getDataGenerator()->create_user();
        $personincharge = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id, $personincharge->id);

        set_config('bookingstatus_notify_personincharge', 0, 'mod_bookit');

        $sink = $this->redirectMessages();
        $sent = booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertSame(1, $sent);
        $this->assertCount(1, $messages);
        $this->assertSame((string)$booker->id, $messages[0]->useridto);
    }

    /**
     * Localized template data must refresh derived placeholders for status and date output.
     *
     * @return void
     */
    public function test_localize_template_data_refreshes_status_time_and_bookingdate_placeholders(): void {
        $template = (object)[
            'newstatus' => event_access_manager::BOOKINGSTATUS_CONFIRMED,
            'oldstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttimestamp' => strtotime('2026-05-08 09:00:00'),
            'endtimestamp' => strtotime('2026-05-08 11:00:00'),
            'bookingstatus' => 'STALE-STATUS',
            'oldbookingstatus' => 'STALE-OLD-STATUS',
            'bookingdate' => 'STALE-DATE',
            'starttime' => 'STALE-START',
            'endtime' => 'STALE-END',
        ];

        $reflection = new \ReflectionMethod(booking_notification_manager::class, 'localize_template_data');
        $reflection->setAccessible(true);
        $localized = $reflection->invoke(null, $template, 'en');

        $this->assertSame(get_string('event_bookingstatus_2', 'mod_bookit'), $localized->bookingstatus);
        $this->assertSame(get_string('event_bookingstatus_0', 'mod_bookit'), $localized->oldbookingstatus);
        $this->assertNotSame('STALE-DATE', $localized->bookingdate);
        $this->assertNotSame('STALE-START', $localized->starttime);
        $this->assertNotSame('STALE-END', $localized->endtime);
    }

    /**
     * One shared override must apply consistently for recipients in different languages.
     *
     * @return void
     */
    public function test_notify_status_changed_uses_shared_configured_templates_for_all_recipient_languages(): void {
        global $DB;

        $booker = $this->getDataGenerator()->create_user();
        $personincharge = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'lang', 'de', ['id' => $booker->id]);
        $DB->set_field('user', 'lang', 'en', ['id' => $personincharge->id]);
        $eventid = $this->create_test_event($booker->id, $personincharge->id, null, 'Configured Exam');

        set_config(
            install_helper::get_booking_status_notification_template_config_key('confirmed', 'subject'),
            'Shared accepted ###EVENTNAME###',
            'mod_bookit'
        );
        set_config(
            install_helper::get_booking_status_notification_template_config_key('confirmed', 'body'),
            'Shared body ###EVENTNAME### ###BOOKINGDATE###',
            'mod_bookit'
        );
        $sink = $this->redirectMessages();
        booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(2, $messages);

        $bookermessage = $this->get_message_for_user($messages, $booker->id);
        $examinermessage = $this->get_message_for_user($messages, $personincharge->id);

        $this->assertSame('Shared accepted Configured Exam', $bookermessage->subject);
        $this->assertSame('Shared accepted Configured Exam', $examinermessage->subject);
        $this->assertStringStartsWith("Hello,\n\nShared body Configured Exam ", $bookermessage->fullmessage);
        $this->assertStringStartsWith("Hello,\n\nShared body Configured Exam ", $examinermessage->fullmessage);
        $this->assertStringNotContainsString('###BOOKINGDATE###', $bookermessage->fullmessage);
        $this->assertStringNotContainsString('###BOOKINGDATE###', $examinermessage->fullmessage);
        $this->assertStringContainsString("\n\nRoom: ", $bookermessage->fullmessage);
        $this->assertStringContainsString("\n\nKind regards,\nYour BookIt team", $bookermessage->fullmessage);
    }

    /**
     * Shipped defaults must remain active when no shared override exists.
     *
     * @return void
     */
    public function test_notify_status_changed_uses_shipped_defaults_without_shared_override(): void {
        global $DB;

        $booker = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'lang', 'en', ['id' => $booker->id]);
        $eventid = $this->create_test_event($booker->id, null, null, 'Configured Exam');

        unset_config('bookingstatus_subject_confirmed', 'mod_bookit');
        unset_config('bookingstatus_body_confirmed', 'mod_bookit');

        $sink = $this->redirectMessages();
        booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $this->assertStringStartsWith('Booking request confirmed: Configured Exam on ', $messages[0]->subject);
        $this->assertStringStartsWith(
            "Hello,\n\nThank you for your request \"Configured Exam\" for ",
            $messages[0]->fullmessage
        );
        $this->assertStringContainsString("\n\nOpen booking: ", $messages[0]->fullmessage);
        $this->assertStringEndsWith("Kind regards,\nYour BookIt team", $messages[0]->fullmessage);
    }

    /**
     * Disabled status types must suppress all message and email delivery.
     *
     * @return void
     */
    public function test_notify_status_changed_suppresses_disabled_status(): void {
        $booker = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($booker->id);

        set_config('bookingstatus_enabled_confirmed', 0, 'mod_bookit');

        $messagesink = $this->redirectMessages();
        $emailsink = $this->redirectEmails();
        $sent = booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $messagesink->get_messages();
        $emails = $emailsink->get_messages();
        $emailsink->close();
        $messagesink->close();

        $this->assertSame(0, $sent);
        $this->assertCount(0, $messages);
        $this->assertCount(0, $emails);
    }

    /**
     * Service-team users and shared service addresses must both receive delivery when configured.
     *
     * @return void
     */
    public function test_notify_status_changed_includes_service_team_users_and_shared_addresses(): void {
        $booker = $this->getDataGenerator()->create_user();
        $serviceteamuser = $this->getDataGenerator()->create_user();
        $this->assign_service_team_user($serviceteamuser->id);
        $eventid = $this->create_test_event($booker->id);

        set_config(
            'bookingstatus_service_addresses',
            'service.one@example.com invalid-address service.two@example.com service.one@example.com',
            'mod_bookit'
        );

        $messagesink = $this->redirectMessages();
        $emailsink = $this->redirectEmails();
        $sent = booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $messagesink->get_messages();
        $emails = $emailsink->get_messages();
        $emailsink->close();
        $messagesink->close();

        $this->assertSame(4, $sent);
        $this->assertCount(2, $messages);
        $this->assertCount(2, $emails);
        $this->assert_message_recipients($messages, [$booker->id, $serviceteamuser->id]);
    }

    /**
     * One user who appears in multiple recipient roles must only receive one notification.
     *
     * @return void
     */
    public function test_notify_status_changed_deduplicates_overlapping_service_team_roles(): void {
        $booker = $this->getDataGenerator()->create_user();
        $serviceteamuser = $this->getDataGenerator()->create_user();
        $this->assign_service_team_user($serviceteamuser->id);
        $eventid = $this->create_test_event(
            $booker->id,
            $serviceteamuser->id,
            $serviceteamuser->id . ',' . $serviceteamuser->id
        );

        $sink = $this->redirectMessages();
        $sent = booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertSame(2, $sent);
        $this->assertCount(2, $messages);
        $this->assert_message_recipients($messages, [$booker->id, $serviceteamuser->id]);
    }

    /**
     * Later edits by a different user must not overwrite the original requester recipient.
     *
     * @return void
     */
    public function test_notify_status_changed_uses_stable_requester_after_later_edits(): void {
        $requester = $this->getDataGenerator()->create_user();
        $editor = $this->getDataGenerator()->create_user();
        $eventid = $this->create_test_event($requester->id, null, null, 'Stable requester', $editor->id);

        $sink = $this->redirectMessages();
        $sent = booking_notification_manager::notify_status_changed(
            $this->cmid,
            $eventid,
            event_access_manager::BOOKINGSTATUS_NEW,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertSame(1, $sent);
        $this->assertCount(1, $messages);
        $this->assertSame((string)$requester->id, $messages[0]->useridto);
        $this->assertNotSame((string)$editor->id, $messages[0]->useridto);
    }

    /**
     * Assign a module-local service-team role to the given user.
     *
     * @param int $userid
     * @return void
     */
    private function assign_service_team_user(int $userid): void {
        \update_capabilities('mod_bookit');
        $roleid = \create_role('BookIt service team', 'bookitserviceteamtest', 'Service team test role');
        \assign_capability('mod/bookit:managebasics', CAP_ALLOW, $roleid, $this->context->id, true);
        \role_assign($roleid, $userid, $this->context->id);
        \accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * Assert that the collected message recipients match the expected user IDs.
     *
     * @param array $messages
     * @param int[] $expecteduserids
     * @return void
     */
    private function assert_message_recipients(array $messages, array $expecteduserids): void {
        $actual = array_map(static fn($message): int => (int)$message->useridto, $messages);
        $expecteduserids = array_map('intval', $expecteduserids);
        sort($actual);
        sort($expecteduserids);
        $this->assertSame($expecteduserids, $actual);
    }

    /**
     * Return one captured message for the provided recipient.
     *
     * @param array $messages
     * @param int $userid
     * @return \stdClass
     */
    private function get_message_for_user(array $messages, int $userid): \stdClass {
        foreach ($messages as $message) {
            if ((int)$message->useridto === $userid) {
                return $message;
            }
        }

        $this->fail('No message captured for user ' . $userid);
    }

    /**
     * Insert a minimal test event and seed the stable requester history entry.
     *
     * @param int $requesterid
     * @param int|null $personinchargeid
     * @param string|null $otherexaminers
     * @param string $name
     * @param int|null $usermodified
     * @param int $bookingstatus
     * @return int
     */
    private function create_test_event(
        int $requesterid,
        ?int $personinchargeid = null,
        ?string $otherexaminers = null,
        string $name = 'Booking Notification Event',
        ?int $usermodified = null,
        int $bookingstatus = event_access_manager::BOOKINGSTATUS_NEW
    ): int {
        global $DB;

        $now = time();
        $eventid = (int)$DB->insert_record('bookit_event', (object)[
            'name' => $name,
            'semester' => 20261,
            'institutionid' => 0,
            'starttime' => strtotime('2026-05-08 09:00:00'),
            'endtime' => strtotime('2026-05-08 11:00:00'),
            'duration' => 60,
            'roomid' => 0,
            'participantsamount' => null,
            'timecompensation' => null,
            'compensationfordisadvantages' => null,
            'bookingstatus' => $bookingstatus,
            'personinchargeid' => $personinchargeid,
            'otherexaminers' => $otherexaminers,
            'coursetemplate' => null,
            'notes' => null,
            'internalnotes' => null,
            'supportpersons' => null,
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'refcourseid' => null,
            'usermodified' => $usermodified ?? $requesterid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('bookit_event_history', (object)[
            'eventid' => $eventid,
            'action' => 'created',
            'oldstatus' => null,
            'newstatus' => $bookingstatus,
            'changedfields' => null,
            'recoverymarker' => 0,
            'usermodified' => $requesterid,
            'timecreated' => $now - 60,
        ]);

        return $eventid;
    }

    /**
     * Placeholder replacement uses the canonical token set only (no legacy aliases).
     *
     * @return void
     */
    public function test_replace_placeholders_uses_canonical_tokens_only(): void {
        $method = new \ReflectionMethod(booking_notification_manager::class, 'replace_placeholders');
        $method->setAccessible(true);

        $data = (object)[
            'eventname' => 'Exam',
            'bookingdate' => '08.05.2026',
            'bookingstatus' => 'Confirmed',
            'oldbookingstatus' => 'New',
            'eventurl' => 'https://example.test/event',
            'room' => 'Hall',
            'starttime' => '09:00',
            'endtime' => '11:00',
            'bookingperson' => 'Booker',
            'personincharge' => 'PIC',
            'otherexaminers' => 'Other',
        ];

        $template = '###EVENTNAME### / ###BOOKINGDATE### / ###UNKNOWNTOKEN### / ###EVENT###';
        $replaced = $method->invoke(null, $template, $data);

        $this->assertSame('Exam / 08.05.2026 / ###UNKNOWNTOKEN### / ###EVENT###', $replaced);
    }
}
