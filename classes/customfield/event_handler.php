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
 * Custom field handler for BookIt booking events (#97).
 *
 * @package     mod_bookit
 * @copyright   2026 Vadym Kuzyak, Humboldt-Universität zu Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\customfield;

use core_customfield\field_controller;

/**
 * Handler for admin-configurable, global booking-form fields.
 */
class event_handler extends \core_customfield\handler {
    /** @var self|null Singleton instance. */
    protected static $singleton;

    /**
     * Return the singleton handler.
     *
     * @param int $itemid
     * @return \core_customfield\handler
     */
    public static function create(int $itemid = 0): \core_customfield\handler {
        if (self::$singleton === null) {
            self::$singleton = new self(0);
        }
        return self::$singleton;
    }
    /**
     * Context in which the fields are configured (global -> system).
     *
     * @return \context
     */
    public function get_configuration_context(): \context {
        return \context_system::instance();
    }

    /**
     * URL of the field-management page.
     *
     * @return \moodle_url
     */
    public function get_configuration_url(): \moodle_url {
        return new \moodle_url('/mod/bookit/admin/customfield.php');
    }

    /**
     * Context of a single instance. Fields are global, so system context.
     *
     * @param int $instanceid
     * @return \context
     */
    public function get_instance_context(int $instanceid = 0): \context {
        return \context_system::instance();
    }

    /**
     * Who may configure the field definitions.
     *
     * @return bool
     */
    public function can_configure(): bool {
        return has_capability('moodle/site:config', $this->get_configuration_context());
    }

    /**
     * Who may edit a field value on an instance (form access is gated separately).
     *
     * @param field_controller $field
     * @param int $instanceid
     * @return bool
     */
    public function can_edit(field_controller $field, int $instanceid = 0): bool {
        return true;
    }

    /**
     * Who may view a field value on an instance.
     *
     * @param field_controller $field
     * @param int $instanceid
     * @return bool
     */
    public function can_view(field_controller $field, int $instanceid): bool {
        return true;
    }
}
