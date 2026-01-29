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
 * Form for the checklist admin settings.
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

use moodleform;

/**
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_checklist_form extends moodleform {
    /**
     * Define the form
     */
    public function definition(): void {
        global $CFG;
        $mform =& $this->_form;

        $mform->addElement('selectyesno', 'pdf_logo_enable', get_string('settings_pdf_logo_enable', 'bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/pdf_logo_enable</code>' . '<br><br>' .
                get_string('settings_pdf_logo_enable_desc', 'mod_bookit'));

        $logosourceoptions = [
                'site' => get_string('settings_pdf_logo_source_site', 'mod_bookit'),
                'custom' => get_string('settings_pdf_logo_source_custom', 'mod_bookit'),
        ];
        $themeboostunionpath = $CFG->dirroot . '/theme/boost_union';

        if (file_exists($themeboostunionpath) && is_dir($themeboostunionpath)) {
            $logosourceoptions['theme'] = get_string('settings_pdf_logo_source_theme', 'mod_bookit');
        }

        $mform->addElement('select', 'pdf_logo_source', get_string('settings_pdf_logo_source', 'bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/pdf_logo_source</code>' . '<br><br>' .
                get_string('settings_pdf_logo_source_desc', 'mod_bookit'), $logosourceoptions);

        $mform->addElement('filepicker', 'pdf_logo_custom', get_string('settings_pdf_logo_custom', 'bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/pdf_logo_custom</code>' . '<br><br>' .
                get_string('settings_pdf_logo_custom_desc', 'mod_bookit'), null, [
                'maxfiles' => 1,
                'accepted_types' => ['.png', '.jpg', '.jpeg'],
        ]);
        $mform->hideIf('pdf_logo_custom', 'pdf_logo_source', 'neq', 'custom');

        $this->add_action_buttons();
    }
}
