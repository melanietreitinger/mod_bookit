<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_bookit\local\form;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class bookit_form
 *
 * @package    mod_bookit
 * @copyright  @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookit_form extends \moodleform {

    /**
     * Defines the form.
     */
    public function definition() {
        $mform = $this->_form;

        //     // Überschrift (Heading) für die erste Gruppe
        // $mform->addElement('header', 'general', 'General Settings');

        // // Felder für die erste Gruppe erstellen
        // $name = $mform->createElement('text', 'name', 'Event Name', array('size' => '64'));
        // $description = $mform->createElement('editor', 'introeditor', 'Description');

        // // Gruppe mit den Feldern "Name" und "Beschreibung" hinzufügen
        // $mform->addGroup(array($name, $description), 'generalgroup', 'General Information', ' ', false);

        // // Regeln für die Gruppe festlegen
        // $mform->addGroupRule('generalgroup', [
        //     'name' => [
        //         ['required', null, 'required', null, 'client'],
        //         ['maxlength', 255, 'maxlength', 255, 'client']
        //     ]
        // ]);

        // $mform->setType('name', PARAM_TEXT);
        // $mform->setType('introeditor', PARAM_RAW);

        // // Unterüberschrift (Subheading) und eine weitere Gruppe
        // $mform->addElement('html', '<h3>Additional Settings</h3>');

        // // Felder für die zweite Gruppe erstellen
        // $numcards = $mform->createElement('text', 'numcards', 'Number of Cards', array('size' => '4'));
        // $cardtype = $mform->createElement('select', 'cardtype', 'Card Type', [
        //     'tshirtsizes' => 'T-Shirt Sizes',
        //     'fibonacci' => 'Fibonacci Numbers',
        //     'numbers' => 'Normal Numbers',
        // ]);

        // // Gruppe mit den Feldern "Numcards" und "Cardtype" hinzufügen
        // $mform->addGroup(array($numcards, $cardtype), 'additionalgroup', 'Additional Information', ' ', false);

        // // Typ und Standardwerte für die Felder festlegen
        // $mform->setType('numcards', PARAM_INT);
        // $mform->setDefault('numcards', 6);

        // =====================================

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
        for ($i = 0; $i < 4; $i++) {

            $rooms[$i] = get_string('event_room', 'mod_bookit') . " " . ($i);
        }
        $mform->addElement('select', 'room', get_string('event_room', 'mod_bookit'), $rooms);
        $mform->addHelpButton('room', 'event_room', 'mod_bookit');

        // Add the "Bookingtimes" fields.
        $mform->addElement('date_time_selector', 'event_start', get_string('event_start', 'mod_bookit'));
        $mform->addRule('event_start', null, 'required', null, 'client');
        $mform->addHelpButton('event_start', 'event_start', 'mod_bookit');

        $mform->addElement('date_time_selector', 'event_end', get_string('event_end', 'mod_bookit'));
        $mform->addRule('event_end', null, 'required', null, 'client');
        $mform->addHelpButton('event_end', 'event_end', 'mod_bookit');

        // Add the "duration" field.
        $mform->addElement('text', 'duration', get_string('event_duration', 'mod_bookit'), ['size' => '4']);
        $mform->setType('duration', PARAM_INT);
        $mform->addRule('duration', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('duration', 'event_duration', 'mod_bookit');

        // Add "amount of students" field.
        $mform->addElement('text', 'students', get_string('event_students', 'mod_bookit'), array('size' => '4'));
        $mform->setType('students', PARAM_INT);
        $mform->addHelpButton('students', 'event_students', 'mod_bookit');

        // Add the "person in charge" field.
        $mform->addElement('text', 'person', get_string('event_person', 'mod_bookit'), array('size' => '64'));
        $mform->setType('person', PARAM_TEXT);
        $mform->addRule('person', null, 'required', null, 'client');
        $mform->addRule('person', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('person', 'event_person', 'mod_bookit');

        // Add the "email" field.
        $mform->addElement('text', 'email', get_string('event_email', 'mod_bookit'), array('size' => '64'));
        $mform->setType('email', PARAM_TEXT);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', null, 'maxlength', 255, 'client');
        $mform->addHelpButton('email', 'event_email', 'mod_bookit');

        // Add the "compensationfordisadvantages" field.
        $mform->addElement('textarea', 'compensationfordisadvantages', get_string('event_compensationfordisadvantages', 'mod_bookit'), ['size' => '64']);
        $mform->setType('compensationfordisadvantages', PARAM_TEXT);
        $mform->addHelpButton('compensationfordisadvantages', 'event_compensationfordisadvantages', 'mod_bookit');

        $mform->addElement('textarea', 'notes', get_string("event_notes", "mod_bookit"), 'wrap="virtual" rows="20" cols="50"');
        $mform->addHelpButton('notes', 'event_notes', 'mod_bookit');

        // RESSOURCES.
        // $mform->addElement('header', 'resources', get_string('event_resources', 'mod_bookit'));
        // $mform->setExpanded('resources', true);

        $resources = [
                'Hardware' => ['microphone', 'headphone'],
                'Software' => ['safe exam browser', 'chrome'],
                'Licenses' => ['license1', 'license'],
        ];
        //$resourcedetails = [];

        // '$resources = [
        //     'category 1' => [],
        // ];'

        foreach($resources as $key => $value){
            $mform->addElement('header', $key, $key);
            $mform->setExpanded($key, true);

            foreach($value as $v) {
                $preprocedure = [];
                $preprocedure[] =  $mform->createElement('advcheckbox', 'preprocedure[]','', $v, ['group' => 1], ['',$v]);
                $preprocedure[] =  $mform->createElement('text', 'amount', get_string('resource_amount', 'mod_bookit'), array('size' => '4'));
                $mform->setType('amount', PARAM_INT);
                $mform->addGroup($preprocedure, 'preproceduregroup', 'Please select', ['<br>'], false);
            }
        }

        $this->add_action_buttons(true, get_string('save'));
    }
}
