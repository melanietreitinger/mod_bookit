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
 * Resource category card output class
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\output;

use mod_bookit\local\entity\resource\bookit_resource_category;
use mod_bookit\local\manager\resource_manager;
use renderer_base;
use renderable;
use templatable;
use stdClass;

/**
 * Resource category card output class
 *
 * Prepares data for a single resource category card
 */
class resource_category_card implements renderable, templatable {
    /** @var bookit_resource_category */
    private $category;

    /** @var int */
    private $totalrooms;

    /**
     * Constructor
     *
     * @param bookit_resource_category $category
     * @param int $totalrooms Total number of rooms (passed in to avoid repeated DB queries)
     */
    public function __construct(bookit_resource_category $category, int $totalrooms = 0) {
        $this->category = $category;
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
        $data->id = $this->category->get_id();
        $data->name = $this->category->get_name();
        $data->description = format_text($this->category->get_description() ?? '');
        $data->description_raw = $this->category->get_description() ?? '';
        $data->sortorder = $this->category->get_sortorder();
        $data->resources = [];

        $resources = resource_manager::get_all_resources($this->category->get_id());

        foreach ($resources as $resource) {
            $itemcard = new resource_item_card($resource, $this->totalrooms);
            $data->resources[] = $itemcard->export_for_template($output);
        }

        return $data;
    }
}
