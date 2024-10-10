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
 * Categories manager class.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\manager;

/**
 * Categories manager class.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categories_manager {

    /**
     * Get categories.
     * @return array[]
     */
    public static function get_categories() {
        return [
                ['name' => 'Rooms',
                        'description' => 'Specific rooms for e-assessments',
                        'id' => 1,
                        'resources' => [
                                ['id' => 1,
                                        'name' => 'E-Assessment-Center - Room 1',
                                        'description' => 'Room capacity: 168 seats',
                                        'amount' => 1,
                                ],
                                ['id' => 2,
                                        'name' => 'E-Assessment-Center - Room 2',
                                        'description' => 'Room capacity: 85 seats',
                                        'amount' => 1,
                                ],
                                ['id' => 3,
                                        'name' => 'E-Assessment-Center - Room 3',
                                        'description' => 'Room capacity: 7 seats',
                                        'amount' => 1,
                                ],
                        ]],
                ['name' => 'Hardware',
                        'description' => 'Specific hardware for e-assessments',
                        'id' => 2,
                        'resources' => [
                                ['id' => 4,
                                        'name' => 'Headphones',
                                        'description' => 'Sennheiser ULTRASOUND 2000 XXL',
                                        'amount' => 270,
                                ],
                                ['id' => 5,
                                        'name' => 'Keyboard',
                                        'description' => 'Typemaster 1337 Haxx0r',
                                        'amount' => 270,
                                ],
                        ]],
                ['name' => 'Magic Creatures',
                        'description' => 'For a little magic...',
                        'id' => 3,
                        'resources' => [
                                ['id' => 6,
                                        'name' => 'Unicorn',
                                        'description' => 'Just fabulous!',
                                        'amount' => 270,
                                ],
                                ['id' => 7,
                                        'name' => 'Moodlicorn',
                                        'description' => 'Just fabulous aaand magic!',
                                        'amount' => 270,
                                ],
                                ['id' => 8,
                                        'name' => 'Fairy',
                                        'description' => 'For extra luck and glitter.',
                                        'amount' => 270,
                                ],
                        ]],
        ];
    }
}
