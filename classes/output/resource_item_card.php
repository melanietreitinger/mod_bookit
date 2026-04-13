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
 * Resource item card output class
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\output;

use mod_bookit\local\entity\resource\bookit_resource;
use mod_bookit\local\manager\color_manager;
use renderer_base;
use renderable;
use templatable;
use stdClass;

/**
 * Resource item card output class
 *
 * Prepares data for a single resource item card
 */
class resource_item_card implements renderable, templatable {
    /** @var bookit_resource */
    private $resource;

    /** @var int */
    private $totalrooms;

    /**
     * Constructor
     *
     * @param bookit_resource $resource
     * @param int $totalrooms Total number of rooms (passed in to avoid repeated DB queries)
     */
    public function __construct(bookit_resource $resource, int $totalrooms = 0) {
        $this->resource = $resource;
        $this->totalrooms = $totalrooms;
    }

    /**
     * Export data for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->id = $this->resource->get_id();
        $data->name = $this->resource->get_name();
        $data->description = format_text($this->resource->get_description() ?? '');
        $data->description_raw = $this->resource->get_description() ?? '';
        $data->categoryid = $this->resource->get_categoryid();
        $data->amount = $this->resource->get_amount();
        $data->amountirrelevant = $this->resource->is_amountirrelevant();
        $data->sortorder = $this->resource->get_sortorder();
        $data->active = $this->resource->is_active();
        $data->roomids = json_encode($this->resource->get_roomids());
        $data->roomnames = $this->get_room_names();

        $assignedcount = count($this->resource->get_roomids() ?? []);
        $data->isallrooms = $this->totalrooms > 0 &&
            ($this->resource->get_roomids() === null || $assignedcount === $this->totalrooms);

        return $data;
    }

    /**
     * Get room names with colors for display
     *
     * @return array
     */
    private function get_room_names(): array {
        global $DB;

        $roomids = $this->resource->get_roomids();
        if (empty($roomids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($roomids, SQL_PARAMS_NAMED);
        $rooms = $DB->get_records_select('bookit_room', "id $insql", $inparams);

        $roomnames = [];
        foreach ($roomids as $roomid) {
            if (!isset($rooms[$roomid])) {
                continue;
            }
            $room = $rooms[$roomid];
            $eventcolor = $room->eventcolor ?? '';
            $textcolor = color_manager::get_textcolor_for_background($eventcolor);
            $textclass = $textcolor === '#000' ? 'text-dark' : 'text-light';

            $roomnames[] = [
                'roomid' => $room->id,
                'roomname' => $room->name,
                'shortname' => $room->shortname ?? '',
                'eventcolor' => $eventcolor,
                'textclass' => $textclass,
                'roomurl' => (new \moodle_url('/mod/bookit/admin/edit_room.php', ['id' => $room->id]))->out(false),
            ];
        }

        return $roomnames;
    }
}
