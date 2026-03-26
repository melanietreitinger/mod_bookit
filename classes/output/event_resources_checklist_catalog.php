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
 * Event resources checklist catalog output class.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\output;

use mod_bookit\local\entity\resource\bookit_resource_status;
use mod_bookit\local\manager\event_resource_manager;
use mod_bookit\local\manager\resource_settings_manager;
use renderer_base;
use renderable;
use templatable;
use stdClass;

/**
 * Event resources checklist catalog output class.
 *
 * Prepares data for the event_resources_checklist_catalog template.
 */
class event_resources_checklist_catalog implements renderable, templatable {
    /** @var int Event ID */
    private int $eventid;

    /** @var int Course module ID */
    private int $cmid;

    /** @var bool Whether current user can manage */
    private bool $canmanage;

    /** @var stdClass Event record */
    private stdClass $event;

    /**
     * Constructor.
     *
     * @param int $eventid Event ID
     * @param int $cmid Course module ID
     * @param bool $canmanage Whether current user can manage checklist
     * @param stdClass $event Event database record
     */
    public function __construct(int $eventid, int $cmid, bool $canmanage, stdClass $event) {
        $this->eventid = $eventid;
        $this->cmid = $cmid;
        $this->canmanage = $canmanage;
        $this->event = $event;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $DB;

        $data = new stdClass();
        $data->eventid = $this->eventid;
        $data->cmid = $this->cmid;
        $data->canmanage = (int)$this->canmanage;
        $data->contextid = \context_system::instance()->id;
        $data->eventname = format_string($this->event->name);
        $data->starttime = !empty($this->event->starttime) ? userdate($this->event->starttime) : '';
        $data->endtime = !empty($this->event->endtime) ? userdate($this->event->endtime) : '';

        // Room name.
        $data->roomname = '';
        if (!empty($this->event->roomid)) {
            $room = $DB->get_record('bookit_room', ['id' => $this->event->roomid], 'name');
            $data->roomname = $room ? format_string($room->name) : '';
        }

        // Booking status label.
        $statusmap = [
            0 => get_string('event_bookingstatus_0', 'mod_bookit'),
            1 => get_string('event_bookingstatus_1', 'mod_bookit'),
            2 => get_string('event_bookingstatus_2', 'mod_bookit'),
            3 => get_string('event_bookingstatus_3', 'mod_bookit'),
            4 => get_string('event_bookingstatus_4', 'mod_bookit'),
        ];
        $bookingstatus = (int)($this->event->bookingstatus ?? 0);
        $data->bookingstatus = $statusmap[$bookingstatus] ?? '';

        $eventresources = event_resource_manager::get_resources_for_event($this->eventid);

        // Track progress: confirmed / total.
        $totalcount = 0;
        $confirmedcount = 0;

        // Group items by category.
        $categoriesmap = [];

        foreach ($eventresources as $eventresource) {
            $resource = $DB->get_record('bookit_resource', ['id' => $eventresource->get_resourceid()]);
            if (!$resource) {
                continue;
            }

            $categoryid = (int)($resource->categoryid ?? 0);
            if ($categoryid > 0) {
                $category = $DB->get_record('bookit_resource_category', ['id' => $categoryid]);
                $categoryname = $category ? format_string($category->name) : '';
            } else {
                $categoryname = '';
            }

            if (!isset($categoriesmap[$categoryid])) {
                $categoriesmap[$categoryid] = [
                    'id'    => $categoryid,
                    'name'  => $categoryname,
                    'items' => [],
                ];
            }

            // Compute due date from checklist item relative to event start time.
            $checklistitem = resource_settings_manager::get_checklist_item_by_resource($eventresource->get_resourceid());
            $duedate = '';
            if ($checklistitem && $checklistitem->get_duedate()) {
                $duedatetype = $checklistitem->get_duedatetype();
                $rawduedate = $checklistitem->get_duedate();
                $dateformat = get_string('strftimedate', 'langconfig');
                if ($duedatetype === 'before_event' && !empty($this->event->starttime)) {
                    $duetimestamp = (int)$this->event->starttime - (int)$rawduedate;
                    $duedate = userdate($duetimestamp, $dateformat);
                } else if ($duedatetype === 'after_event' && !empty($this->event->endtime)) {
                    $duetimestamp = (int)$this->event->endtime + (int)$rawduedate;
                    $duedate = userdate($duetimestamp, $dateformat);
                } else if ($rawduedate > 0) {
                    $duedate = userdate($rawduedate, $dateformat);
                }
            }

            // Available amount from resource (max available).
            $availableamount = (int)$resource->amount;
            $amountirrelevant = (bool)$resource->amountirrelevant;

            $status = $eventresource->get_status();

            $itemdata = new stdClass();
            $itemdata->id               = $eventresource->get_id();
            $itemdata->resourceid       = $eventresource->get_resourceid();
            $itemdata->resourcename     = format_string($resource->name);
            $itemdata->categoryid       = $categoryid;
            $itemdata->amount           = $eventresource->get_amount();
            $itemdata->availableamount  = $availableamount;
            $itemdata->amountirrelevant = $amountirrelevant;
            $itemdata->status           = $status->value;
            $itemdata->duedate          = $duedate;
            $itemdata->canmanage        = (int)$this->canmanage;
            $itemdata->isrequested      = ($status === bookit_resource_status::REQUESTED);
            $itemdata->isconfirmed      = ($status === bookit_resource_status::CONFIRMED);
            $itemdata->isinprogress     = ($status === bookit_resource_status::INPROGRESS);
            $itemdata->isrejected       = ($status === bookit_resource_status::REJECTED);

            $totalcount++;
            if ($status === bookit_resource_status::CONFIRMED) {
                $confirmedcount++;
            }

            $categoriesmap[$categoryid]['items'][] = $itemdata;
        }

        $data->categories = array_values($categoriesmap);
        $data->hasresources = !empty($data->categories);

        // Progress bar data.
        $data->progresstotal     = $totalcount;
        $data->progressconfirmed = $confirmedcount;
        $data->progresspercent   = $totalcount > 0 ? (int)round(($confirmedcount / $totalcount) * 100) : 0;
        $data->progresscomplete  = ($totalcount > 0 && $confirmedcount === $totalcount);

        return $data;
    }
}
