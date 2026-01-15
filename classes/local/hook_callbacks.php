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
     * This adds a BookIt settings link to the primary navigation for users with the capability.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function primary_navigation_extend(\core\hook\navigation\primary_extend $hook): void {
        global $PAGE, $OUTPUT;

        $context = \context_system::instance();

        // Check if user has the required capability.
        // TODO: use other capability.
        if (!\has_capability('mod/bookit:managemasterchecklist', $context) || is_siteadmin()) {
            return;
        }

        // Get the primary navigation.
        $primarynav = $hook->get_primaryview();

        $icon = $OUTPUT->pix_icon('i/settings', get_string('settings_overview', 'mod_bookit'));

        // Add BookIt settings node to the primary navigation.
        $node = $primarynav->add(
                $icon . get_string('pluginname', 'mod_bookit'),
            new \moodle_url('/mod/bookit/admin/calendar.php?id=calendar'),
            navigation_node::TYPE_CUSTOM,
            null,
            'bookit_settings',
            new \pix_icon('i/settings', '')
        );

        $tabslist = tabs::get_tabrow($context);

        // Set it as active if we're on any bookit admin page.
        foreach ($tabslist as $tab) {
            if ($PAGE->url->compare(new \moodle_url('/mod/bookit/admin/'.$tab->id.'.php'), URL_MATCH_BASE)) {
                $node->make_active();
            }
        }
    }
}
