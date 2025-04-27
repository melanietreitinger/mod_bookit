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
 * Table listing all weekplan assignments of a room.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\table;

use core\output\html_writer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table listing all weekplan assignments of a room.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class weekplan_room_table extends \table_sql {

    /**
     * Constructor for weekplan_room_table.
     * @param int $roomid ID of room to create table for.
     */
    public function __construct(
        /** @var int ID of room to create table for. */
        private int $roomid
    ) {
        global $PAGE;
        parent::__construct('mod_bookit-room_weekplan_table');
        $this->define_baseurl($PAGE->url);
        $this->set_sql('wr.id, wr.weekplanid, w.name as weekplanname, wr.starttime, wr.endtime',
            '{bookit_weekplan_room} wr ' .
            'JOIN {bookit_weekplan} w ON wr.weekplanid = w.id ',
            'wr.roomid = :roomid',
            ['roomid' => $roomid]
        );
        $this->column_nosort = ['tools'];
        $this->define_columns(['starttime', 'weekplanname', 'tools']);
        $this->define_headers([
            get_string('period', 'mod_bookit'),
            get_string('weekplan', 'mod_bookit'),
            get_string('tools', 'mod_bookit'),
        ]);
    }

    /**
     * Renders the starttime column.
     * @param object $row
     * @return string
     */
    public function col_starttime(object $row) {
        return date('d.m.Y', $row->starttime) . ' - ' . date('d.m.Y', $row->endtime);
    }

    /**
     * Renders the weekplanname column.
     * @param object $row
     * @return string
     */
    public function col_weekplanname(object $row) {
        return html_writer::link(
            new \moodle_url('/mod/bookit/weekplan.php', ['id' => $row->weekplanid]),
            htmlentities($row->weekplanname)
        );
    }

    /**
     * Render tools column.
     * @param object $row Row data.
     * @return string action buttons for workflow.
     */
    public function col_tools($row) {
        global $OUTPUT;
        $output = '';

        $alt = get_string('edit');
        $icon = 't/edit';
        $url = new \moodle_url('/mod/bookit/edit_weekplan_room.php', ['id' => $row->id, 'roomid' => $this->roomid]);
        $output .= $OUTPUT->action_icon($url, new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null, ['title' => $alt]);

        $alt = get_string('delete');
        $output .= $OUTPUT->action_icon(new \moodle_url('/mod/bookit/view_room.php', [
            'id' => $this->roomid, 'action' => 'delete', 'weekplanroomid' => $row->id,
        ]), new \pix_icon('t/delete', $alt));

        return $output;
    }
}
