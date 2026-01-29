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
 * Form for creating and editing a blocker.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann RUB
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form;

use core\context;
use core_form\dynamic_form;
use mod_bookit\local\persistent\blocker;
use mod_bookit\local\persistent\room;
use moodle_url;

/**
 * Form for creating and editing a blocker.
 *
 * @package     mod_bookit
 * @copyright   2025 Justus Dieckmann RUB
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_blocker_form extends dynamic_form {
    /**
     * Define the form
     */
    public function definition(): void {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);

        $rooms = room::get_records();
        $options = [0 => get_string('globally', 'mod_bookit')];
        foreach ($rooms as $room) {
            $options[$room->get('id')] = $room->get('name');
        }
        $mform->addElement('select', 'roomid', get_string('room', 'mod_bookit'), $options);

        $mform->addElement('date_time_selector', 'starttime', get_string('start', 'mod_bookit'));
        $mform->setType('starttime', PARAM_TEXT);

        $mform->addElement('date_time_selector', 'endtime', get_string('end', 'mod_bookit'));
        $mform->setType('endtime', PARAM_TEXT);
    }

    /**
     * Extra validation.
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        if ($data['endtime'] < $data['starttime']) {
            $errors['endtime'] = get_string('end_before_start', 'mod_bookit');
        }
        return $errors;
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        $id = $this->optional_param('id', null, PARAM_INT);
        $blocker = new blocker($id);
        if ($id) {
            $this->set_data([
                'id' => $blocker->get('id'),
                'name' => $blocker->get('name'),
                'roomid' => $blocker->get('roomid'),
                'starttime' => $blocker->get('starttime'),
                'endtime' => $blocker->get('endtime'),
            ]);
        } else {
            $startdate = $this->optional_param('startdate', null, PARAM_TEXT);
            if ($startdate) {
                $this->set_data([
                    'starttime' => strtotime($startdate),
                    'endtime' => strtotime($startdate) + 60 * 60,
                ]);
            }
        }
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return \context_system::instance();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        require_capability('mod/bookit:managemasterchecklist', $context); // XXX TODO: use other capability.
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return array empty.
     */
    public function process_dynamic_submission(): array {
        if ($formdata = $this->get_data()) {
            // Set roomid to null if it is falsy (0).
            $formdata->roomid = $formdata->roomid ?: null;

            $blocker = new blocker($formdata->id ?? 0, $formdata);
            $blocker->save();
        }

        return [];
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $params = [
                'id' => $this->optional_param('id', null, PARAM_INT),
                'cmid' => $this->optional_param('cmid', null, PARAM_INT),
        ];
        return new moodle_url('/mod/bookit/view.php', $params);
    }
}
