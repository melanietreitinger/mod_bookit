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
 * Table listing all rooms.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\table;

use core\exception\moodle_exception;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table listing all rooms.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rooms_table extends \table_sql {
    /**
     * Constructor.
     */
    public function __construct() {
        global $PAGE;
        parent::__construct('mod_bookit-rooms_table');
        $this->define_baseurl($PAGE->url);
        $this->set_sql(
            'r.id, r.name, r.active, r.description, r.eventcolor, w.name as activeweekplan, w.id as activeweekplanid, ' .
            'r.shortname, r.location, r.seats ',
            '{bookit_room} r ' .
            'LEFT JOIN {bookit_weekplan_room} wr ON r.id = wr.roomid AND wr.starttime <= :time1 AND ' .
                '(wr.endtime IS NULL OR wr.endtime >= :time2) ' .
            'LEFT JOIN {bookit_weekplan} w ON wr.weekplanid = w.id ',
            'true',
            ['time1' => time(), 'time2' => time()]
        );
        $this->column_nosort = ['internalnotes', 'tools'];
        $this->define_columns([
            'name',
            'shortname',
            'seats',
            'location',
            'description',
            'activeweekplan',
            'tools',
        ]);
        $this->define_headers([
            get_string('name'),
            get_string('shortname', 'mod_bookit'),
            get_string('seats', 'mod_bookit'),
            get_string('location', 'mod_bookit'),
            get_string('description'),
            get_string('active_weekplan', 'mod_bookit'),
            get_string('tools', 'mod_bookit'),
        ]);
    }

    /**
     * Render name column.
     * @param object $row Row data.
     * @return string.
     */
    public function col_name($row) {
        $url = new \moodle_url('/mod/bookit/admin/view_room.php', ['id' => $row->id]);
        return \html_writer::link($url, $row->name);
    }

    /**
     * Render 'activeweekplan' column.
     *
     * @param object $row
     * @return string
     * @throws moodle_exception
     */
    public function col_activeweekplan($row) {
        if (isset($row->activeweekplanid)) {
            return \html_writer::link(
                new \moodle_url('/mod/bookit/admin/weekplan.php', ['id' => $row->activeweekplanid]),
                $row->activeweekplan,
            );
        }
        return '-';
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
        $url = new \moodle_url('/mod/bookit/admin/view_room.php', ['id' => $row->id]);
        $output .= $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        );

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
