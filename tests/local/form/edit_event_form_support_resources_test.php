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
 * Tests for support-on-site read-only resources in edit_event_form (Spec 064).
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
 * Form rendering and save tests for support-only resource read-only behaviour.
 *
 * @covers \mod_bookit\form\edit_event_form::definition
 * @covers \mod_bookit\form\edit_event_form::process_dynamic_submission
 * @runTestsInSeparateProcesses
 */
final class edit_event_form_support_resources_test extends advanced_testcase {
    /** @var int */
    private int $resourceid;

    /** @var int */
    private int $resourceid2;

    /** @var int */
    private int $roomid;

    /**
     * Enable resources and create quantity-based fixtures.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        message_update_providers('mod_bookit');
        install_helper::ensure_booking_status_notification_defaults();
        set_config(install_helper::CONFIG_RESOURCES_ENABLED, 1, 'mod_bookit');

        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $this->roomid = $generator->create_room([
            'name' => 'Support resources room',
            'shortname' => 'SUP064',
        ]);
        $generator->create_resource_category(['name' => 'Support equipment']);
        $this->resourceid = $generator->create_resource([
            'name' => 'Support projector',
            'category_name' => 'Support equipment',
            'amount' => 5,
            'amountirrelevant' => false,
        ]);
        $this->resourceid2 = $generator->create_resource([
            'name' => 'Support laptop',
            'category_name' => 'Support equipment',
            'amount' => 3,
            'amountirrelevant' => false,
        ]);
    }

    /**
     * Create a module context with bookit_supportonsite role available.
     *
     * @return context_module
     */
    private function create_support_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Support resources test']);
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
     * Create a participant context for booker scenarios.
     *
     * @return context_module
     */
    private function create_participant_context(): context_module {
        $course = $this->getDataGenerator()->create_course();
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Booker resources test']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant resources', 'bookitparticipant064', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Assign the participant role.
     *
     * @param context_module $context
     * @param int $userid
     * @return void
     */
    private function assign_participant_role(context_module $context, int $userid): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'bookitparticipant064'], 'id', MUST_EXIST);
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
            'name' => 'Service resources test',
        ]);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit service resources', 'bookitservice064', 'manager');
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
        return $this->roomid;
    }

    /**
     * Persist an event with optional resources and support assignment.
     *
     * @param context_module $context
     * @param int $bookingpersonid
     * @param int|null $supportuserid
     * @param int $bookingstatus
     * @param array $resourceamounts Map of resourceid => amount
     * @return bookit_event
     */
    private function create_event_with_resources(
        context_module $context,
        int $bookingpersonid,
        ?int $supportuserid,
        int $bookingstatus,
        array $resourceamounts = []
    ): bookit_event {
        $examiner = $this->getDataGenerator()->create_user();
        $starttime = strtotime('+14 days 09:00:00');
        $endtime = strtotime('+14 days 11:00:00');
        $mappings = [];
        foreach ($resourceamounts as $resourceid => $amount) {
            $mappings[] = (object)['resourceid' => (int)$resourceid, 'amount' => (int)$amount];
        }

        $this->setUser($bookingpersonid);
        $event = new bookit_event(
            0,
            'Support resources event',
            20261,
            1,
            $starttime,
            $endtime,
            120,
            $this->create_room(),
            10,
            0,
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
            time(),
            time(),
            $mappings,
        );

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, $bookingpersonid, $context);
        $record = event_manager::get_event((int)$saved->id);
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
            'bookit_resources_section' => '1',
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

        $loaded = event_manager::get_event((int)$event->id);
        foreach (get_object_vars($loaded) as $key => $value) {
            if (str_starts_with($key, 'checkbox_') || str_starts_with($key, 'resource_')) {
                $data[$key] = $value;
            }
        }

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
            if ($value === null) {
                unset($submission->$key);
            } else {
                $submission->$key = $value;
            }
        }
        if (array_key_exists('editevent', $overrides) && (int) $overrides['editevent'] === 0) {
            $this->strip_resource_checkbox_fields($submission);
            unset($submission->bookit_resources_section);
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
     * Remove resource checkbox defaults from a form data object.
     *
     * @param \stdClass $data
     * @return void
     */
    private function strip_resource_checkbox_fields(\stdClass $data): void {
        foreach (array_keys((array) $data) as $key) {
            if (str_starts_with($key, 'checkbox_') || str_starts_with($key, 'resource_')) {
                unset($data->$key);
            }
        }
    }

    /**
     * Normalise hidden editevent element value from MoodleQuickForm.
     *
     * @param mixed $value
     * @return int
     */
    private function normalise_editevent_value(mixed $value): int {
        if (is_array($value)) {
            return (int)($value[0] ?? 0);
        }

        return (int)$value;
    }

    /**
     * Assert a field is disabled when editevent is not set.
     *
     * @param MoodleQuickForm $mform
     * @param string $fieldname
     * @return void
     */
    private function assert_field_disabled_if_editevent_neq(MoodleQuickForm $mform, string $fieldname): void {
        $dependencies = $mform->_dependencies['editevent']['neq'] ?? [];
        $found = false;
        foreach ($dependencies as $elements) {
            if (in_array($fieldname, $elements, true)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Missing disabledIf(editevent, neq) for {$fieldname}");
    }

    /**
     * Assert support-only users cannot edit resource controls.
     *
     * @return void
     */
    public function test_support_only_form_disables_resource_quantity_fields(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid => 2, $this->resourceid2 => 1]
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);

        $this->assertSame(0, $this->normalise_editevent_value($mform->getElementValue('editevent')));
        $this->assert_field_disabled_if_editevent_neq($mform, 'checkbox_' . $this->resourceid);
        $this->assert_field_disabled_if_editevent_neq($mform, 'checkbox_' . $this->resourceid2);
        $this->assert_field_disabled_if_editevent_neq($mform, 'resource_' . $this->resourceid);
        $this->assert_field_disabled_if_editevent_neq($mform, 'resource_' . $this->resourceid2);
    }

    /**
     * Booked resource amounts remain visible in the loaded event data.
     *
     * @return void
     */
    public function test_support_readonly_shows_booked_amount(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid => 3]
        );

        $loaded = event_manager::get_event((int)$event->id);
        $amountfield = 'resource_' . $this->resourceid;
        $this->assertObjectHasProperty($amountfield, $loaded);
        $this->assertSame(3, (int)$loaded->$amountfield);
    }

    /**
     * Support-only users cannot view or edit resources on New bookings.
     *
     * @return void
     */
    public function test_support_resources_readonly_new_status(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            [$this->resourceid => 2]
        );

        $record = event_manager::get_event((int)$event->id);
        $this->assertFalse(
            event_access_manager::can_user_view_event_details($record, $context, (int)$supportuser->id)
        );
        $this->assertFalse(
            event_access_manager::can_user_edit_event_resources($record, $context, (int)$supportuser->id)
        );
    }

    /**
     * Support-only users cannot edit resources on In progress bookings.
     *
     * @return void
     */
    public function test_support_resources_readonly_in_progress_status(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid => 2]
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);
        $this->assert_field_disabled_if_editevent_neq($mform, 'resource_' . $this->resourceid);
    }

    /**
     * Support may save internal notes without changing resource mappings.
     *
     * @return void
     */
    public function test_support_save_preserves_resource_mappings_when_editing_notes(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid => 2, $this->resourceid2 => 1]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$supportuser->id,
            ['internalnotes' => 'Updated support note']
        );

        $this->assertSame(2, (int)$DB->get_field(
            'bookit_event_resource',
            'amount',
            ['eventid' => $event->id, 'resourceid' => $this->resourceid]
        ));
        $this->assertSame(1, (int)$DB->get_field(
            'bookit_event_resource',
            'amount',
            ['eventid' => $event->id, 'resourceid' => $this->resourceid2]
        ));
        $this->assertSame('Updated support note', $DB->get_field('bookit_event', 'internalnotes', ['id' => $event->id]));
    }

    /**
     * Tampered resource submissions must not change persisted mappings for support-only users.
     *
     * @return void
     */
    public function test_support_tamper_resource_amount_ignored_on_save(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid => 2]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$supportuser->id,
            [
                'internalnotes' => 'Tamper attempt',
                'editevent' => 1,
                'checkbox_' . $this->resourceid => 1,
                'resource_' . $this->resourceid => 99,
            ]
        );

        $this->assertSame(2, (int)$DB->get_field(
            'bookit_event_resource',
            'amount',
            ['eventid' => $event->id, 'resourceid' => $this->resourceid]
        ));
    }

    /**
     * Accepted bookings keep static resource display for support users.
     *
     * @return void
     */
    public function test_support_accepted_uses_bookingcompleted_static(): void {
        $bookingperson = $this->getDataGenerator()->create_user();
        $supportuser = $this->getDataGenerator()->create_user();
        $context = $this->create_support_context();
        $this->getDataGenerator()->enrol_user((int)$supportuser->id, $context->get_course_context()->instanceid);
        $this->assign_support_role($context, (int)$supportuser->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$bookingperson->id,
            (int)$supportuser->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            [$this->resourceid => 2]
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$supportuser->id);
        $mform = $this->get_mform($form);

        $this->assertTrue($mform->elementExists('resourcestatus_' . $this->resourceid));
        $this->assertFalse($mform->elementExists('checkbox_' . $this->resourceid));
        $this->assertFalse($mform->elementExists('resource_' . $this->resourceid));
    }

    /**
     * Service-team users can edit and persist resource quantities.
     *
     * @return void
     */
    public function test_service_team_can_edit_resource_quantity(): void {
        global $DB;

        $editor = $this->getDataGenerator()->create_user();
        $context = $this->create_service_context((int)$editor->id);
        $event = $this->create_event_with_resources(
            $context,
            (int)$editor->id,
            null,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid => 2]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$editor->id,
            [
                'checkbox_' . $this->resourceid => 1,
                'resource_' . $this->resourceid => 4,
            ]
        );

        $this->assertSame(4, (int)$DB->get_field(
            'bookit_event_resource',
            'amount',
            ['eventid' => $event->id, 'resourceid' => $this->resourceid]
        ));
    }

    /**
     * Booking persons may edit resources on New requests.
     *
     * @return void
     */
    public function test_booker_new_can_edit_resources(): void {
        global $DB;

        $booker = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context();
        $this->getDataGenerator()->enrol_user((int)$booker->id, $context->get_course_context()->instanceid);
        $this->assign_participant_role($context, (int)$booker->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$booker->id,
            null,
            event_access_manager::BOOKINGSTATUS_NEW,
            [$this->resourceid => 2]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$booker->id,
            [
                'checkbox_' . $this->resourceid => 1,
                'resource_' . $this->resourceid => 3,
            ]
        );

        $this->assertSame(3, (int)$DB->get_field(
            'bookit_event_resource',
            'amount',
            ['eventid' => $event->id, 'resourceid' => $this->resourceid]
        ));
    }

    /**
     * Dual-role support+booker users keep editable resources on New bookings.
     *
     * @return void
     */
    public function test_dual_role_support_and_booker_shows_editable_resources(): void {
        $booker = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context();
        $this->getDataGenerator()->enrol_user((int)$booker->id, $context->get_course_context()->instanceid);
        $this->assign_participant_role($context, (int)$booker->id);
        $this->ensure_support_capabilities($context);
        $this->assign_support_role($context, (int)$booker->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$booker->id,
            (int)$booker->id,
            event_access_manager::BOOKINGSTATUS_NEW,
            [$this->resourceid => 2]
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$booker->id);
        $mform = $this->get_mform($form);

        $this->assertSame(1, $this->normalise_editevent_value($mform->getElementValue('editevent')));
        $this->assertTrue($mform->elementExists('resourcegroup_' . $this->resourceid));
    }

    /**
     * Accepted bookings remain read-only for bookers without service edit rights.
     *
     * @return void
     */
    public function test_booker_accepted_resources_follow_participant_rules(): void {
        $booker = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context();
        $this->getDataGenerator()->enrol_user((int)$booker->id, $context->get_course_context()->instanceid);
        $this->assign_participant_role($context, (int)$booker->id);

        $event = $this->create_event_with_resources(
            $context,
            (int)$booker->id,
            null,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            [$this->resourceid => 2]
        );

        $form = $this->create_rendered_form($context, (int)$event->id, (int)$booker->id);
        $mform = $this->get_mform($form);

        $this->assertTrue($mform->elementExists('resourcestatus_' . $this->resourceid));
        $this->assertFalse($mform->elementExists('checkbox_' . $this->resourceid));
    }
}
