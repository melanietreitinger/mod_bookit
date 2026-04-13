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

/**
 * Form for creating and editing a resource category.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form\resource;

use core_form\dynamic_form;
use context;
use context_system;
use mod_bookit\local\manager\resource_manager;
use moodle_url;

/**
 * Form for creating and editing a resource category.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_resource_category_form extends dynamic_form {
    /**
     * Define the form.
     */
    public function definition(): void {
        $mform =& $this->_form;

        // Hidden field: id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Hidden field: action (put or delete).
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'put');

        // Field: name.
        $mform->addElement('text', 'name', get_string('resources:name', 'mod_bookit'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'server');
        $mform->addRule('name', null, 'maxlength', 255, 'server');
        $mform->addHelpButton('name', 'resources:name', 'mod_bookit');

        // Field: description.
        $mform->addElement('textarea', 'description', get_string('resources:description', 'mod_bookit'), [
            'rows' => 4,
            'cols' => 50,
        ]);
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'resources:description', 'mod_bookit');

        // Field: active is removed; categories are always active.

        // Hidden field: sortorder (set automatically).
        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        // Hidden field: categoryorder (for reordering all categories).
        $mform->addElement('hidden', 'categoryorder');
        $mform->setType('categoryorder', PARAM_TEXT);

        // Hidden field: items (for reordering items within category).
        $mform->addElement('hidden', 'items');
        $mform->setType('items', PARAM_TEXT);

        // Hidden fields for cross-category item move.
        $mform->addElement('hidden', 'targetcategoryid');
        $mform->setType('targetcategoryid', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);
    }

    /**
     * Load in existing data as form defaults.
     *
     * This is called to load the existing data for editing or to set up defaults
     * when creating a new category.
     */
    public function set_data_for_dynamic_submission(): void {
        $id = $this->optional_param('id', null, PARAM_INT);

        if (!empty($id)) {
            // Edit mode: Load existing category.
            $category = resource_manager::get_category($id);
            $data = (object) [
                'id' => $category->get_id(),
                'name' => $category->get_name(),
                'description' => $category->get_description(),
                'sortorder' => $category->get_sortorder(),
            ];
        } else {
            // Create mode: Set defaults.
            $data = (object) [
                'id' => 0,
                'sortorder' => 0,
            ];
        }

        $this->set_data($data);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @throws \moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('mod/bookit:managebasics', context_system::instance());
    }

    /**
     * Process the form submission.
     *
     * This is where the form data is saved to the database.
     *
     * @return array Empty array (required by interface)
     */
    public function process_dynamic_submission(): array {
        $formdata = $this->get_data();

        // Check for delete action.
        if (!empty($formdata->action) && $formdata->action === 'delete') {
            return $this->process_delete_request($formdata->id);
        }

        // Handle category reordering if categoryorder provided.
        if (!empty($formdata->categoryorder)) {
            // Parse comma-separated IDs.
            $categoryids = explode(',', $formdata->categoryorder);
            $sortorder = 1;
            foreach ($categoryids as $catid) {
                if ($catid = clean_param($catid, PARAM_INT)) {
                    resource_manager::update_category_sortorder($catid, $sortorder++);
                }
            }
            return [];
        }

        // Handle item reordering if items provided.
        if (!empty($formdata->items)) {
            // Expecting JSON array.
            $itemids = json_decode($formdata->items, true);
            if (is_array($itemids)) {
                global $DB;
                $sortorder = 1;
                foreach ($itemids as $itemid) {
                    if ($itemid = clean_param($itemid, PARAM_INT)) {
                        $DB->set_field('bookit_resource', 'sortorder', $sortorder++, ['id' => $itemid]);
                    }
                }
                // If item moved to a different category, update its categoryid.
                if (!empty($formdata->itemid) && !empty($formdata->targetcategoryid)) {
                    $itemid = clean_param($formdata->itemid, PARAM_INT);
                    $targetcategoryid = clean_param($formdata->targetcategoryid, PARAM_INT);
                    if ($itemid && $targetcategoryid) {
                        $DB->set_field('bookit_resource', 'categoryid', $targetcategoryid, ['id' => $itemid]);
                    }
                }
            }
            return [];
        }

        // Regular save logic.
        // Create entity from form data.
        $category = new \mod_bookit\local\entity\resource\bookit_resource_category(
            $formdata->id ?: null,
            $formdata->name,
            $formdata->description ?? '',
            $formdata->sortorder ?? 0,
            time(), // Timecreated (will be set by manager if new).
            time()   // Timemodified.
        );

        // Save via manager.
        global $USER;
        $savedid = resource_manager::save_category($category, $USER->id);

        // Return reactive state update format.
        return [
            [
                'name' => 'categories',
                'action' => 'put',
                'fields' => [
                    'id' => $savedid,
                    'name' => $formdata->name,
                    'description' => $formdata->description ?? '',
                    'sortorder' => $formdata->sortorder ?? 0,
                ],
            ],
        ];
    }

    /**
     * Returns context where this form is used.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/mod/bookit/admin/resources.php');
    }

    /**
     * Process DELETE requests for removing resource categories.
     *
     * @param int $id The ID of the resource category to delete
     * @return array Result of the delete operation
     */
    private function process_delete_request(int $id): array {
        $category = resource_manager::get_category($id);
        resource_manager::delete_category($id);

        return [
            [
                'name' => 'categories',
                'action' => 'delete',
                'fields' => [
                    'id' => $id,
                ],
            ],
        ];
    }
}
