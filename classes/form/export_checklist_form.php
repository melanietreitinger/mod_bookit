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
 * Form for exporting a checklist.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\form;

use core_form\dynamic_form;
use mod_bookit\local\manager\sharing_manager;

/**
 * Form class for exporting checklists.
 *
 * This form handles the export of checklist data in various formats
 * through AJAX requests.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_checklist_form extends dynamic_form {
    /**
     * Form definition.
     *
     * This method defines the form elements for checklist export.
     */
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('hidden', 'masterid');
        $mform->setType('masterid', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'export');

        $data = [
            'export_help' => get_string('export_help', 'mod_bookit'),
        ];
        $exportinfo = $OUTPUT->render_from_template('mod_bookit/masterchecklist/bookit_checklist_exportinfo', $data);
        $mform->addElement('static', 'export_info', '', $exportinfo);

        $formats = [];
        $formats[] = $mform->createElement('radio', 'format', '', get_string('csv_format', 'mod_bookit'), 'csv');
        $formats[] = $mform->createElement('radio', 'format', '', get_string('pdf_format', 'mod_bookit'), 'pdf');

        $mform->addGroup($formats, 'format_group', get_string('export_format', 'mod_bookit'), '<br/>', false);
        $mform->addRule('format_group', null, 'required', null, 'client');
        $mform->setDefault('format', 'csv');

        // Add title field for PDF export.
        $mform->addElement('text', 'pdf_title', get_string('pdf_title', 'mod_bookit'), ['size' => 50]);
        $mform->setType('pdf_title', PARAM_TEXT);
        $mform->addHelpButton('pdf_title', 'pdf_title', 'mod_bookit');

        // Hide title field initially (show only when PDF is selected).
        $mform->hideIf('pdf_title', 'format', 'neq', 'pdf');
    }

    /**
     * Check if the current user has access to this form.
     */
    protected function check_access_for_dynamic_submission(): void {
        // Add capability check if needed.
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
        $data = $this->get_data();
        $ajaxdata = $this->_ajaxformdata;

        if (!empty($ajaxdata['masterid']) && !empty($data->format)) {
            $masterid = (int)$ajaxdata['masterid'];

            $exportparams = [
                'masterid' => $masterid,
                'format' => $data->format,
            ];

            // Add PDF title if format is PDF and title is provided.
            if ($data->format === 'pdf' && !empty($data->pdf_title)) {
                $exportparams['title'] = $data->pdf_title;
            }

            $exporturl = new \moodle_url('/mod/bookit/export.php', $exportparams);

            return [
                'success' => true,
                'message' => 'Export ready',
                'downloadurl' => $exporturl->out(false),
            ];
        }

        return ['success' => false, 'message' => 'Missing required data'];
    }

    /**
     * Set data for the form.
     */
    public function set_data_for_dynamic_submission(): void {
        $data = [];

        if (!empty($this->_ajaxformdata['masterid'])) {
            $data['masterid'] = $this->_ajaxformdata['masterid'];

            // Set default PDF title to simple string.
            $data['pdf_title'] = 'Master Checklist';
        }

        $this->set_data($data);
    }
}
