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
 * Class for loading/storing institutions from the DB.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\persistent;

use core\persistent;

/**
 * Class for loading/storing rooms from the DB.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class room extends persistent {
    /** Table name for the persistent. */
    const TABLE = 'bookit_room';

    /** @var int Constant for allowing free placement inside slots. */
    const MODE_FREE = 0;

    /** @var int Constant for only allowing events to start at starts of slots. */
    const MODE_SLOTS = 1;
    /** @var int */
    const OVERLAPPING_ALL = 0;
    /** @var int */
    const OVERLAPPING_ALLOW_NON_CONFIRMED = 1;
    /** @var int */
    const OVERLAPPING_NONE = 2;

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'shortname' => [
                'type' => PARAM_TEXT,
            ],
            'description' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
            ],
            'location' => [
                'type' => PARAM_TEXT,
            ],
            'eventcolor' => [
                'type' => PARAM_TEXT,
            ],
            'active' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
            'roommode' => [
                'type' => PARAM_INT,
                'choices' => [0, 1],
            ],
            'seats' => [
                'type' => PARAM_INT,
            ],
            'extratimebefore' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'extratimeafter' => [
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'preventoverlap' => [
                'type' => PARAM_INT,
                'choices' => [
                    self::OVERLAPPING_ALL,
                    self::OVERLAPPING_ALLOW_NON_CONFIRMED,
                    self::OVERLAPPING_NONE,
                ],
            ],
        ];
    }
}
