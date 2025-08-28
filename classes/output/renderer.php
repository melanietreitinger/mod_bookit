<?php

namespace mod_bookit\output;

use mod_bookit\local\entity\bookit_checklist_category;
use mod_bookit\local\entity\bookit_checklist_master;
use mod_bookit\local\entity\bookit_checklist_item;
use mod_bookit\local\entity\bookit_notification_slot;

class renderer extends \plugin_renderer_base {

    protected function render_checklist_master(bookit_checklist_master $checklistmaster) {
        $data = $checklistmaster->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/bookit_checklist_master', $data);
    }

    protected function render_checklist_category(bookit_checklist_category $checklistcategory) {
        $data = $checklistcategory->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/bookit_checklist_category', $data);
    }

    protected function render_checklist_item(bookit_checklist_item $checklistitem) {
        $data = $checklistitem->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/bookit_checklist_item', $data);
    }

    protected function render_notification_slot(bookit_notification_slot $notification_slot) {
        $data = $notification_slot->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/bookit_notification_slot', $data);
    }
}
