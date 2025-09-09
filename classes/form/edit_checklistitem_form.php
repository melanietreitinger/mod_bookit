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

namespace mod_bookit\form;

use core_form\dynamic_form;
use mod_bookit\local\entity\bookit_checklist_item;
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
class edit_checklistitem_form extends dynamic_form {
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

        $ajaxdata = $this->_ajaxformdata;
        $categories = [];
        if (!empty($ajaxdata['categories'])) {
            $categories = array_column($ajaxdata['categories'], 'name', 'id');
        }

        $mform->addElement(
            'select',
            'categoryid',
            get_string('checklistcategory', 'mod_bookit'),
            $categories,
            ['style' => 'width:50%;']
        );
        $mform->setType('categoryid', PARAM_INT);
        $mform->addRule('categoryid', null, 'required', null, 'client');

        $allrooms = array_column(checklist_manager::get_bookit_rooms(), 'name', 'id');
        $allroles = array_column(checklist_manager::get_bookit_roles(), 'name', 'id');

        $select = $mform->addElement('select', 'roomids', get_string('rooms', 'mod_bookit'), $allrooms, ['style' => 'width:50%;']);
        $mform->setType('roomids', PARAM_TEXT);
        $mform->addRule('roomids', null, 'required', null, 'client');
        $select->setMultiple(true);

        $mform->addElement('select', 'roleid', get_string('role', 'mod_bookit'), $allroles, ['style' => 'width:50%;']);
        $mform->setType('roleid', PARAM_INT);
        $mform->addRule('roleid', null, 'required', null, 'client');

        $duedateradio = [
            $mform->createElement('radio', 'duedate', '', get_string('noduedate', 'mod_bookit'), 'none'),
            $mform->createElement('radio', 'duedate', '', get_string('beforeexam', 'mod_bookit'), 'before'),
            $mform->createElement('radio', 'duedate', '', get_string('afterexam', 'mod_bookit'), 'after'),
        ];

        $mform->addGroup($duedateradio, 'duedategroup', get_string('duedate', 'mod_bookit'), null, false);
        $mform->setDefault('duedate', 'none');

        $mform->addElement('duration', 'duedaysoffset', get_string('time', 'mod_bookit'), ['units' => [DAYSECS]]);
        $mform->setDefault('duedaysoffset', [
            'number' => 14,
            'timeunit' => DAYSECS,
        ]);
        $mform->hideIf('duedaysoffset', 'duedate', 'eq', 'none');

        $mform->addElement('header', 'notifications', get_string('notifications', 'mod_bookit'));
        $mform->setExpanded('notifications', false);

        foreach (bookit_notification_type::cases() as $case) {
            $mform->addElement('checkbox', strtolower($case->name), get_string(strtolower($case->name), 'mod_bookit'));
            $select = $mform->addElement(
                'select',
                strtolower($case->name) . '_recipient',
                get_string('recipient', 'mod_bookit'),
                $allroles,
                ['style' => 'width:50%;']
            );
            $select->setMultiple(true);
            $mform->hideIf(strtolower($case->name) . '_recipient', strtolower($case->name));

            if (array_search($case, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE]) !== false) {
                $mform->addElement('duration', strtolower($case->name) . '_time', get_string('time', 'mod_bookit'),
                ['units' => [DAYSECS]]);
                $mform->hideIf(strtolower($case->name) . '_time', strtolower($case->name));
            }

            $mform->addElement('editor', strtolower($case->name) . '_messagetext', get_string('customtemplate', 'mod_bookit'));
            $mform->setType(strtolower($case->name) . '_messagetext', PARAM_RAW);
            $mform->setDefault(strtolower($case->name) . '_messagetext', [
                'text'   => get_string('customtemplatedefaultmessage', 'mod_bookit'),
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ]);
            $mform->hideIf(strtolower($case->name) . '_messagetext', strtolower($case->name));

            $mform->addElement('hidden', strtolower($case->name) . '_id');
            $mform->setType(strtolower($case->name) . '_id', PARAM_INT);
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
        $ajaxdata = $this->_ajaxformdata;

        error_log('ARO - AJAXDATA IN SET DATA: ' . print_r($ajaxdata, true));

        $item = new \StdClass();
        $id = $this->optional_param('itemid', null, PARAM_INT);
        $itemslots = [];
        if (!empty($id)) {
            $item = bookit_checklist_item::from_database($id);
            $item->itemid = $item->id;
            $itemslots = bookit_notification_slot::get_slots_for_item($item->id);
        }

        if (!empty($itemslots)) {
            foreach ($itemslots as $slot) {
                if ($slot->isactive == 0) {
                    continue;
                }
                $slottype = bookit_notification_type::tryFrom($slot->type);

                if (array_search($slottype, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE]) !== false) {
                    $item->{$slottype->value . '_time'}['number'] = $slot->duedaysoffset;
                }
                $item->{$slottype->value . '_id'} = $slot->id;
                $item->{$slottype->value} = 1;
                $item->{$slottype->value . '_recipient'} = json_decode($slot->roleids, true);
                $item->{$slottype->value . '_messagetext'}['text'] = $slot->messagetext;
            }
        }

        $this->set_data($item);
    }

    /**
     * Processes PUT requests for updating checklist items.
     *
     * @param array $data The request data to process
     * @return array Result of the operation with updated item data
     */
    public function process_put_request($data) {
        global $USER;

        error_log('ARO - DATA IN PUT REQUEST: ' . print_r($data, true));

        if (!empty($data['itemid'])) {
            $item = bookit_checklist_item::from_database($data['itemid']);

            $fields = [
                    'title' => $data['title'],
                    'order' => 0,
                    'categoryid' => $data['categoryid'],
                    'roomids' => $data['roomids'],
                    'roleid' => $data['roleid'],
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
                $data['roleid'],
                $data['title'],
                $data['description'] ?? '',
                1,
                null,
                0,
                0,
                null,
                0,
                $USER->id,
                time(),
                time()
            );
        }

        $id = $item->save();

        foreach (bookit_notification_type::cases() as $case) {
            $caseName = strtolower($case->name);

            if (!empty($data[$caseName])) {
                $daysoffset = 0;
                if ($case === bookit_notification_type::BEFORE_DUE || $case === bookit_notification_type::OVERDUE) {
                    $daysoffset = $data[$caseName . '_time']['number'] ?? 0;
                }

                if (!empty($data[$caseName . '_id'])) {
                    $slot = bookit_notification_slot::from_database($data[$caseName . '_id']);

                    $slot->roleids = json_encode($data[$caseName . '_recipient'] ?? []);
                    $slot->messagetext = format_text($data[$caseName . '_messagetext']['text'] ?? '', FORMAT_HTML);
                    $slot->duedaysoffset = $daysoffset;

                    $slot->save();
                } else {
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
                        json_encode($data[$caseName . '_recipient'] ?? []),
                        $daysoffset,
                        $duedaysrelation,
                        format_text($data[$caseName . '_messagetext']['text'] ?? '', FORMAT_HTML),
                        1,
                        $USER->id,
                        time(),
                        time()
                    );

                    $slot->save();
                }
            } else if (!empty($data[$caseName . '_id'])) {
                $slot = bookit_notification_slot::from_database($data[$caseName . '_id']);
                $slot->isactive = 0;
                $slot->save();
            }
        }

        if (!isset($fields)) {
            $fields = [
                'id' => $id,
                'title' => $data['title'],
                'order' => 0,
                'category' => $data['categoryid'],
                'roomids' => $data['roomids'],
                'roleid' => $data['roleid'],
            ];
        }

        $fields['id'] = $id;

        foreach ($data['roomids'] as $roomid) {
            $fields['roomnames'][(int) $roomid] = checklist_manager::get_roomname_by_id((int) $roomid);
        }

        $fields['rolename'] = checklist_manager::get_rolename_by_id($fields['roleid']);

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
}
