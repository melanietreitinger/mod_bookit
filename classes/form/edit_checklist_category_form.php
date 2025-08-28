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
 * Form for creating and editing a checklist category.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\form;

use core_form\dynamic_form;
use mod_bookit\local\entity\bookit_checklist_category;

class edit_checklist_category_form extends dynamic_form {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'masterid');
        $mform->setType('masterid', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'put');

        $mform->addElement('hidden', 'checklistitems');
        $mform->setType('checklistitems', PARAM_TEXT);

        $mform->addElement('text', 'name', get_string('category_name', 'mod_bookit'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

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

        if (!empty($this->_ajaxformdata['masterid'])) {
            $data['masterid'] = $this->_ajaxformdata['masterid'];
        }

        if (!empty($this->_ajaxformdata['id'])) {
            $id = $this->_ajaxformdata['id'];

            try {
                $category = bookit_checklist_category::from_database($id);
                $data['id'] = $category->id;
                $data['masterid'] = $category->masterid;
                $data['name'] = $category->name;
                $data['checklistitems'] = json_encode($this->_ajaxformdata['checklistitems']);

            } catch (\Exception $e) {
                error_log("Error loading checklist category with ID $id: " . $e->getMessage());
            }
        }

        $this->set_data($data);

    }
    public function process_put_request($ajaxdata = []): array {
        global $USER;


        if (!empty($ajaxdata['id'])) {

            $category = bookit_checklist_category::from_database($ajaxdata['id']);
            $category->name = $ajaxdata['name'];
            $category->description = $ajaxdata['description'] ?? '';
            $category->checklistitems = $ajaxdata['checklistitems'] ?? '';
            $category->usermodified = $USER->id;
            $category->timemodified = time();

            $category->save();

            $id = $category->id;
        } else {

            $category = new bookit_checklist_category(
                0,
                $ajaxdata['masterid'],
                $ajaxdata['name'],
                $ajaxdata['description'] ?? '',
                '',
                0,
                $USER->id,
                time(),
                time()
            );

            $id = $category->save();

        }

        return [
            [
                'name' => 'checklistcategories',
                'action' => 'put',
                'fields' => [
                    'id' => $id,
                    'name' => $ajaxdata['name'],
                    'order' => 0,
                    'items' => $ajaxdata['checklistitems'],
                ],
            ],
        ];
    }

    public function process_delete_request($id): array {
        $category = bookit_checklist_category::from_database($id);
        $category->delete();

        return [
            [
                'name' => 'checklistcategories',
                'action' => 'delete',
                'fields' => [
                    'id' => $id,
                ],
            ],
        ];
    }

}