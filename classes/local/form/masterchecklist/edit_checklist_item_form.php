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
 * Form for creating and editing a checklist item.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form\masterchecklist;

use core_form\dynamic_form;
use mod_bookit\local\entity\bookit_checklist_item;
use mod_bookit\local\entity\bookit_checklist_category;
use mod_bookit\local\manager\checklist_manager;
use mod_bookit\local\entity\bookit_notification_slot;
use mod_bookit\local\entity\bookit_notification_type;

/**
 * Form class for editing checklist items.
 *
 * This form handles the creation and modification of checklist items
 * through AJAX requests. It supports PUT and DELETE operations.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_checklist_item_form extends dynamic_form {
    /**
     * Convert form field name to object property name.
     *
     * Converts snake_case form field names (e.g., 'before_due') to camelCase property names (e.g., 'beforedue').
     *
     * @param string $fieldname The form field name
     * @param string $suffix Optional suffix to append (e.g., 'time', 'messagetext', 'recipient', 'id')
     * @return string The object property name
     */
    private function get_property_name(string $fieldname, string $suffix = ''): string {
        // Remove underscores to convert to camelCase.
        $propertyname = str_replace('_', '', $fieldname);

        if ($suffix) {
            $propertyname .= $suffix;
        }

        return $propertyname;
    }

    /**
     * Form definition.
     *
     * This method defines the form elements for checklist item editing.
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'masterid');
        $mform->setType('masterid', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'put');

        $mform->addElement('textarea', 'title', get_string('checklistitemname', 'mod_bookit'), ['style' => 'width:50%;']);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addHelpButton('title', 'checklistitemname', 'mod_bookit');

        $mform->addElement(
            'select',
            'categoryid',
            get_string('checklistcategory', 'mod_bookit'),
            null,
            ['style' => 'width:50%;']
        );
        $mform->setType('categoryid', PARAM_INT);
        $mform->addRule('categoryid', null, 'required', null, 'client');
        $mform->addHelpButton('categoryid', 'checklistcategory', 'mod_bookit');

        $allrooms = array_column(checklist_manager::get_bookit_rooms(), 'name', 'id');
        $allroles = array_column(checklist_manager::get_bookit_roles(), 'name', 'id');

        $select = $mform->addElement('select', 'roomids', get_string('rooms', 'mod_bookit'), $allrooms, [
            'style' => 'width:50%; max-height:150px;',
            'size' => '4',
        ]);
        $mform->setType('roomids', PARAM_TEXT);
        $mform->addRule('roomids', null, 'required', null, 'client');
        $select->setMultiple(true);
        $mform->addHelpButton('roomids', 'rooms', 'mod_bookit');

        $select = $mform->addElement('select', 'roleids', get_string('role', 'mod_bookit'), $allroles, [
            'style' => 'width:50%; max-height:150px;',
            'size' => '4',
        ]);
        $mform->setType('roleids', PARAM_TEXT);
        $mform->addRule('roleids', null, 'required', null, 'client');
        $select->setMultiple(true);
        $mform->addHelpButton('roleids', 'role', 'mod_bookit');

        $duedateradio = [
            $mform->createElement('radio', 'duedate', '', get_string('noduedate', 'mod_bookit'), 'none'),
            $mform->createElement('radio', 'duedate', '', get_string('beforeexam', 'mod_bookit'), 'before'),
            $mform->createElement('radio', 'duedate', '', get_string('afterexam', 'mod_bookit'), 'after'),
        ];

        $mform->addGroup($duedateradio, 'duedategroup', get_string('duedate', 'mod_bookit'), null, false);
        $mform->setDefault('duedate', 'none');
        $mform->addRule('duedategroup', null, 'required', null, 'client');
        $mform->addHelpButton('duedategroup', 'duedate', 'mod_bookit');

        $mform->addElement('duration', 'duedaysoffset', get_string('time', 'mod_bookit'), ['units' => [DAYSECS]]);
        $mform->setDefault('duedaysoffset', [
            'number' => 14,
            'timeunit' => DAYSECS,
        ]);
        $mform->hideIf('duedaysoffset', 'duedate', 'eq', 'none');
        $mform->addHelpButton('duedaysoffset', 'time', 'mod_bookit');

        $mform->addElement('header', 'notifications', get_string('notifications', 'mod_bookit'));
        $mform->setExpanded('notifications', false);

        foreach (bookit_notification_type::cases() as $case) {
            $mform->addElement('html', '<hr/>');

            $mform->addElement('checkbox', $case->value, get_string($case->value, 'mod_bookit'));
            $select = $mform->addElement(
                'select',
                $case->value . '_recipient',
                get_string('recipient', 'mod_bookit'),
                $allroles,
                ['style' => 'width:50%;']
            );
            $select->setMultiple(true);
            $mform->addHelpButton($case->value . '_recipient', 'recipient', 'mod_bookit');
            $mform->hideIf($case->value . '_recipient', $case->value);

            if (array_search($case, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE]) !== false) {
                $mform->addElement(
                    'duration',
                    $case->value . '_time',
                    get_string('notification_time', 'mod_bookit'),
                    ['units' => [DAYSECS]]
                );
                $mform->setDefault($case->value . '_time', [
                    'number' => 1,
                    'timeunit' => DAYSECS,
                ]);
                $mform->hideIf($case->value . '_time', $case->value);
                $mform->addHelpButton($case->value . '_time', 'notification_time', 'mod_bookit');
            }

            $mform->addElement('editor', $case->value . '_messagetext', get_string('customtemplate', 'mod_bookit'));
            $mform->setType($case->value . '_messagetext', PARAM_RAW);

            $defaultmessagekey = 'customtemplatedefaultmessage_' . $case->value;
            $defaultmessage = get_string($defaultmessagekey, 'mod_bookit');

            $mform->setDefault($case->value . '_messagetext', [
                'text'   => $defaultmessage,
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ]);
            $mform->hideIf($case->value . '_messagetext', $case->value);
            $mform->addHelpButton($case->value . '_messagetext', 'customtemplate', 'mod_bookit');

            $mform->addElement('button', $case->value . '_reset', get_string('reset', 'mod_bookit'));
            $mform->hideIf($case->value . '_reset', $case->value);

            $mform->addElement('hidden', $case->value . '_id');
            $mform->setType($case->value . '_id', PARAM_INT);
        }
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

        if (empty($data->categoryid)) {
            $data->categoryid = $ajaxdata['categoryid'] ?? null;
        }

        if (!empty($data->action)) {
            switch ($data->action) {
                case 'delete':
                    return $this->process_delete_request($ajaxdata['itemid']);
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
        $item = new \StdClass();
        $id = $this->optional_param('itemid', null, PARAM_INT);
        $itemslots = [];
        if (!empty($id)) {
            $item = bookit_checklist_item::from_database($id);
            $item->itemid = $item->id;
            $itemslots = bookit_notification_slot::get_slots_for_item($item->id);
        }

        $checklistcategories = checklist_manager::get_categories_by_master_id($item->masterid ?? null);

        $options = array_column($checklistcategories, 'name', 'id');
        $this->_form->getElement('categoryid')->loadArray($options);

        $hasactiveslots = false;

        if (!empty($itemslots)) {
            foreach ($itemslots as $slot) {
                if ($slot->isactive == 1 && $hasactiveslots == false) {
                    $hasactiveslots = true;
                }
                $slottype = bookit_notification_type::tryFrom($slot->type);

                if (array_search($slottype, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE]) !== false) {
                    $timeprop = $this->get_property_name($slottype->value, 'time');
                    $item->{$timeprop}['number'] = $slot->duedaysoffset;
                    $item->{$timeprop}['timeunit'] = DAYSECS;
                }
                $idprop = $this->get_property_name($slottype->value, 'id');
                $item->{$idprop} = $slot->id;

                $mainprop = $this->get_property_name($slottype->value);
                $item->{$mainprop} = $slot->isactive;

                $recipientprop = $this->get_property_name($slottype->value, 'recipient');
                $item->{$recipientprop} = json_decode($slot->roleids, true);

                $messageprop = $this->get_property_name($slottype->value, 'messagetext');
                $item->{$messageprop}['text'] = $slot->messagetext;
            }

            if ($hasactiveslots) {
                $this->_form->setExpanded('notifications', true);
            }
        }

        $item->duedate = $item->duedaysrelation ?? 'none';

        // Transform object property names to form field names.
        $formdata = clone $item;
        foreach (bookit_notification_type::cases() as $case) {
            $fieldname = $case->value; // E.g., 'before_due'.
            $propname = $this->get_property_name($fieldname); // E.g., 'beforedue'.

            // Map camelCase properties to underscore form fields.
            if (isset($item->{$propname})) {
                $formdata->{$fieldname} = $item->{$propname};
            }

            $suffixes = ['time', 'messagetext', 'recipient', 'id'];
            foreach ($suffixes as $suffix) {
                $fieldnamewithsuffix = $fieldname . '_' . $suffix;
                $propnamewithsuffix = $this->get_property_name($fieldname, $suffix);

                if (isset($item->{$propnamewithsuffix})) {
                    $formdata->{$fieldnamewithsuffix} = $item->{$propnamewithsuffix};
                }
            }
        }

        $this->set_data($formdata);
    }

    /**
     * Processes PUT requests for updating checklist items.
     *
     * @param array $data The request data to process
     * @return array Result of the operation with updated item data
     */
    public function process_put_request($data) {
        global $USER;

        if (!empty($data['itemid'])) {
            $item = bookit_checklist_item::from_database($data['itemid']);

            $duedaysoffset = 0;
            if (isset($data['duedaysoffset'])) {
                if (is_array($data['duedaysoffset']) && isset($data['duedaysoffset']['number'])) {
                    $duedaysoffset = (int)$data['duedaysoffset']['number'];
                } else if (is_numeric($data['duedaysoffset'])) {
                    $duedaysoffset = (int)$data['duedaysoffset'];
                }
            }

            $fields = [
                    'title' => $data['title'],
                    'order' => 0,
                    'categoryid' => $data['categoryid'],
                    'roomids' => $data['roomids'],
                    'roleids' => $data['roleids'],
                    'duedaysrelation' => $data['duedate'],
                    'duedaysoffset' => $duedaysoffset,
            ];

            foreach ($fields as $key => $value) {
                if (isset($item->$key) && $item->$key === $value) {
                    unset($fields[$key]);
                } else {
                    $item->$key = $value;
                }
            }

            $item->usermodified = $USER->id;
            $item->timemodified = time();
            $item->itemid = $item->id;
        } else {
            $item = new bookit_checklist_item(
                0,
                1,
                $data['categoryid'],
                null,
                $data['roomids'],
                $data['roleids'],
                $data['title'],
                $data['description'] ?? '',
                1,
                null,
                0,
                0,
                null,
                0,
                null,
                $USER->id,
                time(),
                time()
            );
        }

        $id = $item->save();

        if (!empty($data['categoryid'])) {
            $category = bookit_checklist_category::from_database($data['categoryid']);

            $existingitems = [];
            if (!empty($category->checklistitems)) {
                $existingitems = array_map('intval', explode(',', trim($category->checklistitems, '"[]')));
            }

            if (!in_array($id, $existingitems)) {
                $existingitems[] = $id;
                $category->checklistitems = implode(',', $existingitems);
                $category->save();
            }
        }

        foreach (bookit_notification_type::cases() as $case) {
            $casename = strtolower($case->name);

            if (!empty($data[$casename])) {
                $daysoffset = 0;
                if ($case === bookit_notification_type::BEFORE_DUE || $case === bookit_notification_type::OVERDUE) {
                    $daysoffset = $data[$casename . '_time']['number'] ?? 0;
                }

                if (!empty($data[$casename . '_id'])) {
                    $slot = bookit_notification_slot::from_database($data[$casename . '_id']);

                    $slot->roleids = json_encode($data[$casename . '_recipient'] ?? []);
                    $slot->messagetext = format_text($data[$casename . '_messagetext']['text'] ?? '', FORMAT_HTML);
                    $slot->duedaysoffset = $daysoffset;

                    $slot->save();
                } else {
                    // Check if there already is an existing record (active or inactive) before creating a new one.
                    $existingslot = bookit_notification_slot::get_slot_by_item_and_type($id, $case->value);

                    if ($existingslot) {
                        // Reactivate and update existing slot.
                        $existingslot->roleids = json_encode($data[$casename . '_recipient'] ?? []);
                        $existingslot->messagetext = format_text($data[$casename . '_messagetext']['text'] ?? '', FORMAT_HTML);
                        $existingslot->duedaysoffset = $daysoffset;
                        $existingslot->isactive = 1;
                        $existingslot->save();
                    } else {
                        // Create new slot.
                        $duedaysrelation = null;
                        if ($case === bookit_notification_type::BEFORE_DUE) {
                            $duedaysrelation = 'before';
                        } else if ($case === bookit_notification_type::OVERDUE) {
                            $duedaysrelation = 'after';
                        }

                        $slot = new bookit_notification_slot(
                            0,
                            $id,
                            $case->value,
                            json_encode($data[$casename . '_recipient'] ?? []),
                            $daysoffset,
                            $duedaysrelation,
                            format_text($data[$casename . '_messagetext']['text'] ?? '', FORMAT_HTML),
                            1,
                            $USER->id,
                            time(),
                            time()
                        );

                        $slot->save();
                    }
                }
            } else if (!empty($data[$casename . '_id'])) {
                $slot = bookit_notification_slot::from_database($data[$casename . '_id']);
                $slot->isactive = 0;
                $slot->save();
            }
        }

        if (!isset($fields)) {
            $duedaysoffset = 0;
            if (isset($data['duedaysoffset']['number'])) {
                $duedaysoffset = $data['duedaysoffset']['number'];
            } else if (isset($data['duedaysoffset']) && is_numeric($data['duedaysoffset'])) {
                $duedaysoffset = (int)$data['duedaysoffset'];
            }

            $fields = [
                'id' => $id,
                'title' => $data['title'],
                'order' => 0,
                'category' => $data['categoryid'],
                'roomids' => $data['roomids'],
                'roleids' => $data['roleids'],
                'duedaysrelation' => $data['duedate'],
                'duedaysoffset' => $duedaysoffset,
            ];
        }

        $fields['id'] = $id;
        $fields['roomnames'] = [];
        foreach ($data['roomids'] as $roomid) {
            $room = checklist_manager::get_room_by_id((int) $roomid);
            array_push($fields['roomnames'], [
                'roomid' => (int) $roomid,
                'roomname' => $room->name,
                'eventcolor' => $room->eventcolor,
                'textclass' => $room->textclass,
            ]);
        }

        $fields['rolenames'] = [];
        foreach ($data['roleids'] as $roleid) {
            if (checklist_manager::user_has_bookit_role((int) $roleid)) {
                $extraclasses = 'badge badge-warning text-dark';
            } else {
                $extraclasses = 'badge badge-primary text-light';
            }
            $fields['rolenames'][] = [
                'roleid' => (int) $roleid,
                'rolename' => checklist_manager::get_rolename_by_id((int) $roleid),
                'extraclasses' => $extraclasses,
            ];
        }

        return [
            [
                'name' => 'checklistitems',
                'action' => 'put',
                'fields' => $fields,
            ],
        ];
    }

    /**
     * Processes DELETE requests for removing checklist items.
     *
     * @param int $id The ID of the checklist item to delete
     * @return array Result of the delete operation
     */
    public function process_delete_request($id) {

        $item = bookit_checklist_item::from_database($id);
        $categoryid = $item->categoryid;

        if (!empty($categoryid)) {
            $category = bookit_checklist_category::from_database($categoryid);

            $existingitems = [];
            if (!empty($category->checklistitems)) {
                $existingitems = array_map('intval', explode(',', trim($category->checklistitems, '"[]')));
            }

            $updateditems = array_filter($existingitems, fn($itemid) => $itemid !== (int) $id);

            $updateditems = array_values($updateditems);

            $category->checklistitems = empty($updateditems) ? '' : implode(',', $updateditems);
            $category->save();
        }

        $item->delete();
        return [
            [
                'name' => 'checklistitems',
                'action' => 'delete',
                'fields' => [
                    'id' => $id,
                ],
            ],
        ];
    }

    /**
     * Validate form data.
     *
     * @param array $data Form data
     * @param array $files Form files
     * @return array Array of validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['duedate']) && $data['duedate'] != 'none') {
            if (isset($data['duedaysoffset']) && $data['duedaysoffset'] <= 0) {
                $errors['duedaysoffset'] = get_string('err_numeric', 'form');
            }
        }

        foreach (bookit_notification_type::cases() as $case) {
            if (!empty($data[$case->value])) {
                // Checkbox enabled → validate its fields.
                if (empty($data[$case->value . '_recipient'])) {
                    $errors[$case->value . '_recipient'] = get_string('required');
                }

                if (in_array($case, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE])) {
                    if (empty($data[$case->value . '_time'])) {
                        $errors[$case->value . '_time'] = get_string('required');
                    }
                }

                if (empty($data[$case->value . '_messagetext']['text'])) {
                    $errors[$case->value . '_messagetext'] = get_string('required');
                }
            }
        }

        return $errors;
    }

    /**
     * Called after the form definition and after data has been set.
     * This is the right place to make adjustments to form data before validation.
     */
    public function definition_after_data() {
        parent::definition_after_data();

        // Fix main duedaysoffset field.
        $this->fix_duration_field('duedaysoffset');

        // Fix notification time fields.
        foreach (bookit_notification_type::cases() as $case) {
            if (in_array($case, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE])) {
                $fieldname = $case->value . '_time';
                $this->fix_duration_field($fieldname);
            }
        }
    }

    /**
     * Fix a duration field to ensure it has the correct array format
     *
     * @param string $fieldname The name of the field to fix
     */
    private function fix_duration_field($fieldname) {
        if (!$this->_form->elementExists($fieldname)) {
            return;
        }

        $element = $this->_form->getElement($fieldname);
        $currentvalue = $element->getValue();

        $duration = [
            'number' => 0,
            'timeunit' => DAYSECS,
        ];

        if (is_array($currentvalue)) {
            $duration['number'] = isset($currentvalue['number']) ? (int)$currentvalue['number'] : 0;
        } else if (is_numeric($currentvalue)) {
            $duration['number'] = (int)$currentvalue;
        }

        $element->setValue($duration);
    }
}
