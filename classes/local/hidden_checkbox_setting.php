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
 * Admin checkbox setting hidden from view while remaining in the settings form.
 *
 * @package     mod_bookit
 * @copyright   2026 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

/**
 * Wraps a checkbox setting in a hidden div.
 */
class hidden_checkbox_setting extends \admin_setting_configcheckbox {
    /**
     * Returns the checkbox setting inside a hidden div.
     *
     * @param string $data The current setting value.
     * @param string $query The search query.
     * @return string XHTML field.
     */
    public function output_html($data, $query = '') {
        return \html_writer::tag('div', parent::output_html($data, $query), ['style' => 'display:none']);
    }
}
