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
 * Tests for support-on-site internal fields visibility in edit_event_form (Spec 065).
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
 * Form rendering and save tests for support-only internal fields behaviour.
 *
 * @covers \mod_bookit\form\edit_event_form::definition
 * @covers \mod_bookit\form\edit_event_form::process_dynamic_submission
 * @runTestsInSeparateProcesses
 */
final class edit_event_form_support_internal_fields_test extends advanced_testcase {
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
        set_config('calendar_optional_fields', 'refcourseid,notes', 'mod_bookit');
    }

    /**
     * Create a module context with bookit_supportonsite role available.
     *
     * @return context_module
     */
    private function create_support_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Support internal test']);
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
     * Create a service-team editor context.
     *
     * @param int $userid
     * @return context_module
     */
    private function create_service_context(int $userid): context_module {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($userid, $course->id);
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Service internal test',
        ]);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit service internal', 'bookitservice065', 'manager');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:editevent', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:editinternal', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
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
            'name' => 'Support internal room',
            'shortname' => 'SUP065',
        ]);
    }

    /**
     * Persist an event and transition it to the requested booking status.
     *
     * @param context_module $context
     * @param int $bookingpersonid
     * @param int|null $supportuserid
     * @param int $bookingstatus
     * @param int $roomid
     * @param array $internaloverrides
     * @return bookit_event
     */
    private function create_event(
        context_module $context,
        int $bookingpersonid,
        ?int $supportuserid,
        int $bookingstatus,
        int $roomid,
        array $internaloverrides = []
    ): bookit_event {
        $examiner = $this->getDataGenerator()->create_user();
        $refcourse = $this->getDataGenerator()->create_course(['fullname' => 'Exam course 065']);

        $starttime = strtotime('+14 days 09:00:00');
        $endtime = strtotime('+14 days 11:00:00');

        $this->setUser($bookingpersonid);
        $event = new bookit_event(
            0,
            'Support internal event',
            20261,
            1,
            $starttime,
            $endtime,
            120,
            $roomid,
            10,
            0,
            '',
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$examiner->id,
            '',
            0,
            'Participant notes',
            (string)($internaloverrides['internalnotes'] ?? 'Original internal notes'),
            (string)($internaloverrides['supportpersons'] ?? ''),
            (int)($internaloverrides['extratimebefore'] ?? 15),
            (int)($internaloverrides['extratimeafter'] ?? 30),
            (int)($internaloverrides['refcourseid'] ?? $refcourse->id),
            $bookingpersonid,
            time(),
            time(),
            [],
        );

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, $bookingpersonid, $context);
        if ($supportuserid !== null) {
            $withevent = bookit_event::from_database((int)$saved->id);
            $withevent->supportpersons = (string)$supportuserid;
            event_manager::save_event_with_lifecycle_tracking(
                $withevent,
                bookit_event::from_database((int)$saved->id),
                $bookingpersonid,
                $context
            );
        }

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
            'timecompensation' => $event->timecompensation,
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
        $submission = (object) array_merge((array) event_manager::get_event($eventid), $formdata);
        foreach ($overrides as $key => $value) {
            $submission->$key = $value;
        }
        $form->set_data($submission);

        $this->assertTrue($form->is_submitted(), 'Form was not marked as submitted.');
        if (!$form->is_validated()) {
            $mform = $this->get_mform($form);
            $this->fail('Form validation failed: ' . json_encode($mform->_errors ?? []));
        }
        $form->process_dynamic_submission();
    }

    /**
     * Support-only users must see the Internal fields header.
     *
     * @return void
     */
    public function test_support_assigned_sees_header_internal(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);

        $this->assertTrue($mform->elementExists('header_internal'));
    }

    /**
     * Support-only users see exam course as static read-only when enabled.
     *
     * @return void
     */
    public function test_support_sees_refcourseid_readonly_when_enabled(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $refcourse = $this->getDataGenerator()->create_course(['fullname' => 'Visible exam course']);
        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid,
            ['refcourseid' => (int)$refcourse->id]
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);

        $this->assertTrue($mform->elementExists('refcourseid_readonly'));
        $this->assertTrue($mform->elementExists('refcourseid'));
        $this->assertSame('hidden', $mform->getElementType('refcourseid'));
        $element = $mform->getElement('refcourseid_readonly');
        $this->assertStringContainsString('Visible exam course', (string)$element->_text);
    }

    /**
     * Support-only users see internal fields read-only except internal notes.
     *
     * @return void
     */
    public function test_support_internal_fields_readonly_except_notes(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);

        $this->assertTrue($mform->elementExists('supportpersons_readonly'));
        $this->assertTrue($mform->elementExists('extratimebefore_readonly'));
        $this->assertTrue($mform->elementExists('extratimeafter_readonly'));
        $this->assertTrue($mform->elementExists('bookingstatusreadonly'));
        $this->assertTrue($mform->elementExists('internalnotes'));
        $this->assertNotSame('select', $mform->getElementType('bookingstatus') ?? '');
    }

    /**
     * Tampered internal field submissions must not change persisted values.
     *
     * @return void
     */
    public function test_support_tamper_internal_fields_ignored_on_save(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $refcourse = $this->getDataGenerator()->create_course(['fullname' => 'Original exam course']);
        $tampercourse = $this->getDataGenerator()->create_course(['fullname' => 'Tampered exam course']);
        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid,
            ['refcourseid' => (int)$refcourse->id]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$supportuser->id,
            [
                'internalnotes' => 'Tamper attempt note',
                'refcourseid' => (int)$tampercourse->id,
                'supportpersons' => (string)$otheruser->id,
                'extratimebefore' => 99,
                'extratimeafter' => 88,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_CANCELED,
            ]
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame((int)$refcourse->id, (int)$persisted->refcourseid);
        $this->assertSame((string)$supportuser->id, $persisted->supportpersons);
        $this->assertSame(15, (int)$persisted->extratimebefore);
        $this->assertSame(30, (int)$persisted->extratimeafter);
        $this->assertSame(event_access_manager::BOOKINGSTATUS_IN_PROGRESS, (int)$persisted->bookingstatus);
        $this->assertSame('Tamper attempt note', $persisted->internalnotes);
    }

    /**
     * Support may save internal notes without changing other internal values.
     *
     * @return void
     */
    public function test_support_save_internal_notes_only(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
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
            ['internalnotes' => 'Updated support note only']
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame('Updated support note only', $persisted->internalnotes);
        $this->assertSame((string)$supportuser->id, $persisted->supportpersons);
        $this->assertSame(15, (int)$persisted->extratimebefore);
        $this->assertSame(30, (int)$persisted->extratimeafter);
    }

    /**
     * New status keeps support internal visibility rules consistent.
     *
     * @return void
     */
    public function test_support_internal_section_new_status(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
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
    }

    /**
     * In progress status shows grouped internal fields for support.
     *
     * @return void
     */
    public function test_support_internal_section_in_progress_status(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);
        $this->assertTrue($mform->elementExists('header_internal'));
        $this->assertTrue($mform->elementExists('refcourseid_readonly'));
    }

    /**
     * Accepted status keeps internal section visible read-only for support.
     *
     * @return void
     */
    public function test_support_internal_section_accepted_status(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            $roomid
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);
        $this->assertTrue($mform->elementExists('header_internal'));
        $this->assertTrue($mform->elementExists('bookingstatusreadonly'));
    }

    /**
     * Service team users can still edit internal fields.
     *
     * @return void
     */
    public function test_service_team_can_edit_internal_fields(): void {
        global $DB;

        $editor = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_service_context((int)$editor->id);
        $roomid = $this->create_room();
        $refcourse = $this->getDataGenerator()->create_course(['fullname' => 'Service exam course']);
        $newcourse = $this->getDataGenerator()->create_course(['fullname' => 'Updated exam course']);

        $event = $this->create_event(
            $context,
            (int)$editor->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid,
            ['refcourseid' => (int)$refcourse->id]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$editor->id,
            [
                'refcourseid' => (int)$newcourse->id,
                'supportpersons' => (string)$supportuser->id,
            ]
        );

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame((int)$newcourse->id, (int)$persisted->refcourseid);
        $this->assertSame((string)$supportuser->id, $persisted->supportpersons);
    }

    /**
     * Unassigned support users must not see the internal fields header.
     *
     * @return void
     */
    public function test_unassigned_support_no_header_internal(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            null,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid
        );

        $record = event_manager::get_event((int)$event->id);
        $this->assertFalse(
            event_access_manager::can_supportperson_view_internal_fields($record, $context, (int)$supportuser->id)
        );
        $this->assertFalse(
            event_access_manager::can_user_view_event_details($record, $context, (int)$supportuser->id)
        );

        try {
            $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
            $this->fail('Expected permission exception when unassigned support opens event details.');
        } catch (\moodle_exception $exception) {
            $this->assertStringContainsString('nopermissions', $exception->errorcode);
        }
    }

    /**
     * Unassigned support tamper attempts must not persist internal values.
     *
     * @return void
     */
    public function test_unassigned_support_tamper_internal_fields_ignored(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $roomid = $this->create_room();
        $event = $this->create_event(
            $context,
            (int)$bookingperson->id,
            null,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            $roomid,
            ['internalnotes' => 'Original notes']
        );

        try {
            $this->submit_edit_event_form(
                $context,
                (int)$event->id,
                (int)$supportuser->id,
                [
                    'internalnotes' => 'Should not save',
                    'refcourseid' => 999,
                    'supportpersons' => (string)$supportuser->id,
                ]
            );
            $this->fail('Expected permission exception for unassigned support save.');
        } catch (\moodle_exception $exception) {
            $this->assertStringContainsString('nopermissions', $exception->errorcode);
        }

        $persisted = $DB->get_record('bookit_event', ['id' => $event->id], '*', MUST_EXIST);
        $this->assertSame('Original notes', $persisted->internalnotes);
        $this->assertSame('', $persisted->supportpersons);
    }
}
