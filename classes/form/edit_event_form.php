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

use context_course;
use core\context;
use core\context\module;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use mod_bookit\external\get_possible_starttimes;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\resource_manager;
use mod_bookit\local\persistent\institution;
use mod_bookit\local\persistent\room;
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
     * @var int BOOKINGSTATUS_NEW: event is not processed yet and can be edited by the creator.
     */
    public const BOOKINGSTATUS_NEW = 0;

    /** @var bookit_event|null An event, if an existing one is getting edited. */
    private $event = null;

    /**
     * Define the form
     */
    public function definition(): void {
        global $DB, $CFG;
        $mform =& $this->_form;

        // Get the plugin config.
        $config = get_config('mod_bookit');

        // Get the ressources.
        $catresourceslist = resource_manager::get_resources();

        // Define variables.
        $context = $this->get_context_for_dynamic_submission();
        $caneditinternal = has_capability('mod/bookit:editinternal', $context);
        $cmid = $this->_ajaxformdata['cmid'] ?? false;
        $course = get_course_and_cm_from_cmid($cmid);
        $contextcourse = context_course::instance($course[0]->id);

        // Set hidden field course module id.
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        // Set hidden field event id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Set hidden field extra time.
        $mform->addElement('hidden', 'extratime', $config->extratime);
        $mform->setType('extratime', PARAM_INT);

        // Show the user who created the entry.
        $mform->addElement('text', 'usermodified', get_string('event_usermodified', 'mod_bookit'));
        $mform->setType('usermodified', PARAM_TEXT);
        $mform->disabledIf('usermodified', 'id', 'neq', 0);
        $mform->hideIf('usermodified', 'id', 'eq', '');

        // Add the standard "name" field.
        $mform->addElement('text', 'name', get_string('event_name', 'mod_bookit'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->disabledIf('name', 'editevent', 'neq');
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
        $mform->disabledIf('semester', 'editevent', 'neq');
        $mform->addRule('semester', null, 'required', null, 'client');
        $mform->addHelpButton('semester', 'select_semester', 'mod_bookit');

        // Add the "institutionid" field.
        $institutions = institution::get_records(['active' => true]);
        $institutionoptions = [];
        foreach ($institutions as $institution) {
            $institutionoptions[$institution->get('id')] = $institution->get('name');
        }
        $mform->addElement('select', 'institutionid', get_string('event_department', 'mod_bookit'), $institutionoptions);
        $mform->addRule('institutionid', null, 'required', null, 'client');
        $mform->addHelpButton('institutionid', 'event_department', 'mod_bookit');

        // Add the "roomid" field.
        $rooms = room::get_records(['active' => true]);
        $roomoptions = [];
        foreach ($rooms as $room) {
            $roomoptions[$room->get('id')] = $room->get('name');
        }

        $mform->addElement('select', 'roomid', get_string('event_room', 'mod_bookit'), $roomoptions);
        $mform->disabledIf('roomid', 'editevent', 'neq');
        $mform->addHelpButton('roomid', 'event_room', 'mod_bookit');

        // Add the "duration" field.
        $duration = [];
        // ...@TODO: remove fallback values if these values are admin settings - see issue#3!
        $eventdefaultduration = ($config->eventdefaultduration ?? 60);
        $eventdurationstepwidth = ($config->eventdurationstepwidth ?? 15);
        $eventmaxduration = ($config->eventmaxduration ?? 480);
        for ($i = $eventdurationstepwidth; $i <= $eventmaxduration; $i += $eventdurationstepwidth) {
            $duration[$i] = $i;
        }
        $select = $mform->addElement('select', 'duration', get_string('event_duration', 'mod_bookit'), $duration);
        $select->setSelected($eventdefaultduration);
        $mform->disabledIf('duration', 'editevent', 'neq');
        $mform->addHelpButton('duration', 'event_duration', 'mod_bookit');

        // Add the "bookingtimes" fields.
        $curdate = new \DateTimeImmutable('+ 1 hour');
        $starttimearray = [
                'step' => 15, // Step to increment minutes by.
                'optional' => false, // Setting 'optional' to true adds an 'enable' checkbox to the selector.
        ];
        // Set time restrictions based on "editinternal" capability.
        if ($caneditinternal) {
            $starttimearray['startyear'] = $config->eventminyears;
        } else {
            $starttimearray['startyear'] = date("Y");

        }
        $starttimearray['stopyear'] = $config->eventmaxyears;

        $mform->addElement('date_selector', 'startdate', get_string('event_start', 'mod_bookit'), $starttimearray);
        $mform->addRule('startdate', null, 'required', null, 'client');
        $mform->addHelpButton('startdate', 'event_start', 'mod_bookit');

        $mform->addElement('select', 'starttime');

        // Closure function to check that no date in the past is selected.
        $checkmindate = function($val) use ($curdate) {
            $checkdate = mktime($val['hour'], $val['minute'], '00', $val['month'], $val['day'], $val['year']);
            if ($checkdate < $curdate->getTimestamp()) {
                return false;
            }
            return true;
        };

        // Add a static field to explain extra time.
        $mform->addElement('static', 'extratime_label', get_string('event_extratime_label', 'mod_bookit'),
                get_string('event_extratime_description', 'mod_bookit', $config->extratime));

        // Add "amount of students" field.
        $mform->addElement('text', 'participantsamount', get_string('event_students', 'mod_bookit'), ['size' => '4']);
        $mform->disabledIf('participantsamount', 'editevent', 'neq');
        $mform->setType('participantsamount', PARAM_INT);
        $mform->addRule('participantsamount', null, 'required', null, 'client');
        $mform->addHelpButton('participantsamount', 'event_students', 'mod_bookit');

        // Add the "person in charge" field.
        $userselectoroptions = [
                'ajax' => 'enrol_manual/form-potential-user-selector',
                'multiple' => false,
                'courseid' => $course[0]->id,
                'enrolid' => 0,
                'perpage' => $CFG->maxusersperpage,
                'userfields' => implode(',', \core_user\fields::get_identity_fields($contextcourse, true)),
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
        $mform->disabledIf('personinchargeid', 'editevent', 'neq');
        $mform->setType('personinchargeid', PARAM_TEXT);
        $mform->setDefault('personinchargeid', '');
        $mform->addRule('personinchargeid', null, 'required', null, 'client');
        $mform->addHelpButton('personinchargeid', 'event_personincharge', 'mod_bookit');

        // Add the "otherexaminers" field.
        $userselectoroptions['multiple'] = true;
        $mform->addElement('autocomplete', 'otherexaminers',
                get_string('event_otherexaminers', 'mod_bookit'), $examinerlist, $userselectoroptions);
        $mform->disabledIf('otherexaminers', 'editevent', 'neq');
        $mform->setType('otherexaminers', PARAM_TEXT);
        $mform->addHelpButton('otherexaminers', 'event_otherexaminers', 'mod_bookit');

        // Add the coursetemplate field.
        // ...@TODO: Implement course template administration in admin settings and query them here.
        $coursetemplates = [0 => get_string('none')];
        $mform->addElement('select', 'coursetemplate', get_string('select_coursetemplate', 'mod_bookit'), $coursetemplates);
        $mform->disabledIf('coursetemplate', 'editevent', 'neq');
        $mform->addRule('coursetemplate', null, 'required', null, 'client');
        $mform->addHelpButton('coursetemplate', 'select_coursetemplate', 'mod_bookit');

        // Add the "timecompensation" field.
        $mform->addElement(
                'advcheckbox',
                'timecompensation',
                get_string('event_timecompensation', 'mod_bookit'),
                get_string('yes')
        );
        $mform->disabledIf('timecompensation', 'editevent', 'neq');
        $mform->setType('timecompensation', PARAM_BOOL);
        $mform->addHelpButton('timecompensation', 'event_timecompensation', 'mod_bookit');

        // Add the "compensationfordisadvantages" field.
        $mform->addElement('textarea', 'compensationfordisadvantages',
                get_string('event_compensationfordisadvantages', 'mod_bookit'), ['size' => '64']);
        $mform->disabledIf('compensationfordisadvantages', 'editevent', 'neq');
        $mform->setType('compensationfordisadvantages', PARAM_TEXT);
        $mform->addHelpButton('compensationfordisadvantages', 'event_compensationfordisadvantages', 'mod_bookit');

        // Add the "notes" field.
        $mform->addElement('textarea', 'notes', get_string("event_notes", "mod_bookit"),
                'wrap="virtual" rows="5" cols="50"');
        $mform->disabledIf('notes', 'editevent', 'neq');
        $mform->addHelpButton('notes', 'event_notes', 'mod_bookit');

        // Internal fields.
        if ($caneditinternal) {
            $mform->addElement('header', 'header_internal', get_string('header_internal', 'mod_bookit'));
            $mform->setExpanded('header_internal', true);
        }
        // Add the "refcourseid" field.
        // ...@TODO: make category to select courses an admin option for 'exclude'.
        // ...@TODO: exclude current course.
        // ...@TODO: make use of capabilities to show courses ???
        $mform->addElement('course', 'refcourseid', get_string('event_refcourseid', 'mod_bookit'),
                ['multiple' => false, 'showhidden' => true, 'exclude' => '']);
        $mform->setType('refcourseid', PARAM_INT);
        $mform->setDefault('refcourseid', 0);
        $mform->hideIf('refcourseid', 'editinternal', 'neq');
        $mform->addHelpButton('refcourseid', 'event_refcourseid', 'mod_bookit');

        if ($caneditinternal) {
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
            $mform->setType('supportpersons', PARAM_TEXT);
            $mform->addHelpButton('supportpersons', 'event_supportperson', 'mod_bookit');
        } else {
            $mform->addElement('hidden', 'supportpersons');
            $mform->setType('supportpersons', PARAM_TEXT);
        }
        // Add the "bookingstatus" field.
        $mform->addElement('select', 'bookingstatus', get_string('event_bookingstatus', 'mod_bookit'),
                explode(',', get_string('event_bookingstatus_list', 'mod_bookit')));
        $mform->hideIf('bookingstatus', 'editinternal', 'neq');
        $mform->addHelpButton('bookingstatus', 'event_bookingstatus', 'mod_bookit');

        // Add the "internalnotes" field.
        $mform->addElement('textarea', 'internalnotes', get_string("event_internalnotes", "mod_bookit"),
                'wrap="virtual" rows="5" cols="50"');
        $mform->hideIf('internalnotes', 'editinternal', 'neq');
        $mform->addHelpButton('internalnotes', 'event_internalnotes', 'mod_bookit');

        // Add the additional resources.
        foreach ($catresourceslist as $category => $c) {
            if ($category === 'Rooms') {
                continue;
            }
            $mform->addElement('header', 'header_' . $c['category_id'], $category);
            $mform->setExpanded('header_' . $c['category_id'], true);

            foreach ($c['resources'] as $rid => $v) {
                $groupelements = [];
                $groupelements[] =
                        $mform->createElement(
                                'advcheckbox',
                                'checkbox_' . $rid,
                                '',
                                $v['name'],
                                ['group' => 1],
                                [0, !0] // Array of values associated with the checked/unchecked state of the checkbox.
                        );
                $mform->disabledIf('checkbox_' . $rid, 'editevent', 'neq');

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
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     *
     * @return void
     */
    public function definition_after_data() {
        global $DB, $USER;
        $mform =& $this->_form;
        $data = $this->get_submitted_data() ?? $this->event;

        $context = $this->get_context_for_dynamic_submission();
        $id = $this->_form->getElementValue('id');
        $bookingstatus = $this->_form->getElementValue('bookingstatus');
        $usermodified = $this->_form->getElementValue('usermodified');
        $examiner = $this->_form->getElementValue('personinchargeid');
        $otherexaminers = $this->_form->getElementValue('otherexaminers') ?? [];
        array_push($otherexaminers, $usermodified, $examiner);

        // Show the user who created the entry.
        $user = $DB->get_record('user', ['id' => $usermodified]);
        $mform->getElement('usermodified')->setValue(
                fullname($user, has_capability('moodle/site:viewfullnames', $context)) // TODO: ???
        );

        // Get context and capabilities.
        $context = $this->get_context_for_dynamic_submission();
        // Event can be edited if capability is set, a new event is created or event is unprocessed (own events).
        $caneditevent = (has_capability('mod/bookit:editevent', $context) || !$id ||
                (self::BOOKINGSTATUS_NEW == (int) $bookingstatus[0] && in_array($USER->id, $otherexaminers))
        );
        $caneditinternal = has_capability('mod/bookit:editinternal', $context);

        // Set hidden field editevent capability.
        $e1 = $this->_form->createElement('hidden', 'editevent', $caneditevent);
        $this->_form->setType('editevent', PARAM_BOOL);
        $mform->insertElementBefore($e1, 'name');

        // Set hidden field editinternal capability.
        $e2 = $this->_form->createElement('hidden', 'editinternal', $caneditinternal);
        $this->_form->setType('editinternal', PARAM_BOOL);
        $mform->insertElementBefore($e2, 'name');

        if ($data) {
            /** @var \MoodleQuickForm_select $starttimeel */
            $starttimeel = $mform->getElement('starttime');
            $starttimeel->removeOptions();
            $starttimeel->loadArray(get_possible_starttimes::list_possible_starttimes(
                (new \DateTime())->setTimestamp($data->startdate), $data->duration, $data->roomid
            ));
        }
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        $event = new StdClass;
        $id = $this->optional_param('id', null, PARAM_INT);
        if (!empty($id)) {
            $event = event_manager::get_event($id);
            $date = (new \DateTime())->setTimestamp($event->starttime);
            $date->setTime(0, 0);
            $event->startdate = $date->getTimestamp();
            $this->event = $event;
        }
        $event->cmid = $this->optional_param('cmid', null, PARAM_INT);

        $this->set_data($event);
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
     * Checks if current user has access to this form, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void {
        // TODO.
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array ...
     */
    public function process_dynamic_submission(): array {
        $formdata = $this->get_data();

        $mappings = [];
        foreach (resource_manager::get_resources() as $category => $catresource) {
            // Rooms.
            foreach ($catresource['resources'] as $id => $v) {
                if ('Rooms' == $category) {
                    if ($formdata->room == $id) {
                        $mappings[] = (object) [
                                'resourceid' => $formdata->room,
                                'amount' => 1,
                        ];
                    }
                } else {
                    // Other Resources.
                    $checkboxname = 'checkbox_' . $id;
                    if ($formdata->$checkboxname ?? false) {
                        $mappings[] = (object) [
                                'resourceid' => $id,
                                'amount' => $formdata->{'resource_' . $id},
                        ];
                    }
                }
            }
        }
        $formdata->resources = $mappings;

        // Calculate endtime.
        $formdata->endtime = $formdata->starttime + $formdata->duration * 60 + $formdata->extratime * 60;

        if (is_array($formdata->supportpersons)) {
            $formdata->supportpersons = implode(',', array_filter($formdata->supportpersons));
        }
        if (is_array($formdata->refcourseid)) {
            $r = $formdata->refcourseid;
            $formdata->refcourseid = $r[0];
        }

        if (!is_int($formdata->usermodified)) {
            unset($formdata->usermodified);
        }

        $event = bookit_event::from_record($formdata);
        $event->save();

        return [];
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
