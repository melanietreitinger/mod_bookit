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
 * Behat stuff for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat step definitions for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_bookit extends behat_base {

    /**
     * Creates new events in a bookit calendar.
     *
     * @Given the following bookit events exist in course :coursename:
     * @param string $coursename The full name of the course where the forums exist.
     * @return void
     */
    public function the_following_bookit_event_exists(string $coursename) {
        $eventgenerator = behat_util::get_data_generator()->get_plugin_generator('mod_bookit');
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype          | name meaning                                | description                                  |
     * | View              | Ratingallocate name                         | The bookit info page                 |
     *
     * @param string $type identifies which type of page this is, e.g. 'mod_bookit > view'.
     * @param string $identifier identifies the particular page, e.g. 'My BookIt Activity'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $DB;
        $moduleinstance = $DB->get_record('bookit', ['name' => $identifier], '*', MUST_EXIST);
        switch (strtolower($type)) {
            case 'view':
                return new moodle_url('/mod/bookit/view.php',
                        ['b' => $moduleinstance->id]);

            /*case 'edit':
                return new moodle_url('/course/modedit.php', [
                        'update' => $this->get_cm_by_bookit_name($identifier)->id]);

            case 'choices':
                return new moodle_url('/mod/bookit/view.php', [
                        'id' => $this->get_cm_by_bookit_name($identifier)->id, 'action' => ACTION_SHOW_CHOICES,
                ]);

            case 'reports':
                return new moodle_url('/mod/bookit/view.php', [
                        'id' => $this->get_cm_by_bookit_name($identifier)->id,
                        'action' => ACTION_SHOW_RATINGS_AND_ALLOCATION_TABLE,
                ]);*/

            default:
                throw new Exception('Unrecognised bookit page type "' . $type . '" with identifier "'.$identifier.'" and id "'.$moduleinstance->id.'".');
        }
    }
}
