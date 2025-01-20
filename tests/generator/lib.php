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
 * Data generator for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_bookit\local\entity\bookit_event;

class mod_bookit_generator extends testing_module_generator {

    public function create_event($event) {
        //global $DB;
        //$DB->insert_record('bookit_event', $event);
        $e = new bookit_event(
                0,
                $event['name'],
                20241,
                $event['department'],
                strtotime($event['startdate']),
                strtotime($event['enddate']),
                90,
                rand(20, 250),
                1,
                '',
                $event['bookingstatus'],
                2,
                '',
                0,
                'External lorem ipsum',
                'Internal Lorem Ipsum dolor...',
                'Susi Support',
                [
                        (object) ['resourceid' => rand(1, 5), 'amount' => 1], // Rooms.
                        (object) ['resourceid' => rand(6, 10), 'amount' => rand(2, 85)], // Other resources.
                        (object) ['resourceid' => rand(6, 10), 'amount' => rand(2, 85)], // Other resources.
                ],
                null,
                2,
                time(),
                time()
        );

        $e->save(2);
    }
}
