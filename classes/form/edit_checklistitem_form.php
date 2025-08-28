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
use mod_bookit\local\entity\bookit_notification_slot;
use mod_bookit\local\manager\checklist_manager;
use mod_bookit\local\entity\bookit_checklist_item;

class edit_checklistitem_form extends dynamic_form {

    public function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'masterid');
        $mform->setType('masterid', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'put');

        $mform->addElement('textarea', 'title', get_string('checklistitemname', 'mod_bookit'),  ['style'=>'width:50%;']);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $ajaxdata = $this->_ajaxformdata;

        $categories = [];

        if (!empty($ajaxdata['categories'])) {
            $categories = array_column($ajaxdata['categories'], 'name', 'id');
        }

        $mform->addElement('select', 'categoryid', get_string('checklistcategory', 'mod_bookit'), $categories, ['style'=>'width:50%;']);
        $mform->setType('categoryid', PARAM_INT);
        $mform->addRule('categoryid', null, 'required', null, 'client');


        $allrooms = array_column(checklist_manager::get_bookit_rooms(), 'name', 'id');

        $mform->addElement('select', 'roomid', get_string('room', 'mod_bookit'), $allrooms, ['style'=>'width:50%;']);
        $mform->setType('roomid', PARAM_INT);
        $mform->addRule('roomid', null, 'required', null, 'client');

        $allroles = array_column(checklist_manager::get_bookit_roles(), 'name', 'id');

        $mform->addElement('select', 'roleid', get_string('role', 'mod_bookit'), $allroles, ['style'=>'width:50%;']);
        $mform->setType('roleid', PARAM_INT);
        $mform->addRule('roleid', null, 'required', null, 'client');

        $alltypes = bookit_notification_slot::get_all_notification_slot_types();

        foreach ($alltypes as $slottype => $val) {

            $mform->addElement('checkbox', strtolower($slottype), get_string(strtolower($slottype), 'mod_bookit'));

            $select = $mform->addElement('select',
                    strtolower($slottype) . '_recipient',
                    get_string('recipient', 'mod_bookit'),
                    $allroles,
                    ['style'=>'width:50%;']);
            $select->setMultiple(true);
            $mform->hideIf(strtolower($slottype) . '_recipient', strtolower($slottype));

            if (array_search($val, [0,2]) !== false) {
                $mform->addElement('duration', strtolower($slottype) . '_time', get_string('time', 'mod_bookit'));
                $mform->hideIf(strtolower($slottype) . '_time', strtolower($slottype));
            }

            $mform->addElement('editor', strtolower($slottype) . '_messagetext', get_string('customtemplate', 'mod_bookit'));

            $mform->setType(strtolower($slottype) . '_messagetext', PARAM_RAW);
            $mform->setDefault(strtolower($slottype) . '_messagetext', [
                'text'   => get_string('customtemplatedefaultmessage', 'mod_bookit'),
                'format' => FORMAT_HTML,
                'itemid' => 0
            ]);
            $mform->hideIf(strtolower($slottype) . '_messagetext', strtolower($slottype));

            $mform->addElement('hidden', strtolower($slottype) . '_id');
            $mform->setType(strtolower($slottype) . '_id', PARAM_INT);

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

        $item = new \StdClass;
        $id = $this->optional_param('itemid', null, PARAM_INT);
        $itemslots = [];
        if (!empty($id)) {
            $item = bookit_checklist_item::from_database($id);
            $item->itemid = $item->id;
            $itemslots = bookit_notification_slot::get_slots_for_item($item->id);
        } else {

        }

        if (empty($itemslots)) {

        } else {
            $alltypes = bookit_notification_slot::get_all_notification_slot_types();

            foreach ($itemslots as $slot) {
                if ($slot->isactive == 0) {
                    continue;
                }
                $slottype = array_search($slot->type, $alltypes);

                if (array_search($alltypes[$slottype], [0,2]) !== false) {
                    $item->{strtolower($slottype) . '_time'}['number'] = $slot->duedaysoffset;
                }
                $item->{strtolower($slottype) . '_id'} = $slot->id;
                $item->{strtolower($slottype)} = 1;
                $item->{strtolower($slottype) . '_recipient'} = explode(',', $slot->roleids);
                $item->{strtolower($slottype) . '_messagetext'}['text'] = $slot->messagetext;

            }
        }

        $this->set_data($item);
    }

    public function process_put_request($ajaxdata = []): array {
        global $USER;

        if (!empty($ajaxdata['itemid'])) {
            $item = bookit_checklist_item::from_database($ajaxdata['itemid']);

            $fields =  [
                    'title' => $ajaxdata['title'],
                    'order' => 0,
                    'categoryid' => $ajaxdata['categoryid'],
                    'roomid' => $ajaxdata['roomid'],
                    'roleid' => $ajaxdata['roleid'],
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
                    $ajaxdata['categoryid'],
                    null, // parentid
                    $ajaxdata['roomid'],
                    $ajaxdata['roleid'],
                    $ajaxdata['title'],
                    $ajaxdata['description'] ?? '',
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

        $alltypes = bookit_notification_slot::get_all_notification_slot_types();

        foreach ($alltypes as $slottype => $val) {
            if (!empty($ajaxdata[strtolower($slottype)])) {

                $daysoffset = array_search($val, [0,2]) !== false ? $ajaxdata[strtolower($slottype) . '_time']['number'] : 0;
                if (!empty($ajaxdata[strtolower($slottype) . '_id'])) {

                    $slot = bookit_notification_slot::from_database($ajaxdata[strtolower($slottype) . '_id']);
                    $slot->roleids = implode(',', $ajaxdata[strtolower($slottype) . '_recipient'] ?? []);
                    $slot->messagetext = format_text($ajaxdata[strtolower($slottype) . '_messagetext']['text'] ?? '', FORMAT_HTML);
                    $slot->duedaysoffset = $daysoffset;

                    $slot->save();

                } else {

                    $slot = new bookit_notification_slot(
                        0,
                        $id,
                        $val,
                        implode(',', $ajaxdata[strtolower($slottype) . '_recipient'] ?? []),
                        $daysoffset,
                        $val == 0 ? 'before' : ($val == 2 ? 'after' : 0),
                        1,
                        format_text($ajaxdata[strtolower($slottype) . '_messagetext']['text'] ?? '', FORMAT_HTML),
                        $USER->id,
                        time(),
                        time()
                    );

                    $slot->save();
                }
            } else {
                if (!empty($ajaxdata[strtolower($slottype) . '_id'])) {
                    $slot = bookit_notification_slot::from_database($ajaxdata[strtolower($slottype) . '_id']);
                    $slot->isactive = 0;
                    $slot->save();
                }
            }
        }

        if (!isset($fields)) {
            $fields = [
                'id' => $id,
                'title' => $ajaxdata['title'],
                'order' => 0,
                'category' => $ajaxdata['categoryid'],
                'roomid' => $ajaxdata['roomid'],
                'roleid' => $ajaxdata['roleid'],
            ];
        }


        $fields['id'] = $id;
        $fields['roomname'] = checklist_manager::get_roomname_by_id($fields['roomid']);
        $fields['rolename'] = checklist_manager::get_rolename_by_id($fields['roleid']);


        return [
            [
                'name' => 'checklistitems',
                'action' => 'put',
                'fields' => $fields,
            ],
        ];
    }

    public function process_delete_request($id): array {

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