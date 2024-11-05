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
use mod_bookit\local\manager\event_manager;
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

        $catresourceslist = resource_manager::get_resources();
        //$mform->addElement('static', 'resources', "<pre>".print_r($catresourceslist, true)."</pre>", true);

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
        foreach ($catresourceslist as $category => $value) {
            if ($category === 'Rooms') {
                foreach ($value['resources'] as $rid => $catresource) {
                    $rooms[$rid] = $catresource['name'];
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
        $userselectoroptions = [
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
                get_string('event_personincharge', 'mod_bookit'), $examinerlist, $userselectoroptions);
        $mform->disabledIf('personinchargeid', 'editevent', 'eq');
        $mform->setType('personinchargeid', PARAM_TEXT);
        $mform->addRule('personinchargeid', null, 'required', null, 'client');
        $mform->addHelpButton('personinchargeid', 'event_personincharge', 'mod_bookit');

        // Add the "otherexaminers" field.
        $userselectoroptions['multiple'] = true;
        $mform->addElement('autocomplete', 'otherexaminers',
                get_string('event_otherexaminers', 'mod_bookit'), $examinerlist, $userselectoroptions);
        $mform->disabledIf('otherexaminers', 'editevent', 'eq');
        $mform->setType('otherexaminers', PARAM_TEXT);
        $mform->addRule('otherexaminers', null, 'required', null, 'client');
        $mform->addHelpButton('otherexaminers', 'event_otherexaminers', 'mod_bookit');

        // Add the coursetemplate field.
        // ...@TODO: Implement course template administration in admin settings and query them here.
        $coursetemplates = [0 => get_string('default')];
        $mform->addElement('select', 'coursetemplate', get_string('select_coursetemplate', 'mod_bookit'), $coursetemplates);
        $mform->disabledIf('coursetemplate', 'editevent', 'eq');
        $mform->addRule('coursetemplate', null, 'required', null, 'client');
        $mform->addHelpButton('coursetemplate', 'select_coursetemplate', 'mod_bookit');

        // Add the "refcourseid" field.
        // ...@TODO: make category to select courses an admin option for 'exclude'.
        // ...@TODO: exclude current course.
        // ...@TODO: make use of capabilities to show courses ???
        $mform->addElement('course', 'refcourseid', get_string('event_refcourseid', 'mod_bookit'),
                ['multiple' => false, 'showhidden' => true, 'exclude' => '']);
        $mform->setType('refcourseid', PARAM_INT);
        $mform->disabledIf('refcourseid', 'editevent', 'eq');
        $mform->setType('refcourseid', PARAM_TEXT);
        $mform->addHelpButton('refcourseid', 'event_refcourseid', 'mod_bookit');

        // Add the "timecompensation" field.
        $mform->addElement(
                'advcheckbox', 
                'timecompensation',
                get_string('event_timecompensation', 'mod_bookit'), 
                get_string('yes')
        );
        $mform->disabledIf('timecompensation', 'editevent', 'eq');
        $mform->setType('timecompensation', PARAM_BOOL);
        $mform->addHelpButton('timecompensation', 'event_timecompensation', 'mod_bookit');

        // Add the "compensationfordisadvantages" field.
        $mform->addElement('textarea', 'compensationfordisadvantages',
                get_string('event_compensationfordisadvantages', 'mod_bookit'), ['size' => '64']);
        $mform->disabledIf('compensationfordisadvantages', 'editevent', 'eq');
        $mform->setType('compensationfordisadvantages', PARAM_TEXT);
        $mform->addHelpButton('compensationfordisadvantages', 'event_compensationfordisadvantages', 'mod_bookit');

        // Add the "notes" field.
        $mform->addElement('textarea', 'notes', get_string("event_notes", "mod_bookit"), 'wrap="virtual" rows="5" cols="50"');
        $mform->disabledIf('notes', 'editevent', 'eq');
        $mform->addHelpButton('notes', 'event_notes', 'mod_bookit');

        // Add the "internalnotes" field.
        $mform->addElement('textarea', 'internalnotes', get_string("event_internalnotes", "mod_bookit"), 'wrap="virtual" rows="5" cols="50"');
        $mform->disabledIf('internalnotes', 'editevent', 'eq');
        $mform->addHelpButton('internalnotes', 'event_internalnotes', 'mod_bookit');

        // Add the "supportpersons" field.
        $supportpersons = [];
        // ...@TODO: Find better query to select users!
        $sqlsupport = "SELECT DISTINCT u.*
                  FROM {user} u
                  WHERE u.deleted = 0 AND u.suspended = 0
                  ORDER BY lastname, firstname";

        $users = $DB->get_records_sql($sqlsupport, []);
        foreach ($users as $id => $user) {
            $supportpersons[$id] = fullname($user);
        }
        $mform->addElement('autocomplete', 'supportpersons',
                get_string('event_supportperson', 'mod_bookit'), $supportpersons, $userselectoroptions);
        $mform->disabledIf('supportpersons', 'editevent', 'eq');
        $mform->setType('supportpersons', PARAM_TEXT);
        $mform->addHelpButton('supportpersons', 'event_supportperson', 'mod_bookit');

        // Add the additional resources.
        foreach ($catresourceslist as $category => $c) {
            if ($category === 'Rooms') {
                continue;
            }
            $mform->addElement('header', 'header_' . $c['category_id'], $category);

            foreach ($c['resources'] as $rid => $v) {
                $bla = 'resource_' . $rid;
                $groupelements = [];
                $groupelements[] =
                        $mform->createElement(
                                'advcheckbox', 
                                'checkbox_' . $rid,
                                '',
                                $v['name'],
                                ['group' => 1],
                                [0, !0] // "array of values that will be associated with the checked/unchecked state of the checkbox"
                        );
                $mform->disabledIf('checkbox_' . $rid, 'editevent', 'eq');

                $groupelements[] =
                        $mform->createElement(
                                'text',
                                'resource_' . $rid,
                                get_string('resource_amount', 'mod_bookit'),
                                ['size' => '4']
                        );
                $mform->setType('resource_' . $rid, PARAM_INT);
                $mform->disabledIf('resource_' . $rid, 'checkbox_' . $rid);

                $mform->addGroup($groupelements, 'resourcegroup', get_string('please_select_and_enter', 'mod_bookit'), ['<br>'],
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
        file_put_contents('/tmp/event.log', date("Y-m-d H:i:s"). ': (process_dynamic_submission) '.print_r($formdata, true) . "\n", FILE_APPEND);

        $mappings = [];
        foreach (resource_manager::get_resources() as $category => $catresource) {
            // Rooms.
            foreach ($catresource['resources'] as $id => $v) {
                if ('Rooms' == $category) {
                    file_put_contents('/tmp/event.log',
                            date("Y-m-d H:i:s") . ': (Room) ' . serialize($formdata->room) . ' / ' . serialize($v) . "\n",
                            FILE_APPEND);
                    if ($formdata->room == $id) {
                        $mappings[] = (object) [
                                'resourceid' => $formdata->room,
                                'amount' => 1,
                        ];
                    }

                } else {
                    // Other Resources.
                    $checkboxname = 'checkbox_' . $id;
                    file_put_contents('/tmp/event.log', date("Y-m-d H:i:s") . ': (get_categories) ' . $checkboxname . ' / ' .
                            print_r($formdata->$checkboxname, true) . "\n", FILE_APPEND);
                    if ($formdata->$checkboxname ?? false) {
                        $mappings[] = (object) [
                                'resourceid' => $id,
                                'amount' => $formdata->{'resource_' . $id},
                        ];
                    }
                }
            }
        }
        file_put_contents('/tmp/event.log', date("Y-m-d H:i:s"). ': (mappings) '.print_r($mappings, true) . "\n", FILE_APPEND);
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
            $event = event_manager::get_event($id);
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
