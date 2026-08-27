<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_bookit\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

use moodleform;

/**
 * Form for the event admin settings.
 *
 * @package     mod_bookit
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_events_form extends moodleform {
    /**
     * Define the form.
     */
    public function definition(): void {
        $mform =& $this->_form;

        $optionalfields = [
            'compensationfordisadvantages' => get_string('event_compensationfordisadvantages', 'mod_bookit'),
            'notes' => get_string('event_notes', 'mod_bookit'),
            'refcourseid' => get_string('event_refcourseid', 'mod_bookit'),
            'coursetemplate' => get_string('select_coursetemplate', 'mod_bookit'),
        ];
        $mform->addElement(
            'select',
            'calendar_optional_fields',
            get_string('calendar_optional_fields', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/calendar_optional_fields</code>',
            $optionalfields,
            ['multiple' => true, 'size' => count($optionalfields)]
        );
        $mform->setType('calendar_optional_fields', PARAM_ALPHANUMEXT);
        $mform->setDefault('calendar_optional_fields', array_keys($optionalfields));
        $mform->addElement(
                'static',
                'calendar_optional_fields_desc',
                '',
                \html_writer::div(get_string('calendar_optional_fields_desc', 'mod_bookit'), 'mb-0')
        );

        $thisyear = (int) date('Y');
        $yearlistmin = array_combine(range($thisyear, $thisyear - 10), range($thisyear, $thisyear - 10));
        $yearlistmax = array_combine(range($thisyear, $thisyear + 10), range($thisyear, $thisyear + 10));

        $mform->addElement(
            'select',
            'eventminyear',
            get_string('settings_eventminyear', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/eventminyear</code>',
            $yearlistmin
        );
        $mform->getElement('eventminyear')->setSelected($thisyear - 1);
        $mform->addElement(
            'static',
            'eventminyear_desc',
            '',
            \html_writer::div(get_string('settings_eventminyear_desc', 'mod_bookit'), 'mb-0')
        );

        $mform->addElement(
            'select',
            'eventmaxyear',
            get_string('settings_eventmaxyear', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/eventmaxyear</code>',
            $yearlistmax
        );
        $mform->getElement('eventmaxyear')->setSelected($thisyear + 1);
        $mform->addElement(
            'static',
            'eventmaxyear_desc',
            '',
            \html_writer::div(get_string('settings_eventmaxyear_desc', 'mod_bookit'), 'mb-0')
        );

        foreach ([
            'semesterlookbackyears' => 0,
            'semesterlookaheadyears' => 2,
        ] as $settingname => $default) {
            $mform->addElement(
                'select',
                $settingname,
                get_string($settingname, 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $settingname . '</code>',
                [0, 1, 2]
            );
            $mform->getElement($settingname)->setSelected($default);
            $mform->addElement(
                'static',
                $settingname . '_desc',
                '',
                \html_writer::div(get_string($settingname . '_desc', 'mod_bookit'), 'mb-0')
            );
        }

        foreach ([
            'eventdefaultduration' => 60,
            'eventmaxduration' => 480,
        ] as $settingname => $default) {
            $mform->addElement(
                'text',
                $settingname,
                get_string('settings_' . $settingname, 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $settingname . '</code>',
                ['size' => 4]
            );
            $mform->setType($settingname, PARAM_INT);
            $mform->getElement($settingname)->setValue($default);
        }

        $steparray = [5 => '5', 10 => '10', 15 => '15', 30 => '30', 60 => '60'];
        foreach (['eventdurationstepwidth', 'eventstartstepwidth'] as $settingname) {
            $mform->addElement(
                'select',
                $settingname,
                get_string('settings_' . $settingname, 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $settingname . '</code>',
                $steparray
            );
        }

        foreach ([
            'extratimebefore' => 'settings_extratime_before',
            'extratimeafter' => 'settings_extratime_after',
        ] as $settingname => $stringname) {
            $mform->addElement(
                'text',
                $settingname,
                get_string($stringname, 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $settingname . '</code>',
                ['size' => 4]
            );
            $mform->setType($settingname, PARAM_INT);
            $mform->getElement($settingname)->setValue(15);
            $mform->addElement(
                'static',
                $settingname . '_desc',
                '',
                \html_writer::div(get_string($stringname . '_desc', 'mod_bookit'), 'mb-0')
            );
        }

        $mform->addElement(
            'textarea',
            'examiner_pool_usernames',
            get_string('examiner_pool_usernames', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/examiner_pool_usernames</code>',
            ['rows' => 8, 'cols' => 80, 'style' => 'margin-bottom: 0 !important;']
        );
        $mform->setType('examiner_pool_usernames', PARAM_RAW_TRIMMED);
        $mform->addElement(
            'static',
            'examiner_pool_usernames_desc',
            '',
            \html_writer::div(get_string('examiner_pool_usernames_desc', 'mod_bookit'), 'mb-0')
        );

        $this->add_action_buttons();
    }
}
