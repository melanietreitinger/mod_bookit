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

use mod_bookit\local\entity\bookit_event;

/**
 * Data generator for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_bookit_generator extends testing_module_generator {
    /**
     * Create a new event.
     * @param array $event
     * @return void
     * @throws dml_exception
     */
    final public function create_event(array $event) {
        $e = new bookit_event(
            0,
            $event['name'],
            20241,
            $event['institution'],
            strtotime($event['startdate']),
            strtotime($event['enddate']),
            90,
            1,
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
            15,
            15,
            null,
            2,
            time(),
            time(),
            [
                    (object) ['resourceid' => rand(1, 5), 'amount' => 1], // Rooms.
                    (object) ['resourceid' => rand(6, 7), 'amount' => rand(2, 85)], // Other resources.
                    (object) ['resourceid' => rand(8, 10), 'amount' => rand(2, 85)], // Other resources.
                ],
        );

        $e->save(2);
    }
}
