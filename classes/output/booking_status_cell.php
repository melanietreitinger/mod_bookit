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

use context_module;
use mod_bookit\local\entity\resource\bookit_resource_status;
use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;
use renderer_base;
use renderable;
use stdClass;
use templatable;

/**
 * Shared booking / resource status table cell.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking_status_cell implements renderable, templatable {
    /** @var string */
    private const MODE_BOOKING = 'booking';

    /** @var string */
    private const MODE_RESOURCE = 'resource';

    /** @var string */
    private $mode;

    /** @var bool */
    private $canedit;

    /** @var string|int */
    private $currentvalue;

    /** @var string */
    private $statustext;

    /** @var string */
    private $statusclass;

    /** @var string */
    private $statusgroupkey;

    /** @var string */
    private $statusgrouptext;

    /** @var string */
    private $statusstyle;

    /** @var array */
    private $options;

    /** @var int */
    private $eventid;

    /** @var int */
    private $cmid;

    /** @var int */
    private $itemid;

    /** @var string */
    private $updateaction;

    /** @var bool */
    private $showhistorysummary;

    /** @var string */
    private $latesthistorysummary;

    /** @var bool */
    private $showhistorydetails;

    /** @var string */
    private $historydetailslabel;

    /** @var string */
    private $historydetailsempty;

    /** @var bool */
    private $hashistoryentries;

    /** @var array */
    private $historyentries;

    /** @var string */
    private $requesttab;

    /**
     * Constructor.
     *
     * @param string $mode booking|resource
     * @param bool $canedit
     * @param string|int $currentvalue
     * @param string $statustext
     * @param string $statusclass
     * @param string $statusgroupkey
     * @param string $statusgrouptext
     * @param string $statusstyle
     * @param array $options
     * @param array $identifiers
     * @param array $history
     */
    private function __construct(
        string $mode,
        bool $canedit,
        $currentvalue,
        string $statustext,
        string $statusclass,
        string $statusgroupkey,
        string $statusgrouptext,
        string $statusstyle,
        array $options,
        array $identifiers,
        array $history
    ) {
        $this->mode = $mode;
        $this->canedit = $canedit;
        $this->currentvalue = $currentvalue;
        $this->statustext = $statustext;
        $this->statusclass = $statusclass;
        $this->statusgroupkey = $statusgroupkey;
        $this->statusgrouptext = $statusgrouptext;
        $this->statusstyle = $statusstyle;
        $this->options = $options;
        $this->eventid = (int)($identifiers['eventid'] ?? 0);
        $this->cmid = (int)($identifiers['cmid'] ?? 0);
        $this->itemid = (int)($identifiers['itemid'] ?? 0);
        $this->updateaction = (string)($identifiers['updateaction'] ?? '');
        $this->requesttab = (string)($identifiers['requesttab'] ?? '');
        $this->showhistorysummary = (bool)($history['showsummary'] ?? false);
        $this->latesthistorysummary = (string)($history['latestsummary'] ?? '');
        $this->showhistorydetails = (bool)($history['showdetails'] ?? false);
        $this->historydetailslabel = (string)($history['detailslabel'] ?? '');
        $this->historydetailsempty = (string)($history['detailsempty'] ?? '');
        $this->hashistoryentries = (bool)($history['hasentries'] ?? false);
        $this->historyentries = $history['entries'] ?? [];
    }

    /**
     * Build a booking-mode cell for an overview row.
     *
     * @param stdClass $event
     * @param context_module $context
     * @param int $userid
     * @param int $cmid
     * @param bool $canmanagebasics
     * @param bool $isrequestworkspace
     * @param string $requesttab
     * @param string $latesthistorysummary
     * @param array $historyentries
     * @return self
     */
    public static function for_booking_overview_row(
        stdClass $event,
        context_module $context,
        int $userid,
        int $cmid,
        bool $canmanagebasics,
        bool $isrequestworkspace,
        string $requesttab,
        string $latesthistorysummary = '',
        array $historyentries = []
    ): self {
        $bookingstatus = (int)($event->bookingstatus ?? 0);
        $colors = event_manager::get_booking_status_colors();
        $statusbg = $colors[$bookingstatus]['bg'] ?? '#ffffff';
        $statusfg = $colors[$bookingstatus]['fg'] ?? '#000000';
        $statusgroupkey = event_manager::get_booking_status_group_key($bookingstatus);

        $canedit = false;
        if ($isrequestworkspace) {
            if ($requesttab === 'openrequests') {
                $canedit = event_access_manager::can_manage_open_requests($context);
            } else if ($requesttab === 'rejectedcancelled' || $requesttab === 'confirmedrequests') {
                $canedit = false;
            } else if (in_array($requesttab, ['allrequests', 'history'], true)) {
                $canedit = $canmanagebasics;
            }
        } else if ($requesttab === 'history') {
            $canedit = false;
        } else {
            $canedit = $canmanagebasics;
        }

        $options = [];
        if ($canedit) {
            $options = event_manager::get_booking_status_options($bookingstatus);
            if ($isrequestworkspace && $requesttab === 'openrequests') {
                $options = array_values(array_filter(
                    $options,
                    static fn(array $option): bool => (int)$option['value'] !== event_access_manager::BOOKINGSTATUS_CANCELED
                ));
            }
            if (empty($options)) {
                $canedit = false;
            }
        }

        return new self(
            self::MODE_BOOKING,
            $canedit,
            $bookingstatus,
            event_manager::get_booking_status_label($bookingstatus),
            event_manager::get_booking_status_class($bookingstatus),
            $statusgroupkey,
            event_manager::get_booking_status_group_label($bookingstatus),
            "background-color:$statusbg;color:$statusfg;",
            $options,
            [
                'eventid' => (int)$event->id,
                'cmid' => $cmid,
                'updateaction' => 'update-booking-status',
                'requesttab' => $requesttab,
            ],
            [
                'showsummary' => $latesthistorysummary !== '',
                'latestsummary' => $latesthistorysummary,
                'showdetails' => $isrequestworkspace,
                'detailslabel' => get_string('overview_workflow_history', 'mod_bookit'),
                'detailsempty' => get_string('overview_workflow_history_empty', 'mod_bookit'),
                'hasentries' => !empty($historyentries),
                'entries' => $historyentries,
            ]
        );
    }

    /**
     * Build a resource-mode cell for an event-resources checklist row.
     *
     * @param stdClass $itemdata Checklist row export object.
     * @param bool $canmanage
     * @return self
     */
    public static function for_resource_row(stdClass $itemdata, bool $canmanage): self {
        $statusvalue = (string)($itemdata->status ?? bookit_resource_status::REQUESTED->value);
        $label = event_manager::get_resource_status_label($statusvalue);
        $palette = self::resource_status_palette();
        $color = $palette[$statusvalue] ?? ['bg' => '#e2e3e5', 'fg' => '#212529', 'class' => 'bookit-resource-status-requested'];
        $groupkey = match ($statusvalue) {
            bookit_resource_status::CONFIRMED->value => 'confirmed',
            bookit_resource_status::REJECTED->value => 'closed',
            default => 'open',
        };

        $options = [];
        if ($canmanage) {
            foreach (bookit_resource_status::cases() as $statuscase) {
                $value = $statuscase->value;
                $optioncolors = $palette[$value] ?? $color;
                $options[] = [
                    'value' => $value,
                    'label' => event_manager::get_resource_status_label($value),
                    'selected' => $value === $statusvalue,
                    'bg' => $optioncolors['bg'],
                    'fg' => $optioncolors['fg'],
                ];
            }
        }

        return new self(
            self::MODE_RESOURCE,
            $canmanage && !empty($options),
            $statusvalue,
            $label,
            $color['class'],
            $groupkey,
            get_string('overview_status_group_' . $groupkey, 'mod_bookit'),
            'background-color:' . $color['bg'] . ';color:' . $color['fg'] . ';',
            $options,
            [
                'eventid' => (int)($itemdata->eventid ?? 0),
                'cmid' => (int)($itemdata->cmid ?? 0),
                'itemid' => (int)($itemdata->id ?? 0),
                'updateaction' => 'update-status',
                'requesttab' => '',
            ],
            []
        );
    }

    /**
     * Export data for Mustache.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->mode = $this->mode;
        $data->canedit = $this->canedit;
        $data->showoptions = $this->canedit && !empty($this->options);
        $data->currentvalue = $this->currentvalue;
        $data->statustext = $this->statustext;
        $data->statusclass = $this->statusclass;
        $data->statusgroupkey = $this->statusgroupkey;
        $data->statusgrouptext = $this->statusgrouptext;
        $data->statusstyle = $this->statusstyle;
        $data->options = array_values($this->options);
        $data->eventid = $this->eventid;
        $data->cmid = $this->cmid;
        $data->itemid = $this->itemid;
        $data->bookingstatus = $this->mode === self::MODE_BOOKING ? (int)$this->currentvalue : 0;
        $data->resourcestatus = $this->mode === self::MODE_RESOURCE ? (string)$this->currentvalue : '';
        $data->updateaction = $this->updateaction;
        $data->requesttab = $this->requesttab;
        $data->cellclasses = 'align-middle py-3 mod-bookit-status-cell';
        $data->showhistorysummary = $this->showhistorysummary;
        $data->latesthistorysummary = $this->latesthistorysummary;
        $data->showhistorydetails = $this->showhistorydetails;
        $data->historydetailslabel = $this->historydetailslabel;
        $data->historydetailsempty = $this->historydetailsempty;
        $data->hashistoryentries = $this->hashistoryentries;
        $data->historyentries = $this->historyentries;
        return $data;
    }

    /**
     * Resource status colour palette.
     *
     * @return array
     */
    private static function resource_status_palette(): array {
        return [
            bookit_resource_status::REQUESTED->value => [
                'bg' => '#e2e3e5',
                'fg' => '#212529',
                'class' => 'bookit-resource-status-requested',
            ],
            bookit_resource_status::CONFIRMED->value => [
                'bg' => '#d1e7dd',
                'fg' => '#0f5132',
                'class' => 'bookit-resource-status-confirmed',
            ],
            bookit_resource_status::INPROGRESS->value => [
                'bg' => '#fff3cd',
                'fg' => '#664d03',
                'class' => 'bookit-resource-status-inprogress',
            ],
            bookit_resource_status::REJECTED->value => [
                'bg' => '#f8d7da',
                'fg' => '#842029',
                'class' => 'bookit-resource-status-rejected',
            ],
        ];
    }
}
