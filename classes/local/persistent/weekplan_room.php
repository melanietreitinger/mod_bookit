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
 * Class for loading/storing weekplan assignments to rooms from the DB.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\persistent;

use core\persistent;

/**
 * Class for loading/storing weekplan assignments to rooms from the DB.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class weekplan_room extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'bookit_weekplan_room';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'weekplanid' => [
                'type' => PARAM_INT,
            ],
            'roomid' => [
                'type' => PARAM_INT,
            ],
            'starttime' => [
                'type' => PARAM_INT,
            ],
            'endtime' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Check if this weekplan_room assignment collides with any other.
     * @return bool
     */
    public function check_for_collision() {
        global $DB;

        $sql = 'SELECT * FROM {bookit_weekplan_room} ' .
            'WHERE starttime <= :endtime AND endtime >= :starttime AND roomid = :roomid';
        $params = [
            'starttime' => $this->get('starttime'),
            'endtime' => $this->get('endtime'),
            'roomid' => $this->get('roomid'),
        ];
        if ($this->get('id')) {
            $sql .= ' AND id != :id';
            $params['id'] = $this->get('id');
        }

        return $DB->record_exists_sql($sql, $params);
    }

}
