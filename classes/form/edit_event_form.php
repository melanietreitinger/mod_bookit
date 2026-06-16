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

use coding_exception;
use context_course;
use core\context;
use core\context\module;
use core\exception\moodle_exception;
use core_form\dynamic_form;
use mod_bookit\external\get_possible_starttimes;
use core_user\fields;
use dml_exception;
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\entity\resource\bookit_resource_status;
use mod_bookit\local\examiner_pool_resolver;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use mod_bookit\local\manager\resource_manager;
use mod_bookit\local\persistent\institution;
use mod_bookit\local\persistent\room;
use moodle_url;
use stdClass;
use function bookit_allowed_weekdays;

/**
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * @SuppressWarnings(PHPMD)
 */
class edit_event_form extends dynamic_form {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /**
     * @var int BOOKINGSTATUS_NEW: event is not processed yet and can be edited by the creator.
     */
    public const BOOKINGSTATUS_NEW = 0;

    /** @var bookit_event|stdClass|null An event, if an existing one is getting edited. */
    private bookit_event|stdClass|null $event = null;

    /**
     * Define the form
     */
    public function definition(): void {
        global $DB, $CFG, $PAGE, $USER;
        $mform =& $this->_form;

        // Get the plugin config.
        $config = get_config('mod_bookit');

        // Define variables.
        $context = $this->get_context_for_dynamic_submission();
        $resourcesenabled = event_access_manager::is_resources_enabled();
        $caneditinternal = has_capability('mod/bookit:editinternal', $context);
        $eventid = $this->optional_param('id', null, PARAM_INT);
        $existingevent = !empty($eventid) ? event_manager::get_event($eventid) : null;
        $participantpastreadonly = $existingevent
            && event_access_manager::should_block_participant_past_edit($existingevent, $context, (int)$USER->id);
        $caneditevent = has_capability('mod/bookit:editevent', $context)
            || empty($eventid)
            || ($existingevent && event_access_manager::can_participant_edit_event($existingevent, (int)$USER->id));
        if ($participantpastreadonly) {
            $caneditevent = false;
        }
        $canviewrestrictedfields = $caneditinternal
            || ($existingevent && event_access_manager::can_supportperson_view_internal_fields(
                $existingevent,
                $context,
                (int)$USER->id
            ));
        $caneditinternalnotes = $caneditinternal
            || ($existingevent && event_access_manager::can_supportperson_edit_internal_notes(
                $existingevent,
                $context,
                (int)$USER->id
            ));
        $canselfcancelnew = !$participantpastreadonly && $existingevent
            && event_access_manager::can_self_cancel_new_request($existingevent, $context, (int)$USER->id);
        $cancancelonly = !$participantpastreadonly && $existingevent
            && event_access_manager::can_participant_cancel_only($existingevent, $context, (int)$USER->id);
        $showbookingstatus = $caneditinternal || $canviewrestrictedfields || $cancancelonly || $canselfcancelnew;
        $showbookingstatusreadonly = $existingevent
            && !$participantpastreadonly
            && !$showbookingstatus
            && event_access_manager::can_user_view_event_details($existingevent, $context, (int)$USER->id);
        $requirepublicfields = $caneditevent && !$canselfcancelnew;
        $cmid = $this->_ajaxformdata['cmid'] ?? false;
        $course = get_course_and_cm_from_cmid($cmid);
        $contextcourse = context_course::instance($course[0]->id);

        // Set hidden field course module id.
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        // Set hidden field event id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Hidden permission flags used by dynamic visibility rules.
        $mform->addElement('hidden', 'editevent', (int)$caneditevent);
        $mform->setType('editevent', PARAM_BOOL);
        $mform->addElement('hidden', 'editinternal', (int)$caneditinternal);
        $mform->setType('editinternal', PARAM_BOOL);
        $mform->addElement('hidden', 'viewrestrictedfields', (int)$canviewrestrictedfields);
        $mform->setType('viewrestrictedfields', PARAM_BOOL);
        $mform->addElement('hidden', 'editinternalnotes', (int)$caneditinternalnotes);
        $mform->setType('editinternalnotes', PARAM_BOOL);
        $mform->addElement('hidden', 'editbookingstatus', (int)($caneditinternal || $cancancelonly || $canselfcancelnew));
        $mform->setType('editbookingstatus', PARAM_BOOL);
        $mform->addElement('hidden', 'cancelonly', (int)$cancancelonly);
        $mform->setType('cancelonly', PARAM_BOOL);

        // Show the user who created the entry.
        $mform->addElement('text', 'usermodified', get_string('event_usermodified', 'mod_bookit'));
        $mform->setType('usermodified', PARAM_TEXT);
        $mform->disabledIf('usermodified', 'id', 'neq', 0);
        $mform->hideIf('usermodified', 'id', 'eq', '');

        // Add the standard "name" field.
        $mform->addElement('text', 'name', get_string('event_name', 'mod_bookit'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->disabledIf('name', 'editevent', 'neq');
        if ($requirepublicfields) {
            $mform->addRule('name', null, 'required', null, 'client');
        }
        $mform->addRule('name', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'event_name', 'mod_bookit');

        // Semester.
        $currentyear = (int) date('Y');
        // Generate semesters dynamically.
        $semesters = [];
        $lookbackyears = max(0, (int)($config->semesterlookbackyears ?? 1));
        $lookaheadyears = max(0, (int)($config->semesterlookaheadyears ?? 1));
        for ($i = -$lookbackyears; $i <= $lookaheadyears; $i++) {
            $semesters[($currentyear + $i) * 10 + 1] = get_string('summer_semester', 'mod_bookit') . " " . ($currentyear + $i);
            $semesters[($currentyear + $i) * 10 + 2] = get_string('winter_semester', 'mod_bookit') . " " . ($currentyear + $i);
        }

        $mform->addElement('select', 'semester', get_string('select_semester', 'mod_bookit'), $semesters);
        if (empty($eventid)) {
            $currentsemester = event_manager::get_current_semester();
            if (array_key_exists($currentsemester, $semesters)) {
                $mform->setDefault('semester', $currentsemester);
            }
        }
        $mform->disabledIf('semester', 'editevent', 'neq');
        if ($requirepublicfields) {
            $mform->addRule('semester', null, 'required', null, 'client');
        }
        $mform->addHelpButton('semester', 'select_semester', 'mod_bookit');

        // Add the "institutionid" field.
        $institutions = institution::get_records(['active' => true]);
        $institutionoptions = [];
        foreach ($institutions as $institution) {
            $institutionoptions[$institution->get('id')] = $institution->get('name');
        }

        if (empty($institutionoptions)) {
            $mform->addElement('static', 'institutionid_empty_notice', '', get_string('institutionid_empty_notice', 'mod_bookit'));
        }

        $mform->addElement('select', 'institutionid', get_string('event_department', 'mod_bookit'), $institutionoptions);
        $mform->disabledIf('institutionid', 'editevent', 'neq');
        if ($requirepublicfields) {
            $mform->addRule('institutionid', null, 'required', null, 'client');
        }
        $mform->addHelpButton('institutionid', 'event_department', 'mod_bookit');

        // Add the "roomid" field.
        $rooms = room::get_records(['active' => true]);
        $roomoptions = [];
        foreach ($rooms as $room) {
            $str = $room->get('name');
            $addinfos = [];
            if ($room->get('seats')) {
                $addinfos[] = get_string('n_seats', 'mod_bookit', $room->get('seats'));
            }
            if ($room->get('location')) {
                $addinfos[] = $room->get('location');
            }
            if ($addinfos) {
                $str .= ' (' . implode(', ', $addinfos) . ')';
            }
            $roomoptions[$room->get('id')] = $str;
        }

        if (empty($roomoptions)) {
            $mform->addElement('static', 'roomid_empty_notice', '', get_string('roomid_empty_notice', 'mod_bookit'));
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
        $starttimearray = [
                'optional' => false, // Setting 'optional' to true adds an 'enable' checkbox to the selector.
        ];
        // Set time restrictions based on "editinternal" capability.
        if ($caneditinternal) {
            $starttimearray['startyear'] = $config->eventminyear ?? (date("Y") - 1);
        } else if ($participantpastreadonly && !empty($existingevent->starttime)) {
            $starttimearray['startyear'] = min((int)date("Y"), (int)date("Y", (int)$existingevent->starttime));
        } else {
            $starttimearray['startyear'] = date("Y");
        }
        $starttimearray['stopyear'] = $config->eventmaxyear ?? (date("Y") + 1);

        $mform->addElement('date_selector', 'startdate', get_string('event_start', 'mod_bookit'), $starttimearray);
        $mform->disabledIf('startdate', 'editevent', 'neq');
        if ($requirepublicfields) {
            $mform->addRule('startdate', null, 'required', null, 'client');
        }
        $mform->addHelpButton('startdate', 'event_start', 'mod_bookit');

        $mform->addElement('select', 'starttime');
        $mform->disabledIf('starttime', 'editevent', 'neq');

        $mform->addElement('static', 'starttime_explanation', '', '');

        // Add a static field to explain extra time.
        $mform->addElement(
            'static',
            'extratime_label',
            get_string('event_extratime_label', 'mod_bookit'),
            get_string('event_extratime_description', 'mod_bookit')
        );

        // Add "amount of students" field.
        $mform->addElement('text', 'participantsamount', get_string('event_students', 'mod_bookit'), ['size' => '4']);
        $mform->disabledIf('participantsamount', 'editevent', 'neq');
        $mform->setType('participantsamount', PARAM_INT);
        if ($requirepublicfields) {
            $mform->addRule('participantsamount', null, 'required', null, 'client');
        }
        $mform->addHelpButton('participantsamount', 'event_students', 'mod_bookit');

        // Add the "person in charge" field.
        $examinersresolver = examiner_pool_resolver::from_config($config);
        $legacyexaminerids = examiner_pool_resolver::get_legacy_user_ids_from_event($existingevent);
        $examinerlist = $examinersresolver->build_options($legacyexaminerids);
        $userselectoroptions = [
                'multiple' => false,
                'courseid' => $course[0]->id,
                'enrolid' => 0,
                'perpage' => $CFG->maxusersperpage,
                'userfields' => implode(',', fields::get_identity_fields($contextcourse, true)),
        ];
        if (!$examinersresolver->is_restricted()) {
            $userselectoroptions['ajax'] = 'enrol_manual/form-potential-user-selector';
        }
        $personinchargeelementname = 'personinchargeid';
        if (!$caneditevent && $existingevent) {
            $personinchargeelementname = 'personinchargeid_readonly';
            $mform->addElement(
                'static',
                $personinchargeelementname,
                get_string('event_personincharge', 'mod_bookit'),
                s($this->format_selector_display($existingevent->personinchargeid ?? '', $examinerlist))
            );
            $mform->addElement('hidden', 'personinchargeid');
            $mform->setType('personinchargeid', PARAM_TEXT);
        } else {
            $mform->addElement(
                'autocomplete',
                'personinchargeid',
                get_string(
                    'event_personincharge',
                    'mod_bookit'
                ),
                $examinerlist,
                $userselectoroptions
            );
            $mform->disabledIf('personinchargeid', 'editevent', 'neq');
            $mform->setType('personinchargeid', PARAM_TEXT);
            $mform->setDefault('personinchargeid', '');
        }
        if ($requirepublicfields) {
            $mform->addRule('personinchargeid', null, 'required', null, 'client');
        }
        $mform->addHelpButton($personinchargeelementname, 'event_personincharge', 'mod_bookit');

        // Add the "otherexaminers" field.
        $userselectoroptions['multiple'] = true;
        $otherexaminerselementname = 'otherexaminers';
        if (!$caneditevent && $existingevent) {
            $otherexaminerselementname = 'otherexaminers_readonly';
            $mform->addElement(
                'static',
                $otherexaminerselementname,
                get_string(
                    'event_otherexaminers',
                    'mod_bookit'
                ),
                s($this->format_selector_display($existingevent->otherexaminers ?? '', $examinerlist))
            );
            $mform->addElement('hidden', 'otherexaminers');
            $mform->setType('otherexaminers', PARAM_TEXT);
        } else {
            $mform->addElement(
                'autocomplete',
                'otherexaminers',
                get_string(
                    'event_otherexaminers',
                    'mod_bookit'
                ),
                $examinerlist,
                $userselectoroptions
            );
            $mform->disabledIf('otherexaminers', 'editevent', 'neq');
            $mform->setType('otherexaminers', PARAM_TEXT);
        }
        $mform->addHelpButton($otherexaminerselementname, 'event_otherexaminers', 'mod_bookit');

        if ($this->is_optional_field_enabled($config, 'coursetemplate')) {
            // Add the coursetemplate field.
            $coursetemplates = [0 => get_string('default')];
            $mform->addElement('select', 'coursetemplate', get_string('select_coursetemplate', 'mod_bookit'), $coursetemplates);
            $mform->disabledIf('coursetemplate', 'editevent', 'neq');
            if ($requirepublicfields) {
                $mform->addRule('coursetemplate', null, 'required', null, 'client');
            }
            $mform->addHelpButton('coursetemplate', 'select_coursetemplate', 'mod_bookit');
        } else {
            $mform->addElement('hidden', 'coursetemplate');
            $mform->setType('coursetemplate', PARAM_INT);
        }

        if ($this->is_optional_field_enabled($config, 'timecompensation')) {
            $mform->addElement(
                'advcheckbox',
                'timecompensation',
                get_string('event_timecompensation', 'mod_bookit'),
                get_string('yes')
            );
            $mform->disabledIf('timecompensation', 'editevent', 'neq');
            $mform->setType('timecompensation', PARAM_BOOL);
            $mform->addHelpButton('timecompensation', 'event_timecompensation', 'mod_bookit');
        } else {
            $mform->addElement('hidden', 'timecompensation');
            $mform->setType('timecompensation', PARAM_BOOL);
        }

        if ($this->is_optional_field_enabled($config, 'compensationfordisadvantages')) {
            $mform->addElement(
                'textarea',
                'compensationfordisadvantages',
                get_string(
                    'event_compensationfordisadvantages',
                    'mod_bookit'
                ),
                ['size' => '64']
            );
            $mform->disabledIf('compensationfordisadvantages', 'editevent', 'neq');
            $mform->setType('compensationfordisadvantages', PARAM_TEXT);
            $mform->addHelpButton('compensationfordisadvantages', 'event_compensationfordisadvantages', 'mod_bookit');
        } else {
            $mform->addElement('hidden', 'compensationfordisadvantages');
            $mform->setType('compensationfordisadvantages', PARAM_TEXT);
        }

        if ($this->is_optional_field_enabled($config, 'notes')) {
            $mform->addElement(
                'textarea',
                'notes',
                get_string("event_notes", "mod_bookit"),
                'wrap="virtual" rows="5" cols="50"'
            );
            $mform->disabledIf('notes', 'editevent', 'neq');
            $mform->addHelpButton('notes', 'event_notes', 'mod_bookit');
        } else {
            $mform->addElement('hidden', 'notes');
            $mform->setType('notes', PARAM_TEXT);
        }

        // Internal fields.
        if ($caneditinternal) {
            $mform->addElement('header', 'header_internal', get_string('header_internal', 'mod_bookit'));
            $mform->setExpanded('header_internal', true);
        }
        if ($this->is_optional_field_enabled($config, 'refcourseid')) {
            $mform->addElement(
                'course',
                'refcourseid',
                get_string(
                    'event_refcourseid',
                    'mod_bookit'
                ),
                ['multiple' => false, 'showhidden' => true, 'exclude' => '']
            );
            $mform->setType('refcourseid', PARAM_INT);
            $mform->setDefault('refcourseid', 0);
            $mform->hideIf('refcourseid', 'editinternal', 'neq');
            $mform->addHelpButton('refcourseid', 'event_refcourseid', 'mod_bookit');
        } else {
            $mform->addElement('hidden', 'refcourseid');
            $mform->setType('refcourseid', PARAM_INT);
        }

        if ($canviewrestrictedfields) {
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
            $supportpersonselementname = 'supportpersons';
            if (!$caneditinternal && $existingevent) {
                $supportpersonselementname = 'supportpersons_readonly';
                $mform->addElement(
                    'static',
                    $supportpersonselementname,
                    get_string(
                        'event_supportperson',
                        'mod_bookit'
                    ),
                    s($this->format_selector_display($existingevent->supportpersons ?? '', $supportpersons))
                );
                $mform->addElement('hidden', 'supportpersons');
                $mform->setType('supportpersons', PARAM_TEXT);
            } else {
                $mform->addElement(
                    'autocomplete',
                    'supportpersons',
                    get_string(
                        'event_supportperson',
                        'mod_bookit'
                    ),
                    $supportpersons,
                    $userselectoroptions
                );
                $mform->setType('supportpersons', PARAM_TEXT);
                $mform->disabledIf('supportpersons', 'editinternal', 'neq', 1);
            }
            $mform->addHelpButton($supportpersonselementname, 'event_supportperson', 'mod_bookit');
        } else {
            $mform->addElement('hidden', 'supportpersons');
            $mform->setType('supportpersons', PARAM_TEXT);
        }

        if ($canviewrestrictedfields) {
            // Don't use PARAM_INT, because it converts an empty text field to 0.
            // In our case, an empty field should mean be the inherited default.
            $mform->addElement('text', 'extratimebefore', get_string('settings_extratime_before', 'mod_bookit'));
            $mform->setType('extratimebefore', PARAM_ALPHANUM);
            $mform->addRule('extratimebefore', null, 'numeric', null, 'client');
            $mform->disabledIf('extratimebefore', 'editinternal', 'neq', 1);
            $mform->addElement('text', 'extratimeafter', get_string('settings_extratime_after', 'mod_bookit'));
            $mform->setType('extratimeafter', PARAM_ALPHANUM);
            $mform->addRule('extratimeafter', null, 'numeric', null, 'client');
            $mform->disabledIf('extratimeafter', 'editinternal', 'neq', 1);
        } else {
            $mform->addElement('hidden', 'extratimebefore');
            $mform->setType('extratimebefore', PARAM_ALPHANUM);
            $mform->addElement('hidden', 'extratimeafter');
            $mform->setType('extratimeafter', PARAM_ALPHANUM);
        }

        $bookingstatusoptions = [];
        $currentbookingstatus = (int)($existingevent->bookingstatus ?? self::BOOKINGSTATUS_NEW);
        if ($caneditinternal) {
            $bookingstatusoptions = explode(',', get_string('event_bookingstatus_list', 'mod_bookit'));
        } else if ($showbookingstatus) {
            $bookingstatusoptions[$currentbookingstatus] = get_string('event_bookingstatus_' . $currentbookingstatus, 'mod_bookit');
            if (
                ($cancancelonly || $canselfcancelnew)
                && event_access_manager::can_transition_booking_status(
                    $currentbookingstatus,
                    event_access_manager::BOOKINGSTATUS_CANCELED
                )
            ) {
                $bookingstatusoptions[event_access_manager::BOOKINGSTATUS_CANCELED] = get_string(
                    'event_bookingstatus_' . event_access_manager::BOOKINGSTATUS_CANCELED,
                    'mod_bookit'
                );
            }
        }

        // Add the "bookingstatus" field.
        if ($participantpastreadonly) {
            $mform->addElement(
                'static',
                'bookingstatusreadonly',
                get_string('event_bookingstatus', 'mod_bookit'),
                get_string('event_bookingstatus_' . $currentbookingstatus, 'mod_bookit')
            );
        } else if ($showbookingstatus && !$cancancelonly) {
            $mform->addElement(
                'select',
                'bookingstatus',
                get_string('event_bookingstatus', 'mod_bookit'),
                $bookingstatusoptions
            );
            $mform->disabledIf('bookingstatus', 'editbookingstatus', 'neq', 1);
            $mform->addHelpButton('bookingstatus', 'event_bookingstatus', 'mod_bookit');
        } else if ($cancancelonly || $showbookingstatusreadonly) {
            $mform->addElement(
                'static',
                'bookingstatusreadonly',
                get_string('event_bookingstatus', 'mod_bookit'),
                get_string('event_bookingstatus_' . $currentbookingstatus, 'mod_bookit')
            );
            $mform->addElement('hidden', 'bookingstatus');
            $mform->setType('bookingstatus', PARAM_INT);
        } else {
            $mform->addElement('hidden', 'bookingstatus');
            $mform->setType('bookingstatus', PARAM_INT);
        }

        if ($participantpastreadonly) {
            $mform->addElement(
                'static',
                'pastparticipantnotice',
                '',
                get_string('event_past_participant_notice', 'mod_bookit')
            );
        } else if ($cancancelonly) {
            $mform->addElement(
                'static',
                'cancelonlynotice',
                '',
                get_string('event_cancel_only_notice', 'mod_bookit')
            );
        }

        // Add the "internalnotes" field.
        if ($canviewrestrictedfields || $caneditinternalnotes) {
            $mform->addElement(
                'textarea',
                'internalnotes',
                get_string("event_internalnotes", "mod_bookit"),
                'wrap="virtual" rows="5" cols="50"'
            );
            $mform->disabledIf('internalnotes', 'editinternalnotes', 'neq', 1);
            $mform->addHelpButton('internalnotes', 'event_internalnotes', 'mod_bookit');
            if (!$caneditinternal && $caneditinternalnotes) {
                $mform->addElement(
                    'static',
                    'supportpersoninternalnotesnotice',
                    '',
                    get_string('event_supportperson_internalnotes_notice', 'mod_bookit')
                );
            }
        } else {
            $mform->addElement('hidden', 'internalnotes');
            $mform->setType('internalnotes', PARAM_TEXT);
        }

        $timeclicked = $this->optional_param('timeclicked', null, PARAM_TEXT);
        $possiblestarttimes = [];
        $selectedtime = null;

        if ($timeclicked && $roomoptions) {
            $timeclicked = new \DateTimeImmutable($timeclicked);
            $timeclickedstamp = $timeclicked->getTimestamp();
            $startdate = $timeclicked->setTime(0, 0);
            $this->_form->setDefault('startdate', $timeclicked->getTimestamp());

            [$possiblestarttimes, ] = get_possible_starttimes::list_possible_starttimes(
                \DateTime::createFromImmutable($startdate),
                $eventdefaultduration,
                array_key_first($roomoptions),
                null,
                $participantpastreadonly || event_access_manager::can_manage_past_bookings($context),
            );

            $smallestdiff = 1e9;
            $selectedtime = null;

            foreach ($possiblestarttimes as $possiblestarttime => $str) {
                if (abs($possiblestarttime - $timeclickedstamp) < $smallestdiff) {
                    $smallestdiff = abs($possiblestarttime - $timeclickedstamp);
                    $selectedtime = $possiblestarttime;
                }
            }
        }

        // Check if booking is completed (status >= 2: Accepted/Canceled/Rejected).
        $eventid = $this->optional_param('id', null, PARAM_INT);
        $bookingcompleted = false;
        $bookedresources = [];
        if (!empty($eventid) && $resourcesenabled) {
            $eventrec = $DB->get_record('bookit_event', ['id' => $eventid], 'bookingstatus');
            if ($eventrec && (int)$eventrec->bookingstatus >= 2) {
                $bookingcompleted = true;
                foreach (resource_manager::get_resources_of_event($eventid) as $rid => $br) {
                    $bookedresources[$rid] = [
                        'amount' => $br->get_amount(),
                        'status' => $br->get_status()->value,
                    ];
                }
            }
        }

        /** @var \MoodleQuickForm_select $starttimeel */
        $starttimeel = $mform->getElement('starttime');
        $starttimeel->removeOptions();
        $starttimeel->loadArray($possiblestarttimes);
        $mform->setDefault('starttime', $selectedtime);

        // Get active resources grouped by category for booking form.
        if ($resourcesenabled) {
            $resourcesdata = resource_manager::get_active_resources_grouped();
            $this->add_resources_fields($mform, $resourcesdata, $bookingcompleted, $bookedresources);
        }
    }

    /**
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     *
     * @return void
     * @throws coding_exception|dml_exception
     */
    public function definition_after_data(): void {
        global $DB, $USER, $PAGE;   // The $PAGE is needed for JS injection.
        $mform =& $this->_form;
        $data = $this->get_submitted_data() ?? $this->event;
        $caneditinternal = (bool)($mform->getElementValue('editinternal')[0] ?? 0);
        $cancancelonly = (bool)($mform->getElementValue('cancelonly')[0] ?? 0);

        $context = $this->get_context_for_dynamic_submission();
        $currenteventid = (int)($data->id ?? $this->event->id ?? 0);
        $existingevent = $currenteventid > 0 ? event_manager::get_event($currenteventid) : null;
        $participantpastreadonly = $existingevent
            && event_access_manager::should_block_participant_past_edit($existingevent, $context, (int)$USER->id);
        $creatorid = $this->_form->getElementValue('usermodified');
        $user = $DB->get_record('user', ['id' => $creatorid]);
        $mform->getElement('usermodified')->setValue(
            fullname($user, has_capability('moodle/site:viewfullnames', $context)) // ...TODO: find better way?
        );

        // Week-day validation  – server side.
        $mform->addRule(
            'starttime',
            get_string('invalidweekday', 'mod_bookit'),
            'callback',
            function ($val): bool {
                // The $val arrives as an array from date_time_selector.
                if (is_array($val)) {
                    // Make_timestamp( year, month, day, hour, minute ).
                    $ts = make_timestamp(
                        (int) $val['year'],
                        (int) $val['month'],
                        (int) $val['day'],
                        (int) ($val['hour'] ?? 0),
                        (int) ($val['minute'] ?? 0)
                    );
                } else {
                    $ts = (int) $val; // Fallback: already a Unix timestamp.
                }

                $allowed = bookit_allowed_weekdays(); // 0 = Sun … 6 = Sat.
                $weekday = (int) date('w', $ts);
                return in_array($weekday, $allowed, true);
            },
            'server'
        );

        // Quick client-side alert (does not block submission).
        $allowed = implode(',', bookit_allowed_weekdays());
        if ($allowed !== '') {
            $PAGE->requires->js_init_code("
                require(['jquery'], function($) {
                    const allowed = [$allowed];
                    $('#id_starttime_day, #id_starttime_month, #id_starttime_year').on('change', function () {
                        const d = new Date(
                            $('#id_starttime_year').val(),
                            $('#id_starttime_month').val() - 1,
                            $('#id_starttime_day').val()
                        );
                        if (!allowed.includes(d.getDay())) {
                            alert('" . get_string('invalidweekday', 'mod_bookit') . "');
                        }
                    });
                });
            ");
            if (!$caneditinternal) {
                if ($this->event) {
                    $mform->setConstant('extratimebefore', $this->event->extratimebefore);
                    $mform->setConstant('extratimeafter', $this->event->extratimeafter);
                } else {
                    $mform->setConstant('extratimebefore', null);
                    $mform->setConstant('extratimeafter', null);
                }
            }

            $selectedroomid = $data->roomid ?? $this->event->roomid ?? null;
            $selectedduration = $data->duration ?? $this->event->duration ?? null;
            $selectedstartdate = $data->startdate ?? null;
            if ($selectedstartdate === null && !empty($this->event->starttime)) {
                $selectedstartdate = (new \DateTime())->setTimestamp((int)$this->event->starttime)->setTime(0, 0)->getTimestamp();
            }
            if (
                $data
                && $selectedroomid
                && !is_null($selectedduration)
                && !is_null($selectedstartdate)
                && $DB->record_exists('bookit_room', ['id' => $selectedroomid])
            ) {
                $excepteventid = (int)($data->id ?? $this->event->id ?? 0) ?: null;
                /** @var \MoodleQuickForm_select $starttimeel */
                $starttimeel = $mform->getElement('starttime');
                $starttimeel->removeOptions();
                [$possiblestarttimes, ] = get_possible_starttimes::list_possible_starttimes(
                    (new \DateTime())->setTimestamp((int)$selectedstartdate),
                    $selectedduration,
                    $selectedroomid,
                    $excepteventid,
                    $participantpastreadonly || event_access_manager::can_manage_past_bookings($context)
                );
                $starttimeel->loadArray($possiblestarttimes);
                $currentstarttime = (int)($data->starttime ?? 0);
                if ($currentstarttime > 0) {
                    $starttimeel->updateAttributes(['data-current-starttime' => (string)$currentstarttime]);
                }
                if (
                    $participantpastreadonly
                    && $currentstarttime > 0
                    && !array_key_exists($currentstarttime, $possiblestarttimes)
                ) {
                    $possiblestarttimes = [
                        $currentstarttime => (new \DateTime())->setTimestamp($currentstarttime)->format('H:i'),
                    ] + $possiblestarttimes;
                    $starttimeel->loadArray($possiblestarttimes);
                }
                if ($currentstarttime > 0 && array_key_exists($currentstarttime, $possiblestarttimes)) {
                    $starttimeel->setSelected((string)$currentstarttime);
                    $starttimeel->setValue((string)$currentstarttime);
                    $mform->setDefault('starttime', $currentstarttime);
                }
            }
        }

        if ($cancancelonly) {
            $mform->getElement('bookingstatus')->setValue((string)event_access_manager::BOOKINGSTATUS_CANCELED);
            $mform->setConstant('bookingstatus', event_access_manager::BOOKINGSTATUS_CANCELED);
        }

        $this->inject_examiner_selector_labels($mform, $data);
    }

    /**
     * Check whether an optional calendar field is enabled in the shared booking profile.
     *
     * @param \stdClass $config
     * @param string $fieldname
     * @return bool
     */
    private function is_optional_field_enabled(\stdClass $config, string $fieldname): bool {
        $rawfields = (string)($config->calendar_optional_fields ?? '');
        if ($rawfields === '') {
            $rawfields = 'timecompensation,compensationfordisadvantages,notes,refcourseid,coursetemplate';
        }
        $enabledfields = array_values(array_filter(array_map('trim', explode(',', $rawfields))));

        return in_array($fieldname, $enabledfields, true);
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        $e = new StdClass();
        $id = $this->optional_param('id', null, PARAM_INT);

        if (!empty($id)) {
            $e = event_manager::get_event($id);
            $date = (new \DateTime())->setTimestamp($e->starttime);
            $date->setTime(0, 0);
            $e->startdate = $date->getTimestamp();
            $this->event = $e;
        }
        $e->cmid = $this->optional_param('cmid', null, PARAM_INT);

        $this->set_data($e);
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
        global $USER;

        $context = $this->get_context_for_dynamic_submission();
        $eventid = $this->optional_param('id', null, PARAM_INT);
        if (empty($eventid)) {
            return;
        }

        $event = event_manager::get_event($eventid);
        if (event_access_manager::is_observer_restricted_mode($context)) {
            throw new moodle_exception('observer_no_detail_access', 'mod_bookit');
        }

        if (
            !event_access_manager::can_user_view_event_details($event, $context, (int)$USER->id)
            && !event_access_manager::can_manage_open_requests($context)
            && !has_capability('mod/bookit:editevent', $context)
        ) {
            throw new moodle_exception('nopermissions', 'error', '', 'view event details');
        }
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array ...
     * @throws dml_exception|coding_exception
     */
    public function process_dynamic_submission(): array {
        global $USER;
        $formdata = $this->get_data();
        $context = $this->get_context_for_dynamic_submission();
        $currentevent = null;
        $submittedstarttime = $this->optional_param('starttime', null, PARAM_INT);
        $submittedduration = $this->optional_param('duration', null, PARAM_INT);
        $caneditpublic = empty($formdata->id) || has_capability('mod/bookit:editevent', $context);
        $caneditinternal = has_capability('mod/bookit:editinternal', $context);
        $caneditinternalnotes = $caneditinternal;
        $caneditbookingstatus = $caneditinternal;
        $canmanagepastbookings = event_access_manager::can_manage_past_bookings($context);
        $bookingstatustransition = null;
        $cancancelonly = false;
        $statusonlyselfcancel = false;
        $resourcesenabled = event_access_manager::is_resources_enabled();
        $currentrecord = null;

        if (!empty($formdata->id)) {
            $currentevent = bookit_event::from_database((int)$formdata->id);
            $currentrecord = event_manager::get_event((int)$formdata->id);
            $participantpastreadonly = event_access_manager::should_block_participant_past_edit(
                $currentrecord,
                $context,
                (int)$USER->id
            );
            $caneditpublic = $caneditpublic
                || event_access_manager::can_participant_edit_event($currentrecord, (int)$USER->id);
            if ($participantpastreadonly) {
                $caneditpublic = false;
            }
            $caneditinternalnotes = $caneditinternal
                || event_access_manager::can_supportperson_edit_internal_notes($currentrecord, $context, (int)$USER->id);
            $cancancelonly = !$participantpastreadonly
                && event_access_manager::can_participant_cancel_only($currentrecord, $context, (int)$USER->id);
            $canselfcancelnew = !$participantpastreadonly
                && event_access_manager::can_self_cancel_new_request($currentrecord, $context, (int)$USER->id);
            $caneditbookingstatus = $caneditinternal
                || $cancancelonly
                || $canselfcancelnew;
            $requestedstatus = (int)($formdata->bookingstatus ?? $currentrecord->bookingstatus);
            $statusonlyselfcancel = $canselfcancelnew
                && $requestedstatus === event_access_manager::BOOKINGSTATUS_CANCELED;

            if ($participantpastreadonly) {
                if ($this->has_past_participant_mutation_attempt($currentrecord, $formdata)) {
                    throw new moodle_exception('event_past_participant_notice', 'mod_bookit');
                }
                return [];
            }

            if (
                $cancancelonly
                && !in_array(
                    $requestedstatus,
                    [(int)$currentrecord->bookingstatus, event_access_manager::BOOKINGSTATUS_CANCELED],
                    true
                )
            ) {
                throw new moodle_exception('event_cancel_only_notice', 'mod_bookit');
            }

            if (!$caneditpublic && !$caneditinternalnotes && !$caneditbookingstatus) {
                throw new moodle_exception('nopermissions', 'error', '', 'update event');
            }
        }

        $mappings = [];
        if ($resourcesenabled && $caneditpublic && !$statusonlyselfcancel) {
            foreach (resource_manager::get_active_resources_grouped() as $categorygroup) {
                // Rooms.
                foreach ($categorygroup['resources'] as $resource) {
                    $id = $resource['id'];
                    if ($categorygroup['category']['name'] === 'Rooms') {
                        if (($formdata->roomid ?? $formdata->room ?? null) == $id) {
                            $mappings[] = (object) [
                                    'resourceid' => (int)($formdata->roomid ?? $formdata->room),
                                    'amount' => 1,
                            ];
                        }
                    } else {
                        // Other Resources.
                        $checkboxname = 'checkbox_' . $id;
                        if ($formdata->$checkboxname ?? false) {
                            // Amountirrelevant resources have no amount input; store 1 as neutral value.
                            $amount = $resource['amountirrelevant'] ? 1 : (int)($formdata->{'resource_' . $id} ?? 1);
                            $mappings[] = (object) [
                                    'resourceid' => $id,
                                    'amount' => $amount,
                            ];
                        }
                    }
                }
            }
        } else if ($currentevent) {
            $mappings = $currentevent->resources;
        }
        $formdata->resources = $mappings;

        if ($caneditpublic && !$statusonlyselfcancel) {
            $resolvedstarttime = $submittedstarttime ?? ($formdata->starttime ?? null);
            $resolvedduration = $submittedduration ?? ($formdata->duration ?? null) ?? $currentevent?->duration;

            if (
                $resolvedstarttime === null
                && $currentrecord !== null
                && $this->should_reuse_current_starttime($currentrecord, $formdata, $submittedstarttime, $submittedduration)
            ) {
                $resolvedstarttime = (int)$currentrecord->starttime;
            }

            if ($resolvedstarttime === null || $resolvedduration === null) {
                if (!$canmanagepastbookings) {
                    throw new moodle_exception('event_error_mintime', 'mod_bookit');
                }
                throw new moodle_exception('missingrequiredfield', 'error');
            }

            if (
                !$canmanagepastbookings
                && get_possible_starttimes::is_starttime_in_past((int)$resolvedstarttime)
            ) {
                throw new moodle_exception('event_error_mintime', 'mod_bookit');
            }

            $formdata->starttime = (int)$resolvedstarttime;
            $formdata->duration = (int)$resolvedduration;
            // Calculate endtime.
            $formdata->endtime = $formdata->starttime + $formdata->duration * 60;
        } else if ($currentevent) {
            $formdata->starttime = $currentevent->starttime;
            $formdata->endtime = $currentevent->endtime;
            $formdata->duration = $currentevent->duration;
            $formdata->semester = $currentevent->semester;
            $formdata->institutionid = $currentevent->institutionid;
            $formdata->roomid = $currentevent->roomid;
            $formdata->participantsamount = $currentevent->participantsamount;
            $formdata->timecompensation = $currentevent->timecompensation;
            $formdata->compensationfordisadvantages = $currentevent->compensationfordisadvantages;
            $formdata->personinchargeid = $currentevent->personinchargeid;
            $formdata->otherexaminers = $currentevent->otherexaminers;
            $formdata->coursetemplate = $currentevent->coursetemplate;
            $formdata->notes = $currentevent->notes;
            $formdata->refcourseid = $currentevent->refcourseid;
            $formdata->name = $currentevent->name;
        }

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

        if (!is_int($formdata->extratimebefore)) {
            $formdata->extratimebefore = null;
        }

        if (!is_int($formdata->extratimeafter)) {
            $formdata->extratimeafter = null;
        }

        if ($currentevent && !$caneditinternal) {
            $formdata->supportpersons = $currentevent->supportpersons;
            $formdata->extratimebefore = $currentevent->extratimebefore;
            $formdata->extratimeafter = $currentevent->extratimeafter;
            if (!$caneditbookingstatus) {
                $formdata->bookingstatus = $currentevent->bookingstatus;
            }
        }

        if ($currentevent && !$caneditinternalnotes) {
            $formdata->internalnotes = $currentevent->internalnotes;
        }

        if ($cancancelonly) {
            $formdata->bookingstatus = event_access_manager::BOOKINGSTATUS_CANCELED;
        }

        if ($currentevent && $caneditbookingstatus && !$caneditinternal) {
            $requestedstatus = (int)($formdata->bookingstatus ?? $currentevent->bookingstatus);
            $allowedstatuses = [
                (int)$currentevent->bookingstatus,
            ];
            if (
                event_access_manager::can_transition_booking_status(
                    (int)$currentevent->bookingstatus,
                    event_access_manager::BOOKINGSTATUS_CANCELED
                )
            ) {
                $allowedstatuses[] = event_access_manager::BOOKINGSTATUS_CANCELED;
            }

            if (!in_array($requestedstatus, $allowedstatuses, true)) {
                throw new moodle_exception('nopermissions', 'error', '', 'change booking status');
            }

            if ($requestedstatus !== (int)$currentevent->bookingstatus) {
                $bookingstatustransition = $requestedstatus;
                $formdata->bookingstatus = (int)$currentevent->bookingstatus;
            } else {
                $formdata->bookingstatus = $requestedstatus;
            }
        }

        $event = bookit_event::from_record($formdata);
        $cmid = (int)$this->optional_param('cmid', 0, PARAM_INT);
        $persistedevent = event_manager::save_event_with_lifecycle_tracking(
            $event,
            $currentevent,
            (int)$USER->id,
            $context,
            $cmid > 0 ? $cmid : null
        );
        if ($bookingstatustransition !== null) {
            $persistedrecord = event_manager::get_event((int)$persistedevent->id);
            event_manager::transition_booking_status(
                $persistedrecord,
                $bookingstatustransition,
                (int)$USER->id,
                $context,
                $cmid > 0 ? $cmid : null
            );
        }

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

    /**
     * Add resources fields grouped by category.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @param array $resourcesdata Grouped resources data from resource_manager
     * @param bool $bookingcompleted When true, only booked resources are shown (read-only).
     * @param array $bookedresources Map of resourceid => ['amount' => int, 'status' => string].
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    private function add_resources_fields(
        \MoodleQuickForm $mform,
        array $resourcesdata,
        bool $bookingcompleted = false,
        array $bookedresources = []
    ): void {
        if (empty($resourcesdata)) {
            return;
        }

        // Load room data for room icons (shortname + color per resource).
        $resourcerooms = resource_manager::get_resource_rooms();

        foreach ($resourcesdata as $categorygroup) {
            $category = $categorygroup['category'];
            $resources = $categorygroup['resources'];

            // For completed bookings: skip category entirely if none of its resources were booked.
            if ($bookingcompleted) {
                $hasbooked = false;
                foreach ($resources as $resource) {
                    if (array_key_exists($resource['id'], $bookedresources)) {
                        $hasbooked = true;
                        break;
                    }
                }
                if (!$hasbooked) {
                    continue;
                }
            }

            // Add category header.
            $mform->addElement('header', 'header_cat_' . $category['id'], $category['name']);
            $mform->setExpanded('header_cat_' . $category['id'], true);

            // Add resources in this category.
            foreach ($resources as $resource) {
                // When booking is completed, only show resources that were booked.
                if ($bookingcompleted && !array_key_exists($resource['id'], $bookedresources)) {
                    continue;
                }

                // For completed bookings: show read-only status badge + amount.
                if ($bookingcompleted) {
                    $bookedinfo = $bookedresources[$resource['id']];
                    $bookedamount = $bookedinfo['amount'];
                    $bookedstatus = $bookedinfo['status'];
                    $statusclassmap = [
                        bookit_resource_status::REQUESTED->value  => 'badge-secondary',
                        bookit_resource_status::CONFIRMED->value  => 'badge-success',
                        bookit_resource_status::INPROGRESS->value => 'badge-primary',
                        bookit_resource_status::REJECTED->value   => 'badge-danger',
                    ];
                    $badgeclass = 'badge ' . ($statusclassmap[$bookedstatus] ?? 'badge-secondary');
                    $statuslabel = get_string('resources:status_' . $bookedstatus, 'mod_bookit');
                    $statichtml = '<span class="' . $badgeclass . '">' . $statuslabel . '</span>';
                    if (!$resource['amountirrelevant']) {
                        $statichtml .= ' &nbsp;' . get_string('booking:resource_amount', 'mod_bookit')
                            . ': <strong>' . $bookedamount . '</strong>';
                    }
                    $mform->addElement('static', 'resourcestatus_' . $resource['id'], $resource['name'], $statichtml);
                    continue;
                }

                // Parse roomids JSON. NULL means available in all rooms (null sentinel passed to JS).
                // A non-null array restricts the resource to those specific rooms.
                if ($resource['roomids'] !== null && $resource['roomids'] !== '') {
                    $roomidsarray = json_decode($resource['roomids'], true);
                    $roomidsarray = is_array($roomidsarray) ? array_map('intval', $roomidsarray) : [];
                } else {
                    $roomidsarray = null; // Null → JS treats as "available in all rooms".
                }

                $groupelements = [];

                // Checkbox for resource selection (no text – name is used as group label).
                $groupelements[] = $mform->createElement(
                    'advcheckbox',
                    'checkbox_' . $resource['id'],
                    '',
                    '',
                    ['group' => 1],
                    [0, 1]
                );
                $mform->disabledIf('checkbox_' . $resource['id'], 'editevent', 'neq');

                // Info icon with popover (Moodle-native pattern: data-toggle=popover, trigger=focus).
                $popoverparts = [];
                if (!empty($resource['description'])) {
                    $popoverparts[] = s($resource['description']);
                }
                if (!$resource['amountirrelevant'] && $resource['amount'] > 0) {
                    $popoverparts[] = get_string('booking:resource_max', 'mod_bookit', $resource['amount']);
                }
                if (!empty($popoverparts)) {
                    $popovercontent = implode('<br>', $popoverparts);
                    $infoicon = '<a class="btn btn-link p-0 ms-1 icon-no-margin" role="button" tabindex="0"'
                        . ' data-container="body" data-toggle="popover"'
                        . ' data-placement="right" data-content="' . $popovercontent . '"'
                        . ' data-html="true" data-trigger="focus"'
                        . ' aria-label="' . get_string('resources:info', 'mod_bookit') . '">'
                        . '<i class="fa fa-info-circle text-info"></i>'
                        . '</a>';
                    $groupelements[] = $mform->createElement('static', 'info_' . $resource['id'], '', $infoicon);
                }

                // Room icons: small colored badges with room shortname, fixed-width container for alignment.
                $rooms = $resourcerooms[$resource['id']] ?? [];
                $roomhtml = '<span class="bookit-resource-rooms ms-2">';
                foreach ($rooms as $room) {
                    $shortname = s($room['shortname'] ?? $room['name']);
                    $color = s($room['color']);
                    $roomhtml .= '<span class="badge ms-1" style="background-color:' . $color . ';color:#fff;"'
                        . ' title="' . s($room['name']) . '">' . $shortname . '</span>';
                }
                $roomhtml .= '</span>';
                $groupelements[] = $mform->createElement('static', 'rooms_' . $resource['id'], '', $roomhtml);

                // Amount field (only if not amount irrelevant).
                if (!$resource['amountirrelevant']) {
                    $groupelements[] = $mform->createElement(
                        'text',
                        'resource_' . $resource['id'],
                        get_string('booking:resource_amount', 'mod_bookit'),
                        ['size' => '4', 'data-resource-max' => (int)$resource['amount']]
                    );
                    $mform->setType('resource_' . $resource['id'], PARAM_INT);
                    $mform->disabledIf('resource_' . $resource['id'], 'checkbox_' . $resource['id']);
                    $mform->setDefault('resource_' . $resource['id'], 1);

                    // Add max amount as static text.
                    $groupelements[] = $mform->createElement(
                        'static',
                        'resource_max_' . $resource['id'],
                        '',
                        get_string('booking:resource_max', 'mod_bookit', $resource['amount'])
                    );
                }

                // Set data attribute for room filtering on the checkbox element.
                $groupelements[0]->updateAttributes(['data-resource-rooms' => json_encode($roomidsarray)]);

                $mform->addGroup(
                    $groupelements,
                    'resourcegroup_' . $resource['id'],
                    $resource['name'],
                    [' '],
                    false
                );
            }
        }
    }

    /**
     * Server-side validation: check resource amounts are within allowed range.
     *
     * @param array $data Form data
     * @param array $files Uploaded files
     * @return array Validation errors
     * @throws coding_exception
     * @throws dml_exception
     */
    #[\Override]
    public function validation($data, $files): array {
        global $USER;
        $context = $this->get_context_for_dynamic_submission();
        $canmanagepastbookings = event_access_manager::can_manage_past_bookings($context);
        $existingevent = null;
        $submittedstarttime = $this->_ajaxformdata['starttime'] ?? null;
        $resourcesenabled = event_access_manager::is_resources_enabled();
        if (!empty($data['id'])) {
            $existingevent = event_manager::get_event((int)$data['id']);
            $participantpastreadonly = event_access_manager::should_block_participant_past_edit(
                $existingevent,
                $context,
                (int)$USER->id
            );
            $caneditpublic = has_capability('mod/bookit:editevent', $context)
                || event_access_manager::can_participant_edit_event($existingevent, (int)$USER->id);
            if ($participantpastreadonly) {
                $caneditpublic = false;
            }
            $caneditinternal = has_capability('mod/bookit:editinternal', $context);
            $caneditinternalnotes = $caneditinternal
                || event_access_manager::can_supportperson_edit_internal_notes($existingevent, $context, (int)$USER->id);
            $cancancelonly = !$participantpastreadonly
                && event_access_manager::can_participant_cancel_only($existingevent, $context, (int)$USER->id);
            $canselfcancelnew = !$participantpastreadonly
                && event_access_manager::can_self_cancel_new_request($existingevent, $context, (int)$USER->id);

            if ($participantpastreadonly && $this->has_past_participant_mutation_attempt($existingevent, (object)$data)) {
                $errors = parent::validation($data, $files);
                $errors['name'] = get_string('event_past_participant_notice', 'mod_bookit');
                return $errors;
            }

            if ($participantpastreadonly) {
                return parent::validation($data, $files);
            }

            if (!$caneditpublic && !$caneditinternalnotes && !$cancancelonly && !$canselfcancelnew) {
                $errors = parent::validation($data, $files);
                $errors['name'] = get_string('nopermissions', 'error');
                return $errors;
            }

            $statusonlyselfcancel = $canselfcancelnew
                && (int)($data['bookingstatus'] ?? $existingevent->bookingstatus)
                    === event_access_manager::BOOKINGSTATUS_CANCELED;

            if (!$caneditpublic || $statusonlyselfcancel) {
                $data['name'] = $data['name'] ?? $existingevent->name;
                $data['semester'] = $data['semester'] ?? $existingevent->semester;
                $data['institutionid'] = $data['institutionid'] ?? $existingevent->institutionid;
                $data['roomid'] = $data['roomid'] ?? $existingevent->roomid;
                $data['startdate'] = $data['startdate'] ?? usergetdate($existingevent->starttime);
                $data['starttime'] = $data['starttime'] ?? $existingevent->starttime;
                $data['duration'] = $data['duration'] ?? $existingevent->duration;
                $data['participantsamount'] = $data['participantsamount'] ?? $existingevent->participantsamount;
                $data['personinchargeid'] = $data['personinchargeid'] ?? $existingevent->personinchargeid;
                $data['otherexaminers'] = $data['otherexaminers'] ?? $existingevent->otherexaminers;
                $data['coursetemplate'] = $data['coursetemplate'] ?? $existingevent->coursetemplate;
                $data['timecompensation'] = $data['timecompensation'] ?? $existingevent->timecompensation;
                $data['compensationfordisadvantages'] = $data['compensationfordisadvantages']
                    ?? $existingevent->compensationfordisadvantages;
                $data['notes'] = $data['notes'] ?? $existingevent->notes;
                $data['refcourseid'] = $data['refcourseid'] ?? $existingevent->refcourseid;
            }
        }

        $errors = parent::validation($data, $files);

        if ($existingevent !== null) {
            if (!$caneditpublic && ($cancancelonly || $canselfcancelnew)) {
                $requestedstatus = (int)($data['bookingstatus'] ?? $existingevent->bookingstatus);
                $allowedstatuses = $cancancelonly
                    ? [(int)$existingevent->bookingstatus, event_access_manager::BOOKINGSTATUS_CANCELED]
                    : [(int)$existingevent->bookingstatus, event_access_manager::BOOKINGSTATUS_CANCELED];
                if (!in_array($requestedstatus, $allowedstatuses, true)) {
                    $errors['bookingstatus'] = get_string('event_cancel_only_notice', 'mod_bookit');
                }
            }
        }

        $roomid = $data['roomid'] ?? $existingevent->roomid ?? null;
        $participantsamount = $data['participantsamount'] ?? $existingevent->participantsamount ?? null;
        $room = !empty($roomid) ? room::get_record(['id' => $roomid], IGNORE_MISSING) : null;
        $starttime = $submittedstarttime ?? ($data['starttime'] ?? null);
        if (
            !$canmanagepastbookings
            && $starttime !== null
            && get_possible_starttimes::is_starttime_in_past((int)$starttime)
        ) {
            $errors['starttime'] = get_string('event_error_mintime', 'mod_bookit');
        }
        if ($room && $participantsamount !== null && $room->get('seats') != 0 && $room->get('seats') < $participantsamount) {
            $errors['roomid'] = get_string('room_doesnt_have_enough_seats', 'mod_bookit');
        }

        if ($resourcesenabled) {
            foreach (resource_manager::get_active_resources_grouped() as $categorygroup) {
                foreach ($categorygroup['resources'] as $resource) {
                    $id = $resource['id'];
                    if (empty($data['checkbox_' . $id]) || $resource['amountirrelevant']) {
                        continue;
                    }
                    $requested = (int)($data['resource_' . $id] ?? 0);
                    $maxamount = (int)$resource['amount'];
                    if ($requested < 1) {
                        $errors['resourcegroup_' . $id] = get_string(
                            'booking:resource_amount_too_low',
                            'mod_bookit'
                        );
                    } else if ($maxamount > 0 && $requested > $maxamount) {
                        $errors['resourcegroup_' . $id] = get_string(
                            'booking:resource_amount_invalid',
                            'mod_bookit',
                            (object)['requested' => $requested, 'available' => $maxamount]
                        );
                    }
                }
            }
        }

        $config = get_config('mod_bookit');
        $examinersresolver = examiner_pool_resolver::from_config($config);
        $legacyexaminerids = examiner_pool_resolver::get_legacy_user_ids_from_event($existingevent);
        $poolerrors = $examinersresolver->validate_assignments(
            isset($data['personinchargeid']) ? (string)$data['personinchargeid'] : null,
            isset($data['otherexaminers']) ? (string)$data['otherexaminers'] : null,
            $legacyexaminerids
        );
        foreach ($poolerrors as $fieldname => $message) {
            $errors[$fieldname] = $message;
        }

        return $errors;
    }

    /**
     * Check whether a blocked participant submission still tries to mutate historical booking fields.
     *
     * @param stdClass $currentevent
     * @param stdClass $formdata
     * @return bool
     */
    private function has_past_participant_mutation_attempt(stdClass $currentevent, stdClass $formdata): bool {
        $currentstate = [
            'name' => (string)($currentevent->name ?? ''),
            'semester' => (string)($currentevent->semester ?? ''),
            'institutionid' => (string)($currentevent->institutionid ?? ''),
            'roomid' => (string)($currentevent->roomid ?? ''),
            'starttime' => (string)($currentevent->starttime ?? ''),
            'duration' => (string)($currentevent->duration ?? ''),
            'participantsamount' => (string)($currentevent->participantsamount ?? ''),
            'personinchargeid' => (string)($currentevent->personinchargeid ?? ''),
            'otherexaminers' => $this->normalise_comma_separated_ids($currentevent->otherexaminers ?? ''),
            'coursetemplate' => (string)($currentevent->coursetemplate ?? ''),
            'timecompensation' => (string)($currentevent->timecompensation ?? ''),
            'compensationfordisadvantages' => (string)($currentevent->compensationfordisadvantages ?? ''),
            'notes' => (string)($currentevent->notes ?? ''),
            'refcourseid' => (string)($currentevent->refcourseid ?? ''),
            'bookingstatus' => (string)($currentevent->bookingstatus ?? ''),
        ];

        $submittedstate = [
            'name' => (string)($formdata->name ?? $currentevent->name ?? ''),
            'semester' => (string)($formdata->semester ?? $currentevent->semester ?? ''),
            'institutionid' => (string)($formdata->institutionid ?? $currentevent->institutionid ?? ''),
            'roomid' => (string)($formdata->roomid ?? ($formdata->room ?? $currentevent->roomid ?? '')),
            'starttime' => (string)($this->_ajaxformdata['starttime'] ?? ($formdata->starttime ?? $currentevent->starttime ?? '')),
            'duration' => (string)($this->_ajaxformdata['duration'] ?? ($formdata->duration ?? $currentevent->duration ?? '')),
            'participantsamount' => (string)($formdata->participantsamount ?? $currentevent->participantsamount ?? ''),
            'personinchargeid' => (string)($formdata->personinchargeid ?? $currentevent->personinchargeid ?? ''),
            'otherexaminers' => $this->normalise_comma_separated_ids(
                $formdata->otherexaminers ?? $currentevent->otherexaminers ?? ''
            ),
            'coursetemplate' => (string)($formdata->coursetemplate ?? $currentevent->coursetemplate ?? ''),
            'timecompensation' => (string)($formdata->timecompensation ?? $currentevent->timecompensation ?? ''),
            'compensationfordisadvantages' => (string)(
                $formdata->compensationfordisadvantages ?? $currentevent->compensationfordisadvantages ?? ''
            ),
            'notes' => (string)($formdata->notes ?? $currentevent->notes ?? ''),
            'refcourseid' => (string)($formdata->refcourseid ?? $currentevent->refcourseid ?? ''),
            'bookingstatus' => (string)($formdata->bookingstatus ?? $currentevent->bookingstatus ?? ''),
        ];

        foreach ($currentstate as $field => $value) {
            if ($submittedstate[$field] !== $value) {
                return true;
            }
        }

        foreach (array_keys((array)($this->_ajaxformdata ?? [])) as $fieldname) {
            if (str_starts_with($fieldname, 'checkbox_') || str_starts_with($fieldname, 'resource_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reuse the current start time when the request did not actually change the booking schedule.
     *
     * This keeps status-only updates working for service roles even if the start-time dropdown cannot
     * repopulate options for an already scheduled historical slot.
     *
     * @param stdClass $currentevent
     * @param stdClass $formdata
     * @param int|null $submittedstarttime
     * @param int|null $submittedduration
     * @return bool
     */
    private function should_reuse_current_starttime(
        stdClass $currentevent,
        stdClass $formdata,
        ?int $submittedstarttime,
        ?int $submittedduration
    ): bool {
        if ($submittedstarttime !== null || (($formdata->starttime ?? null) !== null && $formdata->starttime !== '')) {
            return false;
        }

        $currentstartdate = (new \DateTime())
            ->setTimestamp((int)$currentevent->starttime)
            ->setTime(0, 0)
            ->getTimestamp();
        $submittedstartdate = $this->normalise_submitted_startdate($formdata->startdate ?? null) ?? $currentstartdate;
        $submittedroomid = (int)($formdata->roomid ?? ($formdata->room ?? $currentevent->roomid ?? 0));
        $resolvedduration = $submittedduration ?? ($formdata->duration ?? $currentevent->duration ?? null);

        if ($resolvedduration === null) {
            return false;
        }

        return $submittedstartdate === $currentstartdate
            && $submittedroomid === (int)$currentevent->roomid
            && (int)$resolvedduration === (int)$currentevent->duration;
    }

    /**
     * Normalise a submitted start-date value to a midnight timestamp.
     *
     * @param mixed $value
     * @return int|null
     */
    private function normalise_submitted_startdate(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            if (
                !isset($value['year'], $value['month'], $value['day'])
                || $value['year'] === ''
                || $value['month'] === ''
                || $value['day'] === ''
            ) {
                return null;
            }

            return make_timestamp((int)$value['year'], (int)$value['month'], (int)$value['day'], 0, 0, 0);
        }

        return (new \DateTime())
            ->setTimestamp((int)$value)
            ->setTime(0, 0)
            ->getTimestamp();
    }

    /**
     * Normalise comma separated ids or id arrays for stable comparisons.
     *
     * @param mixed $value
     * @return string
     */
    private function normalise_comma_separated_ids(mixed $value): string {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $ids = array_values(array_filter(
            array_map('trim', explode(',', (string)$value)),
            static fn(string $id): bool => $id !== ''
        ));
        $ids = array_map('intval', $ids);
        sort($ids);

        return implode(',', $ids);
    }

    /**
     * Ensure selected examiner ids have readable autocomplete labels after form data is loaded.
     *
     * @param \MoodleQuickForm $mform
     * @param \stdClass $data
     * @return void
     */
    private function inject_examiner_selector_labels(\MoodleQuickForm $mform, ?\stdClass $data): void {
        if ($data === null) {
            return;
        }
        $personid = (int)($data->personinchargeid ?? 0);
        if ($personid > 0 && $mform->elementExists('personinchargeid')) {
            $this->merge_examiner_autocomplete_options($mform, 'personinchargeid', [$personid]);
        }

        $otherexaminerids = examiner_pool_resolver::parse_user_id_list(
            isset($data->otherexaminers) ? (string)$data->otherexaminers : null
        );
        if ($otherexaminerids !== [] && $mform->elementExists('otherexaminers')) {
            $this->merge_examiner_autocomplete_options($mform, 'otherexaminers', $otherexaminerids);
        }
    }

    /**
     * Merge resolved labels into an examiner autocomplete element.
     *
     * @param \MoodleQuickForm $mform
     * @param string $fieldname
     * @param int[] $userids
     * @return void
     */
    private function merge_examiner_autocomplete_options(\MoodleQuickForm $mform, string $fieldname, array $userids): void {
        $element = $mform->getElement($fieldname);
        if (!is_object($element) || !method_exists($element, 'addOption')) {
            return;
        }

        foreach (examiner_pool_resolver::build_options_for_user_ids($userids) as $value => $label) {
            $element->addOption($label, (string)$value);
        }
    }

    /**
     * Format one or more selected user ids for readonly selector output.
     *
     * @param mixed $value
     * @param array $options
     * @return string
     */
    private function format_selector_display(mixed $value, array $options): string {
        $ids = examiner_pool_resolver::parse_user_id_list(
            $value === null || $value === '' ? null : (string)$value
        );
        if ($ids === []) {
            return '-';
        }

        $resolvedlabels = examiner_pool_resolver::build_options_for_user_ids($ids);
        $labels = [];

        foreach ($ids as $id) {
            $labels[] = $options[$id] ?? $resolvedlabels[$id] ?? examiner_pool_resolver::format_fallback_label($id);
        }

        return implode(', ', $labels);
    }
}
