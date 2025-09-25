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
 * Class for loading/storing blockers from the DB.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\persistent;

use core\persistent;
use mod_bookit\local\timeline;

/**
 * Class for loading/storing blockers from the DB.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blocker extends persistent {
    /** Table name for the persistent. */
    const TABLE = 'bookit_blocker';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'starttime' => [
                'type' => PARAM_INT,
            ],
            'endtime' => [
                'type' => PARAM_INT,
            ],
            'roomid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Returns all blockers for the room in the specified timeframe.
     * @param int $roomid
     * @param string $starttime
     * @param string $endtime
     * @return blocker[]
     */
    public static function get_blockers_for_room(int $roomid, string $starttime, string $endtime): array {
        return self::get_records_select(
            '(roomid = :roomid OR roomid IS NULL) AND endtime >= :starttime AND starttime <= :endtime',
            [
                'roomid' => $roomid,
                'starttime' => $starttime,
                'endtime' => $endtime,
            ]
        );
    }
}
