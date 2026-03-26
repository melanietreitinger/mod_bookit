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
 * Read-only form displaying booked resources and their status for an event.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form\resource;

use mod_bookit\local\entity\resource\bookit_resource_status;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Read-only view of booked resources with their current status.
 *
 * Extends moodleform (not dynamic_form) so it can be rendered standalone.
 * All elements are static; there is no submit button.
 */
class view_event_resources_form extends \moodleform {
    /**
     * Form definition: adds one static element per booked resource, grouped by category.
     */
    public function definition() {
        $mform = $this->_form;

        $bookedresources = $this->_customdata['bookedresources'] ?? [];
        $resourcesdata   = $this->_customdata['resourcesdata'] ?? [];

        $statusclassmap = [
            bookit_resource_status::REQUESTED->value  => 'badge-secondary',
            bookit_resource_status::CONFIRMED->value  => 'badge-success',
            bookit_resource_status::INPROGRESS->value => 'badge-primary',
            bookit_resource_status::REJECTED->value   => 'badge-danger',
        ];

        foreach ($resourcesdata as $categorygroup) {
            $category  = $categorygroup['category'];
            $resources = $categorygroup['resources'];

            // Skip categories where no resource was booked.
            $hasbooked = false;
            foreach ($resources as $resource) {
                if (array_key_exists($resource['id'], $bookedresources)) {
                    $hasbooked = true;
                    break;
                }
            }
            if (!$hasbooked) {
                continue;
            }

            $mform->addElement('header', 'header_cat_' . $category['id'], $category['name']);
            $mform->setExpanded('header_cat_' . $category['id'], true);

            foreach ($resources as $resource) {
                if (!array_key_exists($resource['id'], $bookedresources)) {
                    continue;
                }

                $bookedinfo   = $bookedresources[$resource['id']];
                $bookedamount = $bookedinfo['amount'];
                $bookedstatus = $bookedinfo['status'];

                $badgeclass  = 'badge ' . ($statusclassmap[$bookedstatus] ?? 'badge-secondary');
                $statuslabel = get_string('resources:status_' . $bookedstatus, 'mod_bookit');
                $html        = '<span class="' . $badgeclass . '">' . $statuslabel . '</span>';

                if (!$resource['amountirrelevant']) {
                    $html .= '<span class="ms-3">'
                        . get_string('booking:resource_amount', 'mod_bookit')
                        . ': <strong>' . $bookedamount . '</strong></span>';
                }

                // Info icon with popover (description + max amount), mirroring the booking form.
                $labelhtml = s($resource['name']);
                $popoverparts = [];
                if (!empty($resource['description'])) {
                    $popoverparts[] = s($resource['description']);
                }
                if (!$resource['amountirrelevant'] && $resource['amount'] > 0) {
                    $popoverparts[] = get_string('booking:resource_max', 'mod_bookit', $resource['amount']);
                }
                if (!empty($popoverparts)) {
                    $popovercontent = implode('<br>', $popoverparts);
                    $labelhtml .= ' <a class="btn btn-link p-0 ms-1 icon-no-margin" role="button" tabindex="0"'
                        . ' data-container="body" data-toggle="popover"'
                        . ' data-placement="right" data-content="' . $popovercontent . '"'
                        . ' data-html="true" data-trigger="focus"'
                        . ' aria-label="' . get_string('resources:info', 'mod_bookit') . '">'
                        . '<i class="fa fa-info-circle text-info"></i>'
                        . '</a>';
                }

                $mform->addElement('static', 'resourcestatus_' . $resource['id'], $labelhtml, $html);
            }
        }

        // No submit button – this form is view-only.
    }

    /**
     * No validation needed for a read-only form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        return [];
    }
}
