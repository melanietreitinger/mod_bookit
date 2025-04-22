<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Table listing all institutions
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\table;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table listing all institutions.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class institutions_table extends \table_sql {

    /**
     * Constructor.
     */
    public function __construct() {
        global $PAGE;
        parent::__construct('mod_bookit-institutions_table');
        $this->define_baseurl($PAGE->url);
        $this->set_sql('id, name, internalnotes, active', '{bookit_institution}', 'true');
        $this->column_nosort = ['internalnotes', 'tools'];
        $this->define_columns(['name', 'internalnotes', 'tools']);
        $this->define_headers([
            get_string('institution', 'mod_bookit'),
            get_string('internalnotes', 'mod_bookit'),
            get_string('tools', 'mod_bookit'),
        ]);
    }

    /**
     * Render tools column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_tools($row) {
        global $OUTPUT;
        $output = '';

        $alt = get_string('edit');
        $icon = 't/edit';
        $url = new \moodle_url('/mod/bookit/edit_institution.php', ['id' => $row->id]);
        $output .= $OUTPUT->action_icon($url, new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null, ['title' => $alt]);

        return $output;
    }

    /**
     * Get any extra classes names to add to this row in the HTML.
     *
     * @param \stdClass $row the data for this row.
     * @return string added to the class="" attribute of the tr.
     */
    public function get_row_class($row) {
        if (!$row->active) {
            return 'text-muted';
        }
        return '';
    }
}
