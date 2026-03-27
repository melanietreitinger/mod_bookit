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
 * Form for editing a resource checklist item.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form\resource;

use context;
use context_system;
use core_form\dynamic_form;
use mod_bookit\local\entity\bookit_notification_slot;
use mod_bookit\local\entity\bookit_notification_type;
use mod_bookit\local\form\notification_slots_form_trait;
use mod_bookit\local\manager\checklist_manager;
use mod_bookit\local\manager\resource_settings_manager;
use mod_bookit\local\manager\resource_manager;
use moodle_url;

/**
 * Dynamic form for editing a resource checklist item.
 *
 * Mirrors masterchecklist item form style:
 * - Radio buttons for due date type (none / before_event / after_event)
 * - Duration element for days offset
 * - Notifications section via notification_slots_form_trait
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_resource_settings_item_form extends dynamic_form {
    use notification_slots_form_trait;

    /**
     * Define the form elements.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', 'put');

        $mform->addElement('hidden', 'items');
        $mform->setType('items', PARAM_TEXT);

        // Resource name (read-only).
        $mform->addElement('static', 'resourcename', get_string('resources:resource', 'mod_bookit'), '');

        // Rooms (read-only info).
        $mform->addElement('static', 'roomslist', get_string('rooms', 'mod_bookit'), '');

        // Due date — radio group matching masterchecklist style.
        $duedateradio = [
            $mform->createElement('radio', 'duedatetype', '', get_string('noduedate', 'mod_bookit'), 'none'),
            $mform->createElement(
                'radio',
                'duedatetype',
                '',
                get_string('duedate_before_event', 'mod_bookit'),
                'before_event'
            ),
            $mform->createElement(
                'radio',
                'duedatetype',
                '',
                get_string('duedate_after_event', 'mod_bookit'),
                'after_event'
            ),
        ];
        $mform->addGroup($duedateradio, 'duedatetypegroup', get_string('duedate', 'mod_bookit'), null, false);
        $mform->setDefault('duedatetype', 'none');
        $mform->addHelpButton('duedatetypegroup', 'duedate', 'mod_bookit');

        // Days offset (duration element, hidden when none).
        $mform->addElement('duration', 'duedaysoffset', get_string('time', 'mod_bookit'), ['units' => [DAYSECS]]);
        $mform->setDefault('duedaysoffset', ['number' => 14, 'timeunit' => DAYSECS]);
        $mform->hideIf('duedaysoffset', 'duedatetype', 'eq', 'none');
        $mform->addHelpButton('duedaysoffset', 'time', 'mod_bookit');

        $allroles = array_column(checklist_manager::get_bookit_roles(), 'name', 'id');
        $this->definition_notification_section($allroles);

        // Pre-select and freeze notification recipient fields to service team role.
        $serviceteamroleid = $this->get_serviceteam_role_id();
        if ($serviceteamroleid) {
            foreach (\mod_bookit\local\entity\bookit_notification_type::cases() as $case) {
                $fieldname = $case->value . '_recipient';
                $mform->setDefault($fieldname, [$serviceteamroleid]);
                $mform->freeze($fieldname);
            }
        }
    }

    /**
     * Use resource-specific default notification message templates.
     *
     * @return string
     */
    protected function get_notification_default_message_prefix(): string {
        return 'resources:customtemplatedefaultmessage';
    }

    /**
     * Resource checklist slots use checklistitemid = null (tracked via FK columns instead).
     *
     * @param int $itemid
     * @return int|null
     */
    protected function get_checklistitem_id_for_new_slot(int $itemid): ?int {
        return null;
    }

    /**
     * Find an existing inactive slot via the FK columns on the resource checklist item.
     *
     * @param int $itemid Resource checklist item ID
     * @param string $type Notification type value
     * @return bookit_notification_slot|null
     */
    protected function find_existing_inactive_slot(int $itemid, string $type): ?bookit_notification_slot {
        $rcitem = resource_settings_manager::get_checklist_item($itemid);
        if (!$rcitem) {
            return null;
        }
        $slotid = match ($type) {
            'before_due' => $rcitem->get_beforedueid(),
            'when_due'   => $rcitem->get_whendueid(),
            'overdue'    => $rcitem->get_overdueid(),
            'when_done'  => $rcitem->get_whendoneid(),
            default      => null,
        };
        if ($slotid === null) {
            return null;
        }
        try {
            $slot = bookit_notification_slot::from_database($slotid);
            return ($slot->isactive == 0) ? $slot : null;
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * After saving a new slot, store its ID in the correct FK column.
     *
     * @param bookit_notification_type $case
     * @param int $slotid
     * @param int $itemid
     */
    protected function on_notification_slot_saved(bookit_notification_type $case, int $slotid, int $itemid): void {
        global $USER;
        $rcitem = resource_settings_manager::get_checklist_item($itemid);
        if (!$rcitem) {
            return;
        }
        switch ($case) {
            case bookit_notification_type::BEFORE_DUE:
                $rcitem->set_beforedueid($slotid);
                break;
            case bookit_notification_type::WHEN_DUE:
                $rcitem->set_whendueid($slotid);
                break;
            case bookit_notification_type::OVERDUE:
                $rcitem->set_overdueid($slotid);
                break;
            case bookit_notification_type::WHEN_DONE:
                $rcitem->set_whendoneid($slotid);
                break;
        }
        resource_settings_manager::save_checklist_item($rcitem, $USER->id);
    }

    /**
     * Check access.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('mod/bookit:managebasics', context_system::instance());
    }

    /**
     * Get context.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Get page URL.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/mod/bookit/admin/resources.php', ['id' => 'resources']);
    }

    /**
     * Populate form with existing item data.
     */
    public function set_data_for_dynamic_submission(): void {
        // Skip item loading when processing a reorder request.
        if (!empty($this->optional_param('items', null, PARAM_TEXT))) {
            return;
        }
        if (!empty($this->optional_param('categoryorder', null, PARAM_TEXT))) {
            return;
        }
        $id = $this->optional_param('id', null, PARAM_INT);
        if (empty($id)) {
            // Support opening by resourceid directly (e.g. from resource catalog button).
            $resourceid = $this->optional_param('resourceid', null, PARAM_INT);
            if (!empty($resourceid)) {
                $item = resource_settings_manager::get_checklist_item_by_resource($resourceid);
                if ($item) {
                    $id = $item->get_id();
                }
            }
        }
        if (empty($id)) {
            throw new \moodle_exception('invalidchecklistitemid', 'mod_bookit');
        }

        $item = resource_settings_manager::get_checklist_item($id);
        if (!$item) {
            throw new \moodle_exception('checklistitemnotfound', 'mod_bookit');
        }

        $resource = resource_manager::get_resource_by_id($item->get_resourceid());
        if (!$resource) {
            throw new \moodle_exception('resources:notfound', 'mod_bookit');
        }

        // Build rooms display string.
        $roomnames = resource_manager::get_room_names_for_resource($resource);
        $roomshtml = !empty($roomnames)
            ? implode(', ', array_map('s', $roomnames))
            : get_string('none');

        // Map duedatetype/duedate to radio + duration.
        $duedatetype = $item->get_duedatetype() ?? 'none';
        if ($duedatetype === '') {
            $duedatetype = 'none';
        }
        $duedaysoffset = ['number' => 0, 'timeunit' => DAYSECS];
        if ($duedatetype !== 'none' && $item->get_duedate() !== null) {
            $duedaysoffset['number'] = intval($item->get_duedate() / DAYSECS);
        }

        // Load notification slots by FK IDs stored on the item.
        $slots = [];
        $slotids = [
            $item->get_beforedueid(),
            $item->get_whendueid(),
            $item->get_overdueid(),
            $item->get_whendoneid(),
        ];
        foreach ($slotids as $slotid) {
            if ($slotid !== null) {
                try {
                    $slots[] = bookit_notification_slot::from_database($slotid);
                } catch (\dml_exception $e) {
                    debugging('Notification slot ' . $slotid . ' not found: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }

        $slotdata = $this->get_notification_slot_form_data($slots);

        $formdata = new \stdClass();
        $formdata->id = $item->get_id();
        $formdata->sortorder = $item->get_sortorder();
        $formdata->action = 'put';
        $formdata->duedatetype = $duedatetype;
        $formdata->duedaysoffset = $duedaysoffset;

        // Set static display values.
        $this->_form->getElement('resourcename')->setValue($resource->get_name());
        $this->_form->getElement('roomslist')->setValue($roomshtml);

        $this->populate_notification_form_data($formdata, $slotdata);
        $this->set_data($formdata);
    }

    /**
     * Process the submitted form and return processUpdates data.
     *
     * @return array processUpdates array
     */
    public function process_dynamic_submission(): array {
        global $USER, $DB;

        $itemsparam = $this->optional_param('items', null, PARAM_TEXT);
        if (!empty($itemsparam)) {
            $itemids = json_decode($itemsparam, true);
            if (is_array($itemids)) {
                $sortorder = 1;
                foreach ($itemids as $itemid) {
                    $itemid = clean_param($itemid, PARAM_INT);
                    if ($itemid) {
                        $DB->set_field('bookit_resource_settings', 'sortorder', $sortorder++, ['id' => $itemid]);
                    }
                }
            }
            return [];
        }

        $data = (array)$this->get_data();
        $id = (int)$data['id'];

        $item = resource_settings_manager::get_checklist_item($id);
        if (!$item) {
            throw new \moodle_exception('checklistitemnotfound', 'mod_bookit');
        }

        $duedatetype = $data['duedatetype'] ?? 'none';
        if ($duedatetype === 'none') {
            $item->set_duedatetype(null);
            $item->set_duedate(null);
        } else {
            $item->set_duedatetype($duedatetype);
            // Duration exportValue() returns total seconds as integer, not array.
            $offsetseconds = (int)($data['duedaysoffset'] ?? 0);
            $item->set_duedate($offsetseconds);
        }

        resource_settings_manager::save_checklist_item($item, $USER->id);

        $this->save_notification_slots($data, $id);

        // Reload after notifications saved (FK columns may have been updated).
        $item = resource_settings_manager::get_checklist_item($id);
        $resource = resource_manager::get_resource_by_id($item->get_resourceid());

        $duedatedisplay = null;
        $duedate = $item->get_duedate();
        $duedatetype = $item->get_duedatetype();
        if (!empty($duedate) && !empty($duedatetype) && $duedatetype !== 'none') {
            $days = (int)round((int)$duedate / DAYSECS);
            if ($duedatetype === 'before_event') {
                $duedatedisplay = get_string('checklist_duedate_days_before', 'mod_bookit', $days);
            } else if ($duedatetype === 'after_event') {
                $duedatedisplay = get_string('checklist_duedate_days_after', 'mod_bookit', $days);
            }
        }

        return [[
            'action' => 'put',
            'name'   => 'checklistitems',
            'fields' => [
                'id'             => $item->get_id(),
                'name'           => $resource ? $resource->get_name() : '',
                'sortorder'      => $item->get_sortorder(),
                'resourceid'     => $item->get_resourceid(),
                'duedate'        => $duedate,
                'duedatetype'    => $duedatetype,
                'duedatedisplay' => $duedatedisplay,
                'beforedueid'    => $item->get_beforedueid(),
                'whendueid'      => $item->get_whendueid(),
                'overdueid'      => $item->get_overdueid(),
                'whendoneid'     => $item->get_whendoneid(),
            ],
        ]];
    }

    /**
     * Validate form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $duedatetype = $data['duedatetype'] ?? 'none';
        if ($duedatetype !== 'none') {
            // Duration exportValue() returns total seconds as integer, not array.
            $offsetseconds = (int)($data['duedaysoffset'] ?? 0);
            if ($offsetseconds <= 0) {
                $errors['duedaysoffset'] = get_string('err_numeric', 'form');
            }
        }

        $errors = array_merge($errors, $this->validate_notification_fields($data));
        return $errors;
    }

    /**
     * Fix duration fields after data is set.
     */
    public function definition_after_data() {
        parent::definition_after_data();
        $this->fix_duration_field('duedaysoffset');
        foreach (bookit_notification_type::cases() as $case) {
            if (in_array($case, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE])) {
                $this->fix_duration_field($case->value . '_time');
            }
        }
    }

    /**
     * Ensure a duration field has the correct array format.
     *
     * @param string $fieldname
     */
    private function fix_duration_field(string $fieldname): void {
        if (!$this->_form->elementExists($fieldname)) {
            return;
        }
        $element = $this->_form->getElement($fieldname);
        $currentvalue = $element->getValue();
        $duration = ['number' => 0, 'timeunit' => DAYSECS];
        if (is_array($currentvalue)) {
            $duration['number'] = (int)($currentvalue['number'] ?? 0);
        } else if (is_numeric($currentvalue)) {
            $duration['number'] = (int)$currentvalue;
        }
        $element->setValue($duration);
    }

    /**
     * Get the ID of the service team role (bookit_serviceteam).
     *
     * @return int|null Role ID, or null if not found
     */
    private function get_serviceteam_role_id(): ?int {
        global $DB;
        $role = $DB->get_record('role', ['shortname' => 'bookit_serviceteam'], 'id', IGNORE_MISSING);
        return $role ? (int)$role->id : null;
    }
}
