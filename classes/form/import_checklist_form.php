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
 * Form for importing a checklist.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\form;

require_once($CFG->dirroot.'/repository/lib.php');

use core_form\dynamic_form;
use mod_bookit\local\manager\sharing_manager;

/**
 * Form class for importing checklists.
 *
 * This form handles the import of checklist data from CSV files
 * through AJAX requests.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_checklist_form extends dynamic_form {
    /**
     * Form definition.
     *
     * This method defines the form elements for checklist import.
     */
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('hidden', 'masterid');
        $mform->setType('masterid', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'import');

        // Add info box with help text
        $data = [
            'import_help' => get_string('import_help', 'mod_bookit')
        ];
        $importinfo = $OUTPUT->render_from_template('mod_bookit/masterchecklist/bookit_checklist_importinfo', $data);
        $mform->addElement('static', 'import_info', '', $importinfo);

        // Add file picker for CSV upload
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'mod_bookit'), null,
            [
                // 'maxbytes' => 1024 * 1024, // 1MB max file size
                'accepted_types' => ['*.csv'],
                'return_types' => FILE_INTERNAL
            ]
        );
        $mform->addRule('csvfile', get_string('required'), 'required', null, 'client');

        // Add checkbox to control room imports
        $mform->addElement('checkbox', 'import_rooms', get_string('import_rooms', 'mod_bookit'),
            get_string('import_rooms_desc', 'mod_bookit'));
        $mform->setType('import_rooms', PARAM_BOOL);
        $mform->setDefault('import_rooms', 1); // Default to checked
    }

    /**
     * Check if the current user has access to this form.
     */
    protected function check_access_for_dynamic_submission(): void {
        // Add capability check if needed
    }

    /**
     * Get the context for this form.
     *
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        if (!empty($this->_ajaxformdata['cmid'])) {
            $cmid = $this->_ajaxformdata['cmid'];
            return \context_module::instance($cmid);
        }
        return \context_system::instance();
    }

    /**
     * Get the URL to return to after form submission.
     *
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/mod/bookit/master_checklist.php');
    }

    /**
     * Process the form submission.
     *
     * @return mixed
     */
    public function process_dynamic_submission() {
        global $USER;

        $data = $this->get_data();
        $ajaxdata = $this->_ajaxformdata;

        if (!empty($ajaxdata['masterid']) && !empty($data->csvfile)) {
            $masterid = (int)$ajaxdata['masterid'];

            // Debug logging
            error_log("Import form: masterid = " . $masterid);

            // Use user context for file storage (like contentbank does)
            $usercontext = \context_user::instance($USER->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->csvfile, 'itemid, filepath, filename', false);

            if (empty($files)) {
                return ['success' => false, 'message' => get_string('nofileselected', 'mod_bookit')];
            }

            $file = reset($files);
            $csvdata = $file->get_content();

            // Process the CSV import
            $sharingmanager = new sharing_manager();
            $importrooms = !empty($data->import_rooms);
            $result = $sharingmanager->import_master_checklist_csv($masterid, $csvdata, $importrooms);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => get_string('importsuccessful', 'mod_bookit', $result['imported']),
                    'reload' => true
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message']
                ];
            }
        }

        return ['success' => false, 'message' => get_string('missingdata', 'mod_bookit')];
    }

    /**
     * Set data for the form.
     */
    public function set_data_for_dynamic_submission(): void {
        $data = [];

        if (!empty($this->_ajaxformdata['masterid'])) {
            $data['masterid'] = $this->_ajaxformdata['masterid'];
        }

        $this->set_data($data);
    }
}