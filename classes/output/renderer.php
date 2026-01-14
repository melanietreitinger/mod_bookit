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
 * Renderer for the BookIt module.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\output;

use mod_bookit\local\entity\masterchecklist\bookit_checklist_category;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_master;
use mod_bookit\local\entity\masterchecklist\bookit_checklist_item;
use mod_bookit\local\entity\bookit_notification_slot;

/**
 * Renderer class for the BookIt module.
 *
 * Handles rendering of various BookIt entities.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Renders a master checklist.
     *
     * @param bookit_checklist_master $checklistmaster The master checklist to render
     * @return string HTML output
     */
    protected function render_checklist_master(bookit_checklist_master $checklistmaster) {
        $data = $checklistmaster->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/masterchecklist/bookit_checklist_master', $data);
    }

    /**
     * Renders a checklist category.
     *
     * @param bookit_checklist_category $checklistcategory The checklist category to render
     * @return string HTML output
     */
    protected function render_checklist_category(bookit_checklist_category $checklistcategory) {
        $data = $checklistcategory->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/masterchecklist/bookit_checklist_category', $data);
    }

    /**
     * Renders a checklist item.
     *
     * @param bookit_checklist_item $checklistitem The checklist item to render
     * @return string HTML output
     */
    protected function render_checklist_item(bookit_checklist_item $checklistitem) {
        $data = $checklistitem->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/masterchecklist/bookit_checklist_item', $data);
    }

    /**
     * Renders a notification slot.
     *
     * @param bookit_notification_slot $notificationslot The notification slot to render
     * @return string HTML output
     */
    protected function render_notification_slot(bookit_notification_slot $notificationslot) {
        $data = $notificationslot->export_for_template($this->output);
        return $this->output->render_from_template('mod_bookit/bookit_notification_slot', $data);
    }

    /**
     * Write the tab row in page
     *
     * @param array $tabs the tabs
     * @param string $id  ID of current page (can be empty)
     */
    public function tabs($tabs, $id) {
        return $this->output->tabtree($tabs, $id);
    }
}
