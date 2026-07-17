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

namespace mod_bookit\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Request workspace navigation label with open-request badge counts.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request_workspace_nav_label implements renderable, templatable {
    /** @var int */
    private int $newcount;

    /** @var int */
    private int $inprogresscount;

    /**
     * Constructor.
     *
     * @param int $newcount
     * @param int $inprogresscount
     */
    public function __construct(int $newcount, int $inprogresscount) {
        $this->newcount = $newcount;
        $this->inprogresscount = $inprogresscount;
    }

    /**
     * Export data for the navigation label template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        return (object)[
            'label' => get_string('overview_request_workspace', 'mod_bookit'),
            'newcount' => $this->newcount,
            'inprogresscount' => $this->inprogresscount,
            'newbadgearialabel' => get_string('overview_nav_badge_new', 'mod_bookit', $this->newcount),
            'inprogressbadgearialabel' => get_string('overview_nav_badge_inprogress', 'mod_bookit', $this->inprogresscount),
        ];
    }
}
