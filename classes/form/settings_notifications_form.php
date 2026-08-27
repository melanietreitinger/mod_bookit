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
 * Form for the calendar admin settings.
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

use mod_bookit\local\install_helper;
use moodleform;

/**
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * @SuppressWarnings(PHPMD)
 */
class settings_notifications_form extends moodleform {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /**
     * Define the form
     */
    public function definition(): void {
        $mform =& $this->_form;

        $mform->addElement(
            'header',
            'bookingstatus_notifications_heading',
            get_string('bookingstatus_notifications_heading', 'mod_bookit')
        );

        $mform->addElement(
            'html',
            \html_writer::div(
                get_string('bookingstatus_notifications_desc', 'mod_bookit'),
                'mt-3 mb-3 ml-1'
            )
        );

        $mform->addElement(
            'text',
            'bookingstatus_service_addresses',
            get_string('bookingstatus_service_addresses', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/bookingstatus_service_addresses</code>',
            ['size' => 64]
        );
        $mform->setType('bookingstatus_service_addresses', PARAM_TEXT);

        $mform->addElement(
            'static',
            'bookingstatus_service_addresses_desc',
            '',
            get_string('bookingstatus_service_addresses_desc', 'mod_bookit')
        );

        $recipients = [
            'serviceteam',
            'bookingperson',
            'personincharge',
            'otherexaminers',
        ];
        foreach ($recipients as $recipient) {
            $elementname = 'bookingstatus_notify_' . $recipient;
            $mform->addElement(
                'select',
                $elementname,
                get_string($elementname, 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $elementname . '</code>',
                [
                    0 => get_string('no'),
                    1 => get_string('yes'),
                ]
            );
            $mform->setDefault($elementname, 1);
            $mform->addElement(
                'static',
                $elementname . '_desc',
                '',
                get_string($elementname . '_desc', 'mod_bookit')
            );
        }

        foreach (install_helper::get_booking_status_notification_statuses() as $statuskey => $statusdata) {
            $mform->addElement(
                'header',
                'bookingstatus_heading_' . $statuskey,
                get_string('event_bookingstatus_' . $statusdata['id'], 'mod_bookit')
            );

            $mform->addElement(
                'select',
                $statusdata['enabledconfig'],
                get_string($statusdata['enabledstring'], 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $statusdata['enabledconfig'] . '</code>',
                [
                    0 => get_string('no'),
                    1 => get_string('yes'),
                ]
            );
            $mform->setDefault($statusdata['enabledconfig'], 1);

            $subjectsettingname = install_helper::get_booking_status_notification_template_config_key(
                $statuskey,
                'subject'
            );
            $mform->addElement(
                'text',
                $subjectsettingname,
                get_string($statusdata['subjectstring'], 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $subjectsettingname . '</code>',
                ['size' => 64]
            );
            $mform->setType($subjectsettingname, PARAM_TEXT);
            $mform->setDefault(
                $subjectsettingname,
                install_helper::get_booking_status_notification_default_subject($statuskey)
            );
            $mform->hideIf($subjectsettingname, $statusdata['enabledconfig'], 'eq', 0);
            $subjectdescriptionname = $subjectsettingname . '_desc';
            $mform->addElement(
                'static',
                $subjectdescriptionname,
                '',
                get_string('bookingstatus_subject_desc', 'mod_bookit')
            );
            $mform->hideIf($subjectdescriptionname, $statusdata['enabledconfig'], 'eq', 0);

            $bodysettingname = install_helper::get_booking_status_notification_template_config_key(
                $statuskey,
                'body'
            );
            $mform->addElement(
                'textarea',
                $bodysettingname,
                get_string($statusdata['bodystring'], 'mod_bookit') . '<br>' .
                    '<code class="text-muted small">mod_bookit/' . $bodysettingname . '</code>',
                ['rows' => 8, 'cols' => 80]
            );
            $mform->setType($bodysettingname, PARAM_RAW);
            $mform->setDefault(
                $bodysettingname,
                install_helper::get_booking_status_notification_default_body($statuskey)
            );
            $mform->hideIf($bodysettingname, $statusdata['enabledconfig'], 'eq', 0);
            $bodydescriptionname = $bodysettingname . '_desc';
            $mform->addElement(
                'static',
                $bodydescriptionname,
                '',
                get_string('bookingstatus_body_desc', 'mod_bookit')
            );
            $mform->hideIf($bodydescriptionname, $statusdata['enabledconfig'], 'eq', 0);
        }

        $this->add_action_buttons();
    }
}
