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
 * Hook callbacks for BookIt plugin.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

use navigation_node;

/**
 * Hook callbacks for BookIt plugin.
 */
class hook_callbacks {

    /**
     * Hook callback for primary navigation extension.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function primary_navigation_extend(\core\hook\navigation\primary_extend $hook): void {
        global $USER, $PAGE, $OUTPUT;
        $context = \context_system::instance();

        if (\has_capability('mod/bookit:managemasterchecklist', $context) && !\is_siteadmin()) {
            $url = new \moodle_url('/mod/bookit/master_checklist.php');

            // FIXME: we need to add a node to the settings tree for non-admin, non-manager users.

            // die(print_r($hook->get_primaryview(), true));
            // $hook->get_primaryview()->find('siteadminnode', null)?->add(
            //     get_string('master_checklist', 'mod_bookit'),
            //     $url,
            //     navigation_node::TYPE_CUSTOM,
            //     null,
            //     'bookit_master_checklist'
            // );
            // $PAGE->navigation->extend_for_user($USER);
            // $PAGE->navbar->includesettingsbase = true;
            // $view = $PAGE->navigation;
            // $adminnode = $view->find('siteadminnode', null);
            // $adminroot = \admin_get_root();
            // $settingsnav = $PAGE->settingsnav;
            // $settingsnav = $hook->get_primaryview();

            // $element = $settingsnav->find('siteadminnode', null);
            // $PAGE->add_header_action($OUTPUT->render_from_template('core_admin/header_search_input', [
            //     'action' => new \moodle_url('/admin/search.php'),
            // ]));

            // $element->add(
            //     get_string('master_checklist', 'mod_bookit'),
            //     $url,
            //     navigation_node::TYPE_ROOTNODE,
            //     null,
            //     'bookit_master_checklist'
            // );

            error_log("HERE");
            // $hook->get_primaryview()->add(
            //     get_string('pluginname', 'mod_bookit'),
            //     $url,
            //     navigation_node::TYPE_CUSTOM,
            //     null,
            //     'bookit_master_checklist'
            // );
        }
    }
}