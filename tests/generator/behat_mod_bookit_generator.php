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
 * Behat data generator for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Behat data generator for mod_bookit
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_bookit_generator extends behat_generator_base {
    /**
     * Define the entities that can be created.
     * @return array[]
     */
    final protected function get_creatable_entities(): array {
        return [
                'events' => [ // Refers to 'Given the following "mod_bookit > events" exist'.
                    'datagenerator' => 'event', // Refers to create_event() method in the generator class.
                    'required' => ['name', 'startdate', 'enddate', 'bookingstatus', 'institution'],
                ],
        ];
    }
}
