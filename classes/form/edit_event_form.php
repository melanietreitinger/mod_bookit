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
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\form;

use context;
use context_module;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use mod_bookit\local\entity\event;
use mod_bookit\local\manager\categories_manager;
use moodle_url;
use stdClass;

class edit_event_form extends dynamic_form {

    public function definition(): void {
        $mform =& $this->_form;

        $categories = categories_manager::get_categories();

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Add the standard "name" field.
        $mform->addElement('text', 'name', get_string('event_name', 'mod_bookit'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'event_name', 'mod_bookit');

        // Semester.
        $currentYear = date('Y');
        // Generate semesters dynamically.
        $semesters = [];
        for ($i = -1; $i < 2; $i++) {

            $semesters[($currentYear+$i)*10+1] = get_string('summer_semester', 'mod_bookit') . " " . ($currentYear+$i);
            $semesters[($currentYear+$i)*10+2] = get_string('winter_semester', 'mod_bookit') . " " . ($currentYear+$i);
        }
        $mform->addElement('select', 'semester', get_string('select_semester', 'mod_bookit'), $semesters);
        $mform->addRule('semester', null, 'required', null, 'client');
        $mform->addHelpButton('semester', 'select_semester', 'mod_bookit');

        // Add the "department" field.
        $mform->addElement('text', 'department', get_string('event_department', 'mod_bookit'), array('size' => '64'));
        $mform->setType('department', PARAM_TEXT);
        $mform->addRule('department', null, 'required', null, 'client');
        $mform->addRule('department', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('department', 'event_department', 'mod_bookit');

        // Add the "room" field.
        $rooms = [];
        foreach ($categories as $category) {
            if ($category['name'] === 'Rooms') {
                foreach ($category['resources'] as $resource) {
                    $rooms[$resource['id']] = $resource['name'];
                }
            }
        }
        $mform->addElement('select', 'room', get_string('event_room', 'mod_bookit'), $rooms);
        $mform->addHelpButton('room', 'event_room', 'mod_bookit');

        // Add the "Bookingtimes" fields.
        $mform->addElement('date_time_selector', 'starttime', get_string('event_start', 'mod_bookit'));
        $mform->addRule('starttime', null, 'required', null, 'client');
        $mform->addHelpButton('starttime', 'event_start', 'mod_bookit');

        $mform->addElement('date_time_selector', 'endtime', get_string('event_end', 'mod_bookit'));
        $mform->addRule('endtime', null, 'required', null, 'client');
        $mform->addHelpButton('endtime', 'event_end', 'mod_bookit');

        // Add the "duration" field.
        $mform->addElement('text', 'duration', get_string('event_duration', 'mod_bookit'), ['size' => '4']);
        $mform->setType('duration', PARAM_INT);
        $mform->addRule('duration', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('duration', 'event_duration', 'mod_bookit');

        // Add "amount of students" field.
        $mform->addElement('text', 'participantsamount', get_string('event_students', 'mod_bookit'), array('size' => '4'));
        $mform->setType('participantsamount', PARAM_INT);
        $mform->addHelpButton('participantsamount', 'event_students', 'mod_bookit');

        // Add the "person in charge" field.
        $mform->addElement('text', 'personinchargename', get_string('event_person', 'mod_bookit'), array('size' => '64'));
        $mform->setType('personinchargename', PARAM_TEXT);
        $mform->addRule('personinchargename', null, 'required', null, 'client');
        $mform->addRule('personinchargename', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('personinchargename', 'event_person', 'mod_bookit');

        // Add the "email" field.
        $mform->addElement('text', 'personinchargeemail', get_string('event_email', 'mod_bookit'), array('size' => '64'));
        $mform->setType('personinchargeemail', PARAM_TEXT);
        $mform->addRule('personinchargeemail', null, 'required', null, 'client');
        $mform->addRule('personinchargeemail', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('personinchargeemail', 'event_email', 'mod_bookit');

        // Add the "compensationfordisadvantages" field.
        $mform->addElement('textarea', 'compensationfordisadvantages', get_string('event_compensationfordisadvantages', 'mod_bookit'), ['size' => '64']);
        $mform->setType('compensationfordisadvantages', PARAM_TEXT);
        $mform->addHelpButton('compensationfordisadvantages', 'event_compensationfordisadvantages', 'mod_bookit');

        $mform->addElement('textarea', 'notes', get_string("event_notes", "mod_bookit"), 'wrap="virtual" rows="20" cols="50"');
        $mform->addHelpButton('notes', 'event_notes', 'mod_bookit');

        foreach(categories_manager::get_categories() as $category) {
            if ($category['name'] === 'Rooms') {
                continue;
            }
            $mform->addElement('header', 'header_' . $category['id'], $category['name']);

            foreach($category['resources'] as $v) {
                $preprocedure = [];
                $preprocedure[] =  $mform->createElement('advcheckbox', 'checkbox_' . $v['id'],'', $v['name'], ['group' => 1], ['',$v['name']]);
                $preprocedure[] =  $mform->createElement('text', 'amount_' . $v['id'], get_string('resource_amount', 'mod_bookit'), array('size' => '4'));
                $mform->setType('amount_' . $v['id'], PARAM_INT);
                $mform->addGroup($preprocedure, 'preproceduregroup', get_string('please_select_and_enter', 'mod_bookit'), ['<br>'], false);
                $mform->disabledIf('amount_' . $v['id'], 'checkbox_' . $v['id']);
            }
        }

        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return context_module::instance($cmid);
    }

    /**
     * Checks if current user has access to this card, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void {
        // @TODO
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array ...
     */
    public function process_dynamic_submission(): array {
        $cmid     = $this->optional_param('cmid', null, PARAM_INT);
        $id  = $this->optional_param('id', null, PARAM_INT);
        $context  = $this->get_context_for_dynamic_submission();
        $formdata = $this->get_data();

        $mappings = [];
        #echo json_encode(categories_manager::get_categories());
        #echo json_encode($formdata);
        foreach (categories_manager::get_categories() as $category) {
            foreach ($category['resources'] as $resource) {
                $checkboxname = 'checkbox_' . $resource['id'];
                if ($data->$checkboxname ?? false) {
                    $mappings[] = (object) [
                            'resourceid' => $resource['id'],
                            'amount' => $formdata->{'amount_' . $resource['id']},
                    ];
                }
            }
        }
        #echo json_encode($mappings);
        $formdata->resources = $mappings;
        $formdata->status = event::STATUS_OPEN;
        $event = event::from_record($formdata);
        $event->save();

        return [];
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $event = new StdClass;
        $context = $this->get_context_for_dynamic_submission();
        $id = $this->optional_param('id', null, PARAM_INT);
        file_put_contents('/tmp/formdata.txt', '$id: '.print_r($id, true)."\n", FILE_APPEND);
        if (!empty($id)) {
            file_put_contents('/tmp/formdata.txt', 'is_null...'.serialize($event)."\n", FILE_APPEND);
            $event = $DB->get_record('bookit_event', ['id' => $id]);
        }
        $event->cmid = $this->optional_param('cmid', null, PARAM_INT);


        $this->set_data($event);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     * @throws moodle_exception
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $params = [
                'id' => $this->optional_param('id', null, PARAM_INT),
                'cmid' => $this->optional_param('cmid', null, PARAM_INT),
        ];
        return new moodle_url('/mod/bookit/view.php', $params);
    }
}
