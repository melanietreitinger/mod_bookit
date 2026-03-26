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
 * Trait for notification slots form sections.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form;

use mod_bookit\local\entity\bookit_notification_slot;
use mod_bookit\local\entity\bookit_notification_type;

/**
 * Trait providing reusable notification slots form section for dynamic forms.
 *
 * Usage:
 * - Use this trait in a class that extends core_form\dynamic_form.
 * - Call definition_notification_section($allroles) within definition().
 * - Call get_notification_slot_form_data($slots) and populate_notification_form_data() in set_data_for_dynamic_submission().
 * - Call save_notification_slots($data, $itemid) after saving the parent item.
 * - Add validate_notification_fields($data) results to validation() errors.
 *
 * Override hooks for type-specific behaviour:
 * - get_notification_default_message_prefix(): prefix for default message lang strings
 * - get_checklistitem_id_for_new_slot($itemid): checklistitemid for new slots (null for resource checklist)
 * - find_existing_inactive_slot($itemid, $type): search for reusable inactive slot
 * - on_notification_slot_saved($case, $slotid, $itemid): called after new slot is created
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait notification_slots_form_trait {
    // Overridable hooks.

    /**
     * Lang string prefix for default message templates.
     *
     * Masterchecklist uses 'customtemplatedefaultmessage'.
     * Resource checklist overrides to 'resources:customtemplatedefaultmessage'.
     *
     * @return string
     */
    protected function get_notification_default_message_prefix(): string {
        return 'customtemplatedefaultmessage';
    }

    /**
     * Returns the checklistitemid to assign to a newly created notification slot.
     *
     * Masterchecklist: returns $itemid (FK on bookit_checklist_item).
     * Resource settings: returns null (no FK, uses direct references on resource_settings row).
     *
     * @param int $itemid The parent item ID.
     * @return int|null
     */
    protected function get_checklistitem_id_for_new_slot(int $itemid): ?int {
        return $itemid;
    }

    /**
     * Searches for an existing inactive slot to reactivate instead of creating a new one.
     *
     * Masterchecklist: queries by checklistitemid + type.
     * Resource settings: returns null (slots are always tracked via explicit IDs in hidden fields).
     *
     * @param int $itemid The parent item ID.
     * @param string $type The notification type value (e.g. 'before_due').
     * @return bookit_notification_slot|null
     */
    protected function find_existing_inactive_slot(int $itemid, string $type): ?bookit_notification_slot {
        return bookit_notification_slot::get_slot_by_item_and_type($itemid, $type);
    }

    /**
     * Called after a new notification slot is created/reactivated.
     *
     * Resource checklist overrides this to store the slot ID in the
     * corresponding FK column (beforedueid, whendueid, overdueid, whendoneid)
     * on the bookit_resource_settings record.
     *
     * @param bookit_notification_type $case The notification type case.
     * @param int $slotid The ID of the saved slot.
     * @param int $itemid The parent item ID.
     * @return void
     */
    protected function on_notification_slot_saved(bookit_notification_type $case, int $slotid, int $itemid): void {
        // Default: no-op. Resource checklist overrides.
    }

    // Utility.

    /**
     * Convert snake_case form field name to camelCase property name.
     *
     * @param string $fieldname E.g. 'before_due'
     * @param string $suffix Optional suffix e.g. 'time', 'messagetext', 'recipient', 'id'
     * @return string E.g. 'beforeduetime'
     */
    protected function get_property_name(string $fieldname, string $suffix = ''): string {
        $propertyname = str_replace('_', '', $fieldname);
        if ($suffix) {
            $propertyname .= $suffix;
        }
        return $propertyname;
    }

    // Form definition.

    /**
     * Add notification slots section to the form.
     *
     * @param array $allroles Associative array of role IDs to role names.
     * @return void
     */
    protected function definition_notification_section(array $allroles): void {
        $mform = $this->_form;

        $mform->addElement('header', 'notifications', get_string('notifications', 'mod_bookit'));
        $mform->setExpanded('notifications', false);

        $prefix = $this->get_notification_default_message_prefix();

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

            if (in_array($case, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE])) {
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

            $defaultmessage = get_string($prefix . '_' . $case->value, 'mod_bookit');
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

    // Set form data.

    /**
     * Map an array of notification slots onto a data object and return it.
     *
     * Also expands the notifications header if any slot is active.
     *
     * @param bookit_notification_slot[] $slots
     * @return \stdClass Slot data object with camelCase properties.
     */
    protected function get_notification_slot_form_data(array $slots): \stdClass {
        $slotdata = new \stdClass();
        $hasactiveslots = false;

        foreach ($slots as $slot) {
            if ($slot->isactive == 1 && !$hasactiveslots) {
                $hasactiveslots = true;
            }

            $slottype = bookit_notification_type::tryFrom($slot->type);
            if ($slottype === null) {
                continue;
            }

            if (in_array($slottype, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE])) {
                $timeprop = $this->get_property_name($slottype->value, 'time');
                $slotdata->{$timeprop}['number'] = $slot->duedaysoffset;
                $slotdata->{$timeprop}['timeunit'] = DAYSECS;
            }

            $slotdata->{$this->get_property_name($slottype->value, 'id')} = $slot->id;
            $slotdata->{$this->get_property_name($slottype->value)} = $slot->isactive;
            $slotdata->{$this->get_property_name($slottype->value, 'recipient')} = json_decode($slot->roleids, true);
            $slotdata->{$this->get_property_name($slottype->value, 'messagetext')}['text'] = $slot->messagetext;
        }

        if ($hasactiveslots) {
            $this->_form->setExpanded('notifications', true);
        }

        return $slotdata;
    }

    /**
     * Populate formdata object with notification slot values from slotdata.
     *
     * Call this after building $slotdata via get_notification_slot_form_data().
     *
     * @param \stdClass $formdata The form data object to populate (passed by reference).
     * @param \stdClass $slotdata Slot data from get_notification_slot_form_data().
     * @return void
     */
    protected function populate_notification_form_data(\stdClass &$formdata, \stdClass $slotdata): void {
        $suffixes = ['time', 'messagetext', 'recipient', 'id'];

        foreach (bookit_notification_type::cases() as $case) {
            $fieldname = $case->value;
            $propname = $this->get_property_name($fieldname);

            if (isset($slotdata->{$propname})) {
                $formdata->{$fieldname} = $slotdata->{$propname};
            }

            foreach ($suffixes as $suffix) {
                $propnamewithsuffix = $this->get_property_name($fieldname, $suffix);
                if (isset($slotdata->{$propnamewithsuffix})) {
                    $formdata->{$fieldname . '_' . $suffix} = $slotdata->{$propnamewithsuffix};
                }
            }
        }
    }

    // Save.

    /**
     * Save notification slots from submitted form data.
     *
     * Loops over all notification types. For each:
     * - If checkbox enabled: update existing slot or create/reactivate.
     * - If checkbox disabled but slot exists: deactivate the slot.
     *
     * @param array $data Submitted form data (from get_data()).
     * @param int $itemid The parent item ID (checklist item or resource checklist item).
     * @return void
     */
    protected function save_notification_slots(array $data, int $itemid): void {
        global $USER;

        foreach (bookit_notification_type::cases() as $case) {
            $casename = strtolower($case->name);

            if (!empty($data[$casename])) {
                $daysoffset = 0;
                if (in_array($case, [bookit_notification_type::BEFORE_DUE, bookit_notification_type::OVERDUE])) {
                    $daysoffset = $data[$casename . '_time']['number'] ?? 0;
                }

                if (!empty($data[$casename . '_id'])) {
                    // Update existing slot.
                    $slot = bookit_notification_slot::from_database($data[$casename . '_id']);
                    $slot->roleids = json_encode($data[$casename . '_recipient'] ?? []);
                    $slot->messagetext = format_text($data[$casename . '_messagetext']['text'] ?? '', FORMAT_HTML);
                    $slot->duedaysoffset = $daysoffset;
                    $slot->isactive = 1;
                    $slot->save();
                } else {
                    // Try to reactivate an existing inactive slot (hook: masterchecklist uses this).
                    $existingslot = $this->find_existing_inactive_slot($itemid, $case->value);

                    if ($existingslot) {
                        $existingslot->roleids = json_encode($data[$casename . '_recipient'] ?? []);
                        $existingslot->messagetext = format_text($data[$casename . '_messagetext']['text'] ?? '', FORMAT_HTML);
                        $existingslot->duedaysoffset = $daysoffset;
                        $existingslot->isactive = 1;
                        $existingslot->save();
                        $this->on_notification_slot_saved($case, $existingslot->id, $itemid);
                    } else {
                        // Create new slot.
                        $duedaysrelation = null;
                        if ($case === bookit_notification_type::BEFORE_DUE) {
                            $duedaysrelation = 'before';
                        } else if ($case === bookit_notification_type::OVERDUE) {
                            $duedaysrelation = 'after';
                        }

                        $slot = new bookit_notification_slot(
                            null,
                            $this->get_checklistitem_id_for_new_slot($itemid),
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

                        $slotid = $slot->save();
                        $this->on_notification_slot_saved($case, $slotid, $itemid);
                    }
                }
            } else if (!empty($data[$casename . '_id'])) {
                // Checkbox unchecked but slot exists → deactivate.
                $slot = bookit_notification_slot::from_database($data[$casename . '_id']);
                $slot->isactive = 0;
                $slot->save();
            }
        }
    }

    // Validation.

    /**
     * Validate notification slot fields.
     *
     * Returns an array of errors keyed by form field name.
     * Merge into parent::validation() result.
     *
     * @param array $data Submitted form data.
     * @return array Validation errors.
     */
    protected function validate_notification_fields(array $data): array {
        $errors = [];

        foreach (bookit_notification_type::cases() as $case) {
            if (!empty($data[$case->value])) {
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
}
