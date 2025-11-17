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
 * Form for editing master checklists.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form\masterchecklist;

use core_form\dynamic_form;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_master;

/**
 * Form class for editing master checklists.
 *
 * This form handles the creation and modification of master checklists
 * through AJAX requests. It supports PUT and DELETE operations.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_checklist_master_form extends dynamic_form {
    /**
     * Form definition.
     *
     * This method defines the form elements.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'put');

        $mform->addElement('hidden', 'mastercategoryorder');
        $mform->setType('mastercategoryorder', PARAM_TEXT);
    }

    /**
     * Check if the current user has access to this form.
     */
    protected function check_access_for_dynamic_submission(): void {
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

        if (!empty($data->action)) {
            switch ($data->action) {
                case 'delete':
                    return $this->process_delete_request($ajaxdata['id']);
                case 'put':
                    return $this->process_put_request($ajaxdata);
                default:
                    return ['success' => false, 'message' => 'Unknown action: ' . $data->action];
            }
        }
    }

    /**
     * Set data for the form.
     */
    public function set_data_for_dynamic_submission(): void {
        $data = [];

        if (!empty($this->_ajaxformdata['id'])) {
            $data['id'] = $this->_ajaxformdata['id'];
        }

        if (!empty($this->_ajaxformdata['id'])) {
            $id = $this->_ajaxformdata['id'];

            try {
                $master = bookit_checklist_master::from_database($id);
                $data['id'] = $master->id;
                $data['mastercategoryorder'] = $master->checklistcategories;
            } catch (\Exception $e) {
                debugging("Error loading checklist master with ID $id: " . $e->getMessage());
            }
        }

        $this->set_data($data);
    }
    /**
     * Processes PUT requests for updating checklist items.
     *
     * @param array $data The request data to process
     * @return bool|int Result of the operation, item ID on success or false on failure
     */
    public function process_put_request($data) {
        $master = bookit_checklist_master::from_database($data['id']);
        $master->mastercategoryorder = $data['mastercategoryorder'];
        $master->save();

        return [
            [
                'name' => 'masterchecklists',
                'action' => 'put',
                'fields' => [
                    'id' => $master->id,
                    'mastercategoryorder' => $master->mastercategoryorder,
                ],
            ],
        ];
    }

    /**
     * Processes DELETE requests for removing checklist items.
     *
     * @param array $data The request data containing the item to delete
     * @return bool True if deletion was successful, false otherwise
     */
    public function process_delete_request($data) {
    }
}
