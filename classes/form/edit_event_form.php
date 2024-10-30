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

use core\context;
use core\context\module;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\manager\categories_manager;
use mod_bookit\local\manager\resource_manager;
use moodle_url;
use stdClass;

/**
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_event_form extends dynamic_form {
    /**
     * Define the form
     */
    public function definition(): void {
        global $DB, $CFG;
        $config = get_config('mod_bookit');
        $mform =& $this->_form;

        $resourceslist = resource_manager::get_resources();
        //$mform->addElement('static', 'resources', "<pre>".print_r($resourceslist, true)."</pre>", true);

        $context = $this->get_context_for_dynamic_submission();
        $disabled = !has_capability('mod/bookit:editevent', $context);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'editevent', $disabled);
        $mform->setType('editevent', PARAM_BOOL);

        // Add the standard "name" field.
        $mform->addElement('text', 'name', get_string('event_name', 'mod_bookit'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->disabledIf('name', 'editevent', 'eq');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'event_name', 'mod_bookit');

        // Semester.
        $currentyear = date('Y');
        // Generate semesters dynamically.
        $semesters = [];
        // ...@TODO: Make range of semester terms an admin option.
        for ($i = -1; $i < 2; $i++) {

            $semesters[($currentyear + $i) * 10 + 1] = get_string('summer_semester', 'mod_bookit') . " " . ($currentyear + $i);
            $semesters[($currentyear + $i) * 10 + 2] = get_string('winter_semester', 'mod_bookit') . " " . ($currentyear + $i);
        }
        $mform->addElement('select', 'semester', get_string('select_semester', 'mod_bookit'), $semesters);
        $mform->disabledIf('semester', 'editevent', 'eq');
        $mform->addRule('semester', null, 'required', null, 'client');
        $mform->addHelpButton('semester', 'select_semester', 'mod_bookit');

        // Add the "department" field.
        $mform->addElement('text', 'department', get_string('event_department', 'mod_bookit'), ['size' => '64']);
        $mform->disabledIf('department', 'editevent', 'eq');
        $mform->setType('department', PARAM_TEXT);
        $mform->addRule('department', null, 'required', null, 'client');
        $mform->addRule('department', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('department', 'event_department', 'mod_bookit');

        // Add the "room" field.
        $rooms = [];
        foreach ($resourceslist as $category => $value) {
            if ($category === 'Rooms') {
                foreach ($value['resources'] as $resource) {
                    $rooms[$resource['resource_id']] = $resource['resource_name'];
                }
            }
        }
        $mform->addElement('select', 'room', get_string('event_room', 'mod_bookit'), $rooms);
        $mform->disabledIf('room', 'editevent', 'eq');
        $mform->addHelpButton('room', 'event_room', 'mod_bookit');

        // Add the "bookingtimes" fields.
        // ...@TODO: make stopyear an admin setting issue#3!
        $startarray = ['startyear' => date("Y"), 'stopyear' => date("Y") + ($config->eventmaxyears ?? 1),
                'timezone' => 99, 'step' => 5, 'optional' => false, ];
        $startdate = $this->optional_param('startdate', null, PARAM_TEXT);
        $mform->addElement('date_time_selector', 'starttime', get_string('event_start', 'mod_bookit'), $startarray);
        $mform->disabledIf('starttime', 'editevent', 'eq');
        $mform->setDefault('starttime', (strtotime($startdate ?? '') ?? time()));
        $mform->addRule('starttime', null, 'required', null, 'client');
        $mform->addHelpButton('starttime', 'event_start', 'mod_bookit');

        $stoparray = ['startyear' => date("Y"), 'stopyear' => date("Y") + 1,
                'timezone' => 99, 'step' => 5, 'optional' => false, ];
        // ...@TODO: make default duration of event time an admin setting issue#3!
        $defaultduration = ($config->eventdefaultduration ?? 60);
        $stopdate = ($startdate ? strtotime($startdate ?? '') : time());
        $mform->addElement('date_time_selector', 'endtime', get_string('event_end', 'mod_bookit'), $stoparray);
        $mform->disabledIf('endtime', 'editevent', 'eq');
        $mform->setDefault('endtime', strtotime(' + ' . $defaultduration . ' minutes', $stopdate));
        $mform->addRule('endtime', null, 'required', null, 'client');
        // ...@TODO: Restrict event duration according to $config->eventmaxduration!
        $mform->addHelpButton('endtime', 'event_end', 'mod_bookit');

        // Add the "duration" field.
        $mform->addElement('text', 'duration', get_string('event_duration', 'mod_bookit'), ['size' => '4']);
        $mform->disabledIf('duration', 'editevent', 'eq');
        $mform->setType('duration', PARAM_INT);
        $mform->addRule('duration', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('duration', 'event_duration', 'mod_bookit');

        // Add "amount of students" field.
        $mform->addElement('text', 'participantsamount', get_string('event_students', 'mod_bookit'), ['size' => '4']);
        $mform->disabledIf('participantsamount', 'editevent', 'eq');
        $mform->setType('participantsamount', PARAM_INT);
        $mform->addHelpButton('participantsamount', 'event_students', 'mod_bookit');

        // Add the "person in charge" field.
        $options = [
                'ajax' => 'enrol_manual/form-potential-user-selector',
                'multiple' => false,
                'courseid' => 1,
                'enrolid' => 0,
                'perpage' => $CFG->maxusersperpage,
                'userfields' => implode(',', \core_user\fields::get_identity_fields($context, true)),
        ];
        $examinerlist = [];
        // ...@TODO: Find better query to select users!
        $sql = "SELECT DISTINCT u.*
                  FROM {user} u
                  WHERE u.deleted = 0 AND u.suspended = 0
                  ORDER BY lastname, firstname";

        $users = $DB->get_records_sql($sql, []);
        foreach ($users as $id => $user) {
            $examinerlist[$id] = fullname($user) . ' | ' . $user->email;
        }
        $mform->addElement('autocomplete', 'personinchargeid',
                get_string('event_person', 'mod_bookit'), $examinerlist, $options);
        $mform->disabledIf('personinchargeid', 'editevent', 'eq');
        $mform->setType('personinchargeid', PARAM_TEXT);
        $mform->addRule('personinchargeid', null, 'required', null, 'client');
        $mform->addHelpButton('personinchargeid', 'event_person', 'mod_bookit');

        // Add the "otherexaminers" field.
        $options['multiple'] = true;
        $mform->addElement('autocomplete', 'otherexaminers',
                get_string('event_otherexaminers', 'mod_bookit'), $examinerlist, $options);
        $mform->disabledIf('otherexaminers', 'editevent', 'eq');
        $mform->setType('otherexaminers', PARAM_TEXT);
        $mform->addRule('otherexaminers', null, 'required', null, 'client');
        $mform->addHelpButton('otherexaminers', 'event_otherexaminers', 'mod_bookit');

        // Add the "timecompensation" field.
        $mform->addElement('advcheckbox', 'timecompensation',
                get_string('event_timecompensation', 'mod_bookit'), get_string('yes'));
        $mform->disabledIf('timecompensation', 'editevent', 'eq');
        $mform->setType('timecompensation', PARAM_BOOL);
        $mform->addHelpButton('timecompensation', 'event_timecompensation', 'mod_bookit');

        // Add the "compensationfordisadvantages" field.
        $mform->addElement('textarea', 'compensationfordisadvantages',
                get_string('event_compensationfordisadvantages', 'mod_bookit'), ['size' => '64']);
        $mform->disabledIf('compensationfordisadvantages', 'editevent', 'eq');
        $mform->setType('compensationfordisadvantages', PARAM_TEXT);
        $mform->addHelpButton('compensationfordisadvantages', 'event_compensationfordisadvantages', 'mod_bookit');

        $mform->addElement('textarea', 'notes', get_string("event_notes", "mod_bookit"), 'wrap="virtual" rows="5" cols="50"');
        $mform->disabledIf('notes', 'editevent', 'eq');
        $mform->addHelpButton('notes', 'event_notes', 'mod_bookit');

        foreach ($resourceslist as $category => $value) {
            if ($category === 'Rooms') {
                continue;
            }
            $mform->addElement('header', 'header_' . $value['category_id'], $category);

            foreach ($value['resources'] as $v) {
                $preprocedure = [];
                $preprocedure[] =
                        $mform->createElement('advcheckbox', 'resourcecheckbox_' . $v['resource_id'], '', $v['resource_name'],
                                ['group' => 1],
                                ['', $v['resource_name']]);
                $mform->disabledIf('resourcecheckbox_' . $v['resource_id'], 'editevent', 'eq');

                $preprocedure[] =
                        $mform->createElement('text', 'resourceamount_' . $v['resource_id'],
                                get_string('resource_amount', 'mod_bookit'),
                                ['size' => '4']);
                $mform->setType('resourceamount_' . $v['resource_id'], PARAM_INT);
                $mform->disabledIf('resourceamount_' . $v['resource_id'], 'resourcecheckbox_' . $v['resource_id']);

                $mform->addGroup($preprocedure, 'preproceduregroup', get_string('please_select_and_enter', 'mod_bookit'), ['<br>'],
                        false);
            }
        }
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        return module::instance($cmid);
    }

    /**
     * Checks if current user has access to this card, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void {
        // ...@TODO.
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array ...
     */
    public function process_dynamic_submission(): array {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $id = $this->optional_param('id', null, PARAM_INT);
        $context = $this->get_context_for_dynamic_submission();
        $formdata = $this->get_data();

        $mappings = [];
        #echo json_encode(categories_manager::get_categories());
        #echo json_encode($formdata);
        foreach (categories_manager::get_categories() as $category) {
            foreach ($category['resources'] as $resource) {
                $checkboxname = 'resourcecheckbox_' . $resource['id'];
                if ($data->$checkboxname ?? false) {
                    $mappings[] = (object) [
                            'resourceid' => $resource['id'],
                            'amount' => $formdata->{'resourceamount_' . $resource['id']},
                    ];
                }
            }
        }
        #echo json_encode($mappings);
        $formdata->resources = $mappings;
        $formdata->status = bookit_event::STATUS_OPEN;
        $event = bookit_event::from_record($formdata);
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
        if (!empty($id)) {
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
