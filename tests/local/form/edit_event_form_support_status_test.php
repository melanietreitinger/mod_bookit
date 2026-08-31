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
 * Tests for support-on-site read-only booking status in edit_event_form (Spec 063).
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
use MoodleQuickForm;

/**
 * Form rendering and save tests for support-only booking status read-only behaviour.
 *
 * @covers \mod_bookit\form\edit_event_form::definition
 * @covers \mod_bookit\form\edit_event_form::process_dynamic_submission
 * @runTestsInSeparateProcesses
 */
final class edit_event_form_support_status_test extends advanced_testcase {
    /**
     * Create a module context with bookit_supportonsite role available.
     *
     * @return context_module
     */
    private function create_support_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Support status test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $this->ensure_support_capabilities($context);

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
     * Create a participant context for dual-role cancel scenarios.
     *
     * @return context_module
     */
    private function create_participant_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Dual role test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant dual', 'bookitparticipantdual063', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Assign the dual participant role.
     *
     * @param context_module $context
     * @param int $userid
     * @return void
     */
    private function assign_participant_role(context_module $context, int $userid): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'bookitparticipantdual063'], 'id', MUST_EXIST);
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
            'name' => 'Support status room',
            'shortname' => 'SUP063',
        ]);
    }

    /**
     * Persist an event and transition it to the requested booking status.
     *
     * @param context_module $context
     * @param int $bookingpersonid
     * @param int $supportuserid
     * @param int $bookingstatus
     * @param int $roomid
     * @return bookit_event
     */
    private function create_support_assigned_event(
        context_module $context,
        int $bookingpersonid,
        int $supportuserid,
        int $bookingstatus,
        int $roomid
    ): bookit_event {
        $examiner = $this->getDataGenerator()->create_user();

        $starttime = strtotime('+14 days 09:00:00');
        $endtime = strtotime('+14 days 11:00:00');

        $this->setUser($bookingpersonid);
        $event = new bookit_event(
            0,
            'Support status event',
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
        $record->supportpersons = (string)$supportuserid;
        event_manager::save_event_with_lifecycle_tracking(
            bookit_event::from_record($record),
            bookit_event::from_database((int)$saved->id),
            $bookingpersonid,
            $context
        );

        if ($bookingstatus !== event_access_manager::BOOKINGSTATUS_NEW) {
            $record = event_manager::get_event((int)$saved->id);
            event_manager::transition_booking_status(
                $record,
                $bookingstatus,
                $bookingpersonid,
                $context
            );
        }

        return bookit_event::from_database((int)$saved->id);
    }

    /**
     * Build form submission payload from a persisted event.
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
     * Instantiate the edit form for inspection.
     *
     * @param context_module $context
     * @param int $eventid
     * @param int $userid
     * @return edit_event_form
     */
    private function create_rendered_form(context_module $context, int $eventid, int $userid): edit_event_form {
        $this->setUser($userid);

        $formdata = [
            'cmid' => $context->instanceid,
            'id' => $eventid,
            '_qf__mod_bookit_form_edit_event_form' => 1,
            'sesskey' => sesskey(),
        ];
        $_POST['sesskey'] = $formdata['sesskey'];

        $form = new edit_event_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();

        return $form;
    }

    /**
     * Return the underlying MoodleQuickForm from a dynamic form instance.
     *
     * @param edit_event_form $form
     * @return MoodleQuickForm
     */
    private function get_mform(edit_event_form $form): MoodleQuickForm {
        $reflection = new \ReflectionClass($form);
        $mformproperty = $reflection->getProperty('_form');
        $mformproperty->setAccessible(true);

        return $mformproperty->getValue($form);
    }

    /**
     * Submit the edit event form via the core dynamic form API.
     *
     * @param context_module $context
     * @param int $eventid
     * @param int $userid
     * @param array $overrides
     * @return void
     */
    private function submit_edit_event_form(
        context_module $context,
        int $eventid,
        int $userid,
        array $overrides = []
    ): void {
        $this->setUser($userid);

        $event = event_manager::get_event($eventid);
        $formdata = $this->build_formdata_from_event($event, $context, $overrides);
        $formdata['sesskey'] = sesskey();
        $_POST['sesskey'] = $formdata['sesskey'];

        $form = new edit_event_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_submitted(), 'Form was not marked as submitted.');
        if (!$form->is_validated()) {
            $mform = $this->get_mform($form);
            $this->fail('Form validation failed: ' . json_encode($mform->_errors ?? []));
        }
        $form->process_dynamic_submission();
    }

    /**
     * Assert the booking status is rendered as static read-only text.
     *
     * @param edit_event_form $form
     * @return void
     */
    private function assert_booking_status_is_readonly(edit_event_form $form): void {
        $mform = $this->get_mform($form);
        $this->assertTrue($mform->elementExists('bookingstatusreadonly'));
        if ($mform->elementExists('bookingstatus')) {
            $this->assertNotSame('select', $mform->getElementType('bookingstatus'));
        }
    }

    /**
     * Assert the booking status select is present (editable or cancel-only).
     *
     * @param edit_event_form $form
     * @return void
     */
    private function assert_booking_status_is_select(edit_event_form $form): void {
        $mform = $this->get_mform($form);
        $this->assertTrue($mform->elementExists('bookingstatus'));
        $this->assertSame('select', $mform->getElementType('bookingstatus'));
    }

    /**
     * Read the static booking status label from the rendered form.
     *
     * @param edit_event_form $form
     * @return string
     */
    private function get_readonly_status_label(edit_event_form $form): string {
        $mform = $this->get_mform($form);
        $element = $mform->getElement('bookingstatusreadonly');

        return (string)$element->_text;
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
        set_config('calendar_optional_fields', 'term,department,otherexaminers,notes,internalnotes', 'mod_bookit');
    }

    /**
     * Support-only users must see static booking status, not a select.
     *
     * @return void
     */
    public function test_support_only_form_uses_readonly_status_element(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_support_assigned_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $this->assert_booking_status_is_readonly($form);
    }

    /**
     * Support-only users cannot open New booking details; status edit must stay false.
     *
     * @return void
     */
    public function test_support_readonly_status_label_new(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_support_assigned_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            $roomid
        );

        $record = event_manager::get_event((int)$event->id);
        $this->assertFalse(
            event_access_manager::can_user_view_event_details($record, $context, (int)$supportuser->id)
        );
        $this->assertFalse(
            event_access_manager::can_user_edit_booking_status($record, $context, (int)$supportuser->id)
        );
    }

    /**
     * Support-only users see the In progress status label as static text.
     *
     * @return void
     */
    public function test_support_readonly_status_label_in_progress(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_support_assigned_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $this->assertSame(
            get_string('event_bookingstatus_' . event_access_manager::BOOKINGSTATUS_IN_PROGRESS, 'mod_bookit'),
            $this->get_readonly_status_label($form)
        );
    }

    /**
     * Support-only users see the Accepted status label as static text.
     *
     * @return void
     */
    public function test_support_readonly_status_label_accepted(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_support_assigned_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $this->assertSame(
            get_string('event_bookingstatus_' . event_access_manager::BOOKINGSTATUS_CONFIRMED, 'mod_bookit'),
            $this->get_readonly_status_label($form)
        );
    }

    /**
     * Support may save internal notes without changing booking status.
     *
     * @return void
     */
    public function test_support_save_preserves_status_when_editing_notes(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_support_assigned_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$supportuser->id,
            ['internalnotes' => 'Updated support note']
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$persisted->bookingstatus);
        $this->assertSame('Updated support note', $persisted->internalnotes);
    }

    /**
     * Tampered canceled status submissions must not change the persisted status.
     *
     * @return void
     */
    public function test_support_tamper_canceled_status_ignored_on_save(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_support_assigned_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            $roomid
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$supportuser->id,
            ['bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED]
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_CONFIRMED, (int)$persisted->bookingstatus);
    }

    /**
     * Terminal canceled and rejected bookings keep support status edit disabled.
     *
     * @return void
     */
    public function test_support_readonly_status_canceled_and_rejected(): void {
        $bookingstatuses = [
                event_access_manager::BOOKINGSTATUS_CANCELED,
                event_access_manager::BOOKINGSTATUS_REJECTED,
        ];
        foreach ($bookingstatuses as $bookingstatus) {
            $bookingperson = $this->getDataGenerator()->create_user();
            $supportuser = $this->getDataGenerator()->create_user();
            $context = $this->create_support_context();
            $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
            $this->assign_support_role($context, (int)$supportuser->id);

            $roomid = $this->create_room();
            $event = $this->create_support_assigned_event(
                $context,
                (int)$bookingperson->id,
                (int)$supportuser->id,
                $bookingstatus,
                $roomid
            );

            $record = event_manager::get_event((int)$event->id);
            $this->assertFalse(
                event_access_manager::can_user_edit_booking_status($record, $context, (int)$supportuser->id)
            );
        }
    }

    /**
     * Dual-role support and booker on New must still expose cancel via status select.
     *
     * @return void
     */
    public function test_dual_role_support_and_booker_cancel_shows_status_select(): void {
        $user = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context();
        $this->getDataGenerator()->enrol_user((int)$user->id, $context->get_course_context()->instanceid);
        $this->assign_participant_role($context, (int)$user->id);
        $this->assign_support_role($context, (int)$user->id);

        $roomid = $this->create_room();
        $event = $this->create_support_assigned_event(
            $context,
            (int)$user->id,
            (int)$user->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$user->id);
        $this->assert_booking_status_is_select($form);
        $this->assertTrue(
            event_access_manager::can_self_cancel_new_request(
                event_manager::get_event((int)$event->id),
                $context,
                (int)$user->id
            )
        );
    }
}
