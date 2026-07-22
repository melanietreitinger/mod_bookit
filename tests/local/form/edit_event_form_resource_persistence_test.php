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
 * Tests for event form resource persistence (Spec 052).
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
use mod_bookit\local\manager\event_resource_manager;

/**
 * Resource mapping persistence on edit_event_form save.
 *
 * @covers \mod_bookit\form\edit_event_form::process_dynamic_submission
 * @runTestsInSeparateProcesses
 */
final class edit_event_form_resource_persistence_test extends advanced_testcase {
    /** @var int */
    private int $resourceid;

    /** @var int */
    private int $resourceid2;

    /** @var int */
    private int $roomid;

    /**
     * Set up resources-enabled Bookit fixtures.
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
        set_config(install_helper::CONFIG_RESOURCES_ENABLED, 1, 'mod_bookit');

        /** @var \mod_bookit_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_bookit');
        $this->roomid = $generator->create_room([
            'name' => 'Persistence room',
            'shortname' => 'PRS001',
        ]);
        $generator->create_resource_category(['name' => 'Equipment']);
        $this->resourceid = $generator->create_resource([
            'name' => 'Persistence projector',
            'category_name' => 'Equipment',
            'amount' => 5,
            'amountirrelevant' => true,
        ]);
        $this->resourceid2 = $generator->create_resource([
            'name' => 'Persistence laptop',
            'category_name' => 'Equipment',
            'amount' => 3,
            'amountirrelevant' => true,
        ]);
    }

    /**
     * Create a service-team editor context.
     *
     * @param int $userid
     * @return context_module
     */
    private function create_editor_context(int $userid): context_module {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($userid, $course->id);
        $bookit = $this->getDataGenerator()->create_module('bookit', [
            'course' => $course->id,
            'name' => 'Resource persistence test',
        ]);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit resource editor', 'bookitresourceeditor', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:addevent', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:editevent', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:editinternal', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Build form payload from a persisted event.
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
            'editevent' => 1,
            'editinternal' => 1,
            'editinternalnotes' => 1,
            'editbookingstatus' => 1,
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

        return array_merge($data, $overrides);
    }

    /**
     * Submit the edit event form.
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
        if (array_key_exists('bookit_resources_section', $overrides) && $overrides['bookit_resources_section'] === null) {
            $this->strip_resource_checkbox_fields($submission);
        }
        if (array_key_exists('editevent', $overrides) && (int) $overrides['editevent'] === 0) {
            $this->strip_resource_checkbox_fields($submission);
            unset($submission->bookit_resources_section);
        }
        $form->set_data($submission);

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
     * Remove resource checkbox defaults from a form data object.
     *
     * @param \stdClass $data
     * @return void
     */
    private function strip_resource_checkbox_fields(\stdClass $data): void {
        foreach (array_keys((array) $data) as $key) {
            if (str_starts_with($key, 'checkbox_')) {
                unset($data->$key);
            }
        }
    }

    /**
     * Return assigned resource IDs for an event.
     *
     * @param int $eventid
     * @return int[]
     */
    private function get_assigned_resource_ids(int $eventid): array {
        global $DB;
        $rows = $DB->get_records('bookit_event_resource', ['eventid' => $eventid]);
        return array_map(static fn(\stdClass $row): int => (int) $row->resourceid, array_values($rows));
    }

    /**
     * Create participant context for cancel-only scenarios.
     *
     * @param int $userid
     * @return context_module
     */
    private function create_participant_context(int $userid): context_module {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($userid, $course->id);
        $bookit = $this->getDataGenerator()->create_module('bookit', ['course' => $course->id, 'name' => 'Participant resource']);
        $context = context_module::instance($bookit->cmid);

        \update_capabilities('mod_bookit');
        $roleid = \create_role('Bookit participant resource', 'bookitparticipantresource', 'student');
        \assign_capability('mod/bookit:view', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewownoverview', CAP_ALLOW, $roleid, $context->id, true);
        \assign_capability('mod/bookit:viewalldetailsofownevent', CAP_ALLOW, $roleid, $context->id, true);
        \role_assign($roleid, $userid, $context->id);
        \accesslib_clear_all_caches_for_unit_testing();

        return $context;
    }

    /**
     * Create a persisted event with optional resources.
     *
     * @param context_module $context
     * @param int $userid
     * @param int $bookingstatus
     * @param array $resourceids
     * @return bookit_event
     */
    private function create_event_with_resources(
        context_module $context,
        int $userid,
        int $bookingstatus,
        array $resourceids = []
    ): bookit_event {
        $examiner = $this->getDataGenerator()->create_user();
        $starttime = strtotime('+14 days 09:00:00');
        $endtime = strtotime('+14 days 11:00:00');
        $mappings = [];
        foreach ($resourceids as $rid) {
            $mappings[] = (object)['resourceid' => (int)$rid, 'amount' => 1];
        }

        $this->setUser($userid);
        $event = new bookit_event(
            0,
            'Resource persistence event',
            20261,
            1,
            $starttime,
            $endtime,
            120,
            $this->roomid,
            10,
            0,
            '',
            event_access_manager::BOOKINGSTATUS_NEW,
            (int)$examiner->id,
            '',
            0,
            'Notes',
            '',
            '',
            0,
            0,
            null,
            $userid,
            $userid,
            time(),
            time(),
            $mappings,
        );

        $saved = event_manager::save_event_with_lifecycle_tracking($event, null, $userid, $context);
        if ($bookingstatus !== event_access_manager::BOOKINGSTATUS_NEW) {
            $record = event_manager::get_event((int)$saved->id);
            event_manager::transition_booking_status($record, $bookingstatus, $userid, $context);
        }

        return bookit_event::from_database((int)$saved->id);
    }

    /**
     * New booking with checked resource persists mapping.
     *
     * @return void
     */
    public function test_save_new_event_with_resource_checkbox_persists_mapping(): void {
        global $DB;

        $editor = $this->getDataGenerator()->create_user();
        $context = $this->create_editor_context((int)$editor->id);
        $examiner = $this->getDataGenerator()->create_user();

        $starttime = strtotime('+21 days 09:00:00');
        $formdata = [
            'cmid' => $context->instanceid,
            'id' => 0,
            '_qf__mod_bookit_form_edit_event_form' => 1,
            'editevent' => 1,
            'editinternal' => 1,
            'editinternalnotes' => 1,
            'editbookingstatus' => 1,
            'bookit_resources_section' => '1',
            'name' => 'New resource booking',
            'semester' => 20261,
            'institutionid' => 1,
            'roomid' => $this->roomid,
            'duration' => 120,
            'participantsamount' => 10,
            'personinchargeid' => (int)$examiner->id,
            'otherexaminers' => '',
            'supportpersons' => '',
            'coursetemplate' => 0,
            'timecompensation' => 0,
            'compensationfordisadvantages' => '',
            'notes' => 'Initial',
            'internalnotes' => '',
            'refcourseid' => 0,
            'extratimebefore' => 0,
            'extratimeafter' => 0,
            'bookingstatus' => event_access_manager::BOOKINGSTATUS_NEW,
            'starttime' => $starttime,
            'usermodified' => (int) $editor->id,
            'startdate' => [
                'year' => (int)userdate($starttime, '%Y'),
                'month' => (int)userdate($starttime, '%m'),
                'day' => (int)userdate($starttime, '%d'),
            ],
            'checkbox_' . $this->resourceid => 1,
            'submitbutton' => 'Save changes',
        ];
        $this->setUser((int)$editor->id);
        $formdata['sesskey'] = sesskey();
        $_POST['sesskey'] = $formdata['sesskey'];

        $form = new edit_event_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_submitted(), 'Form was not marked as submitted.');
        $this->assertTrue($form->is_validated());
        $form->process_dynamic_submission();

        $events = $DB->get_records_sql(
            'SELECT * FROM {bookit_event} WHERE ' . $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name'),
            ['name' => 'New resource booking']
        );
        $this->assertCount(1, $events);
        $eventid = (int)reset($events)->id;
        $rows = $DB->get_records('bookit_event_resource', ['eventid' => $eventid]);
        $this->assertCount(1, $rows);
        $this->assertContains($this->resourceid, $this->get_assigned_resource_ids($eventid));

        $reloaded = event_manager::get_event($eventid);
        $checkboxfield = 'checkbox_' . $this->resourceid;
        $this->assertObjectHasProperty($checkboxfield, $reloaded);
        $this->assertEquals(1, $reloaded->$checkboxfield);
    }

    /**
     * Save without resource checkbox POST keys preserves existing resources.
     *
     * @return void
     */
    public function test_save_existing_event_without_checkbox_post_preserves_resources(): void {
        global $DB;

        $editor = $this->getDataGenerator()->create_user();
        $context = $this->create_editor_context((int)$editor->id);
        $event = $this->create_event_with_resources(
            $context,
            (int)$editor->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$editor->id,
            [
                'notes' => 'Updated notes only',
                'bookit_resources_section' => null,
            ]
        );

        $rows = $DB->get_records('bookit_event_resource', ['eventid' => $event->id]);
        $this->assertCount(1, $rows);
        $this->assertContains($this->resourceid, $this->get_assigned_resource_ids((int) $event->id));
    }

    /**
     * Accepted booking save preserves read-only resources.
     *
     * @return void
     */
    public function test_save_accepted_event_preserves_resources(): void {
        global $DB;

        $editor = $this->getDataGenerator()->create_user();
        $context = $this->create_editor_context((int)$editor->id);
        $event = $this->create_event_with_resources(
            $context,
            (int)$editor->id,
            event_access_manager::BOOKINGSTATUS_CONFIRMED,
            [$this->resourceid]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$editor->id,
            ['internalnotes' => 'Accepted internal note']
        );

        $rows = $DB->get_records('bookit_event_resource', ['eventid' => $event->id]);
        $this->assertCount(1, $rows);
        $this->assertContains($this->resourceid, $this->get_assigned_resource_ids((int) $event->id));
    }

    /**
     * Submitting only one resource checkbox removes the other mapping.
     *
     * @return void
     */
    public function test_explicit_uncheck_removes_resource(): void {
        global $DB;

        $editor = $this->getDataGenerator()->create_user();
        $context = $this->create_editor_context((int)$editor->id);
        $event = $this->create_event_with_resources(
            $context,
            (int)$editor->id,
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            [$this->resourceid, $this->resourceid2]
        );

        $this->submit_edit_event_form(
            $context,
            (int)$event->id,
            (int)$editor->id,
            [
                'checkbox_' . $this->resourceid => 1,
                'checkbox_' . $this->resourceid2 => 0,
            ]
        );

        $rows = $DB->get_records('bookit_event_resource', ['eventid' => $event->id]);
        $this->assertCount(1, $rows);
        $assigned = $this->get_assigned_resource_ids((int) $event->id);
        $this->assertContains($this->resourceid, $assigned);
        $this->assertNotContains($this->resourceid2, $assigned);
    }

    /**
     * Cancel-only participant save preserves resources.
     *
     * @return void
     */
    public function test_cancel_only_user_preserves_resources(): void {
        global $DB;

        $bookingperson = $this->getDataGenerator()->create_user();
        $context = $this->create_participant_context((int) $bookingperson->id);
        $examiner = $this->getDataGenerator()->create_user();
        $starttime = strtotime('+14 days 09:00:00');
        $endtime = strtotime('+14 days 11:00:00');
        $this->setUser((int) $bookingperson->id);
        $event = new bookit_event(
            0,
            'Cancel-only resource event',
            20261,
            1,
            $starttime,
            $endtime,
            120,
            $this->roomid,
            10,
            0,
            '',
            event_access_manager::BOOKINGSTATUS_NEW,
            (int) $examiner->id,
            '',
            0,
            'Participant notes',
            '',
            '',
            0,
            0,
            null,
            (int) $bookingperson->id,
            (int) $bookingperson->id,
            time(),
            time(),
            [],
        );
        $saved = event_manager::save_event_with_lifecycle_tracking(
            $event,
            null,
            (int) $bookingperson->id,
            $context
        );
        event_resource_manager::add_resource_to_event(
            (int) $saved->id,
            $this->resourceid,
            1,
            (int) $bookingperson->id
        );
        event_manager::transition_booking_status(
            event_manager::get_event((int) $saved->id),
            event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
            (int) $bookingperson->id,
            $context
        );

        $this->submit_edit_event_form(
            $context,
            (int) $saved->id,
            (int) $bookingperson->id,
            [
                'editevent' => 0,
                'editinternal' => 0,
                'editinternalnotes' => 0,
                'editbookingstatus' => 1,
                'bookingstatus' => event_access_manager::BOOKINGSTATUS_IN_PROGRESS,
                'notes' => 'Minor note change',
            ]
        );

        $rows = $DB->get_records('bookit_event_resource', ['eventid' => $saved->id]);
        $this->assertCount(1, $rows);
        $this->assertContains($this->resourceid, $this->get_assigned_resource_ids((int) $saved->id));
    }
}
