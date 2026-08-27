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
 * Tests for cancel-only form saves preserving booking status (FEED2-004 / Spec 037).
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form;

use advanced_testcase;
use context_module;
use mod_bookit\form\edit_event_form;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\install_helper;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;

/**
 * Form submission tests for save-without-cancel semantics.
 *
 * @covers \mod_bookit\form\edit_event_form::process_dynamic_submission
 * @runTestsInSeparateProcesses
 */
final class edit_event_form_cancel_save_test extends advanced_testcase {
    /**
     * Create a participant context and assign the user.
     *
     * @param int $userid
     * @return context_module
     */
    private function create_participant_context(int $userid): context_module {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($userid, $course->id);
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Cancel save test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant cancel', 'bookitparticipantcancel', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Ensure bookit_supportonsite has required capabilities in the given context.
     *
     * @param context_module $context
     * @return void
     */
    private function ensure_support_capabilities(context_module $context): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'bookit_supportonsite']);
        if (!$role) {
            $roleid = \create_role('Bookit support on site', 'bookit_supportonsite', 'student');
        } else {
            $roleid = (int)$role->id;
        }
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * Assign bookit_supportonsite to a user.
     *
     * @param context_module $context
     * @param int $userid
     * @return void
     */
    private function assign_support_role(context_module $context, int $userid): void {
        global $DB;

        $this->ensure_support_capabilities($context);
        $role = $DB->get_record('role', ['shortname' => 'bookit_supportonsite'], 'id', MUST_EXIST);
        \role_assign($role->id, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * Create a room for event persistence.
     *
     * @return int
     */
    private function create_room(): int {
        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        return $generator->create_room([
            'name' => 'Cancel save room',
            'shortname' => 'CNS001',
        ]);
    }

    /**
     * Build a persisted event for cancel-only participant scenarios.
     *
     * @param context_module $context
     * @param int $bookingpersonid
     * @param int $bookingstatus
     * @param int $roomid
     * @return bookit_event
     */
    private function create_cancel_only_event(
        context_module $context,
        int $bookingpersonid,
        int $bookingstatus,
        int $roomid
    ): bookit_event {
        $examiner = $this->getDataGenerator()->create_user();

        $starttime = strtotime('+14 days 09:00:00');
        $endtime = strtotime('+14 days 11:00:00');

        $this->setUser($bookingpersonid);
        $event = new bookit_event(
            0,
            'Cancel-only save event',
            20261,
            1,
            $starttime,
            $endtime,
            120,
            $roomid,
            10,
            '',
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$examiner->id,
            '',
            0,
            'Participant notes',
            '',
            '',
            0,
            0,
            null,
            $bookingpersonid,
            $bookingpersonid,
            time(),
            time(),
            [],
        );

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, $bookingpersonid, $context);
        $record = event_manager::get_event((int)$saved->id);
        event_manager::transition_booking_status(
            $record,
            $bookingstatus,
            $bookingpersonid,
            $context
        );

        return bookit_event::from_database((int)$saved->id);
    }

    /**
     * Build a full form payload from a persisted event record.
     *
     * @param \stdClass $event
     * @param context_module $context
     * @param array $overrides
     * @return array
     */
    private function build_formdata_from_event(\stdClass $event, context_module $context, array $overrides = []): array {
        $startdate = [
            'year' => (int)userdate((int)$event->starttime, '%Y'),
            'month' => (int)userdate((int)$event->starttime, '%m'),
            'day' => (int)userdate((int)$event->starttime, '%d'),
        ];

        $data = [
            'cmid' => $context->instanceid,
            'id' => $event->id,
            '_qf__mod_bookit_form_edit_event_form' => 1,
            'name' => $event->name,
            'semester' => $event->semester,
            'institutionid' => $event->institutionid,
            'roomid' => $event->roomid,
            'duration' => $event->duration,
            'participantsamount' => $event->participantsamount,
            'personinchargeid' => $event->personinchargeid,
            'otherexaminers' => $event->otherexaminers,
            'supportpersons' => $event->supportpersons,
            'coursetemplate' => $event->coursetemplate,
            'compensationfordisadvantages' => $event->compensationfordisadvantages,
            'notes' => $event->notes,
            'internalnotes' => $event->internalnotes,
            'refcourseid' => $event->refcourseid,
            'extratimebefore' => $event->extratimebefore,
            'extratimeafter' => $event->extratimeafter,
            'bookingstatus' => $event->bookingstatus,
            'starttime' => $event->starttime,
            'startdate' => $startdate,
            'usermodified' => $event->usermodified,
            'submitbutton' => 'Save changes',
        ];

        return array_merge($data, $overrides);
    }

    /**
     * Submit the edit event form via the core dynamic form API.
     *
     * @param context_module $context
     * @param int $eventid
     * @param int $userid
     * @param int $bookingstatus
     * @param array $overrides
     * @return void
     */
    private function submit_edit_event_form(
        context_module $context,
        int $eventid,
        int $userid,
        int $bookingstatus,
        array $overrides = []
    ): void {
        $this->setUser($userid);

        $event = event_manager::get_event($eventid);
        $formdata = $this->build_formdata_from_event(
            $event,
            $context,
            array_merge(['bookingstatus' => $bookingstatus], $overrides)
        );
        $formdata['sesskey'] = sesskey();
        $_POST['sesskey'] = $formdata['sesskey'];

        $form = new edit_event_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_submitted(), 'Form was not marked as submitted.');
        if (!$form->is_validated()) {
            $reflection = new \ReflectionClass($form);
            $mformproperty = $reflection->getProperty('_form');
            $mformproperty->setAccessible(true);
            $mform = $mformproperty->getValue($form);
            $this->fail('Form validation failed: ' . json_encode($mform->_errors ?? []));
        }
        $form->process_dynamic_submission();
    }

    /**
     * Set up notification defaults required by lifecycle saves.
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
        set_config(install_helper::CONFIG_RESOURCES_ENABLED, 0, 'mod_bookit');
        set_config(install_helper::CONFIG_CHECKLIST_ENABLED, 0, 'mod_bookit');
    }

    /**
     * Booking person save on In progress must not set Canceled without explicit intent.
     *
     * @return void
     */
    public function test_participant_save_in_progress_preserves_status_without_cancel(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int)$bookingperson->id);
        $roomid = $this->create_room();
        $event = $this->create_cancel_only_event(
            $context,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $this->assertTrue(event_access_manager::can_participant_cancel_only(
            event_manager::get_event((int)$event->id),
            $context,
            (int)$bookingperson->id
        ));

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$persisted->bookingstatus);
        $this->assertTrue(
            event_access_manager::can_user_view_event_in_calendar($persisted, $context, (int)$bookingperson->id)
        );
    }

    /**
     * Booking person save on Accepted must not set Canceled without explicit intent.
     *
     * @return void
     */
    public function test_participant_save_accepted_preserves_status_without_cancel(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int)$bookingperson->id);
        $roomid = $this->create_room();
        $event = $this->create_cancel_only_event(
            $context,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            $roomid
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CONFIRMED, (int)$persisted->bookingstatus);
    }

    /**
     * Minor field edits must not change status when cancel is not selected.
     *
     * @return void
     */
    public function test_participant_save_in_progress_with_minor_notes_preserves_status(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int)$bookingperson->id);
        $roomid = $this->create_room();
        $event = $this->create_cancel_only_event(
            $context,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            ['notes' => 'Updated participant note']
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$persisted->bookingstatus);
    }

    /**
     * Support on site assigned to Accepted booking must preserve status on save.
     *
     * @return void
     */
    public function test_support_on_site_save_accepted_preserves_status_without_cancel(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int)$bookingperson->id);
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_cancel_only_event(
            $context,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            $roomid
        );

        $record = event_manager::get_event((int)$event->id);
        $record->supportpersons = (string)$supportuser->id;
        event_manager::save_event_with_lifecycle_tracking(
            bookit_event::from_record($record),
            $event,
            (int)$bookingperson->id,
            $context
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CONFIRMED, (int)$persisted->bookingstatus);
    }

    /**
     * Support on site assigned to In progress booking must preserve status on save.
     *
     * @return void
     */
    public function test_support_on_site_save_in_progress_preserves_status_without_cancel(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int)$bookingperson->id);
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_cancel_only_event(
            $context,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $record = event_manager::get_event((int)$event->id);
        $record->supportpersons = (string)$supportuser->id;
        event_manager::save_event_with_lifecycle_tracking(
            bookit_event::from_record($record),
            $event,
            (int)$bookingperson->id,
            $context
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$persisted->bookingstatus);
    }

    /**
     * Explicit cancel selection must still transition to Canceled.
     *
     * @return void
     */
    public function test_participant_explicit_cancel_transitions_to_canceled(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int)$bookingperson->id);
        $roomid = $this->create_room();
        $event = $this->create_cancel_only_event(
            $context,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_CANCELED
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CANCELED, (int)$persisted->bookingstatus);
    }

    /**
     * New booking person saves must keep status New.
     *
     * @return void
     */
    public function test_booking_person_save_new_preserves_new_status(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int)$bookingperson->id);
        $roomid = $this->create_room();

        $starttime = strtotime('+21 days 09:00:00');
        $endtime = strtotime('+21 days 11:00:00');

        $this->setUser($bookingperson);
        $event = new bookit_event(
            0,
            'New booking save',
            20261,
            1,
            $starttime,
            $endtime,
            120,
            $roomid,
            10,
            '',
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$bookingperson->id,
            '',
            0,
            'Initial note',
            '',
            '',
            0,
            0,
            null,
            (int)$bookingperson->id,
            (int)$bookingperson->id,
            time(),
            time(),
            [],
        );
        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, (int)$bookingperson->id, $context);

        $this->submit_edit_event_form(
            $context,
            (int)$saved->id,
            (int)$bookingperson->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            ['notes' => 'Edited note on new booking']
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $saved->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_NEW, (int)$persisted->bookingstatus);
    }
}
