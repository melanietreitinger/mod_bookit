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
 * Resource settings catalog output class.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\output;

use mod_bookit\local\manager\checklist_manager;
use mod_bookit\local\manager\resource_settings_manager;
use mod_bookit\local\manager\resource_manager;
use renderer_base;
use renderable;
use templatable;
use stdClass;

/**
 * Resource settings catalog output class.
 *
 * Prepares data for the resource checklist template.
 */
class resource_settings_catalog implements renderable, templatable {
    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->contextid = \context_system::instance()->id;
        $data->categories = [];

        $categories = resource_manager::get_all_categories();

        $allrooms = checklist_manager::get_bookit_rooms();
        $totalrooms = count($allrooms);
        $roomsbyid = [];
        foreach ($allrooms as $room) {
            $roomsbyid[(int)$room->id] = $room;
        }

        $checklistitems = resource_settings_manager::get_all_checklist_items_with_rooms();

        $itemsbycategory = [];
        foreach ($checklistitems as $item) {
            if (!isset($itemsbycategory[$item->categoryid])) {
                $itemsbycategory[$item->categoryid] = [];
            }
            $itemsbycategory[$item->categoryid][] = $item;
        }

        foreach ($categories as $category) {
            $categorydata = new stdClass();
            $categorydata->id = $category->get_id();
            $categorydata->name = $category->get_name();
            $categorydata->description = format_text($category->get_description() ?? '', FORMAT_HTML);
            $categorydata->sortorder = $category->get_sortorder();
            $categorydata->items = [];

            if (isset($itemsbycategory[$category->get_id()])) {
                foreach ($itemsbycategory[$category->get_id()] as $item) {
                    $categorydata->items[] = $this->build_item_data($item, $roomsbyid, $totalrooms);
                }
            }

            $data->categories[] = $categorydata;
        }

        return $data;
    }

    /**
     * Build template data for a single checklist item.
     *
     * @param stdClass $item Checklist item with joined resource data
     * @param array $roomsbyid Room records indexed by id
     * @param int $totalrooms Total number of rooms
     * @return stdClass
     */
    private function build_item_data(stdClass $item, array $roomsbyid, int $totalrooms): stdClass {
        $itemdata = new stdClass();
        $itemdata->id = $item->id;
        $itemdata->resourceid = $item->resourceid;
        $itemdata->name = $item->name;
        $itemdata->description = format_text($item->description ?? '', FORMAT_HTML);
        $itemdata->categoryid = $item->categoryid;
        $itemdata->amount = $item->amountirrelevant ? null : (int)$item->amount;
        $itemdata->amountirrelevant = (bool)$item->amountirrelevant;
        $itemdata->sortorder = $item->sortorder;
        $itemdata->active = (bool)$item->resource_active;
        $itemdata->duedate = $this->format_duedate($item);
        $itemdata->duedatetype = $item->duedatetype ?? null;

        $roomdata = $this->resolve_rooms($item->roomids ?? null, $roomsbyid, $totalrooms);
        $itemdata->roomnames = $roomdata['visible'];
        $itemdata->moreroomscount = $roomdata['morecount'];
        $itemdata->allroomnames = $roomdata['allnames'];
        $itemdata->rooms = $itemdata->allroomnames;
        $itemdata->hasrooms = $roomdata['hasrooms'];
        $itemdata->isallrooms = $roomdata['isallrooms'];

        return $itemdata;
    }

    /**
     * Format due date string for display.
     *
     * @param stdClass $item Checklist item
     * @return string|null Formatted due date or null
     */
    private function format_duedate(stdClass $item): ?string {
        if (empty($item->duedate) || empty($item->duedatetype) || $item->duedatetype === 'none') {
            return null;
        }

        $days = (int)round((int)$item->duedate / DAYSECS);
        if ($item->duedatetype === 'before_event') {
            return get_string('checklist_duedate_days_before', 'mod_bookit', $days);
        } else if ($item->duedatetype === 'after_event') {
            return get_string('checklist_duedate_days_after', 'mod_bookit', $days);
        }

        return null;
    }

    /**
     * Resolve room names and badges from JSON roomids field.
     *
     * @param string|null $roomidsjson JSON-encoded array of room IDs
     * @param array $roomsbyid Room records indexed by id
     * @param int $totalrooms Total number of rooms
     * @return array{visible: array, morecount: int|null, allnames: string, hasrooms: bool, isallrooms: bool}
     */
    private function resolve_rooms(?string $roomidsjson, array $roomsbyid, int $totalrooms): array {
        $roomnames = [];
        $roomnamesplain = [];

        if (!empty($roomidsjson)) {
            $roomids = json_decode($roomidsjson, true);
            if (is_array($roomids)) {
                foreach ($roomids as $roomid) {
                    if (isset($roomsbyid[(int)$roomid])) {
                        $room = $roomsbyid[(int)$roomid];
                        $roomnames[] = [
                            'roomid'     => $room->id,
                            'roomname'   => $room->name,
                            'shortname'  => $room->shortname ?? '',
                            'eventcolor' => $room->eventcolor ?? '#6c757d',
                            'textclass'  => $room->textclass ?? 'text-light',
                        ];
                        $roomnamesplain[] = $room->name;
                    }
                }
            }
        }

        $maxvisible = 2;
        $visiblerooms = array_slice($roomnames, 0, $maxvisible);
        $moreroomscount = max(0, count($roomnames) - $maxvisible);
        foreach ($visiblerooms as &$r) {
            $r['shortname'] = $r['shortname'] ?: $r['roomname'];
        }
        unset($r);

        $assignedcount = !empty($roomidsjson) ? count(json_decode($roomidsjson, true) ?: []) : 0;

        return [
            'visible'   => $visiblerooms,
            'morecount' => $moreroomscount > 0 ? $moreroomscount : null,
            'allnames'  => implode(', ', $roomnamesplain),
            'hasrooms'  => !empty($roomnames),
            'isallrooms' => $totalrooms > 0 && $assignedcount === $totalrooms,
        ];
    }
}
