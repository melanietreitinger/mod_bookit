<?php

namespace mod_bookit\local\manager;

class categories_manager {

    public static function get_categories() {
        return [
                ['name' => 'Rooms',
                        'description' => 'Specific rooms for e-assessments',
                        'id' => 1,
                        'resources' => [
                                ['id' => 1,
                                        'name' => 'E-Assessment-Center - Room 1',
                                        'description' => 'Room capacity: 168 seats',
                                        'amount' => 1],
                                ['id' => 2,
                                        'name' => 'E-Assessment-Center - Room 2',
                                        'description' => 'Room capacity: 85 seats',
                                        'amount' => 1],
                                ['id' => 3,
                                        'name' => 'E-Assessment-Center - Room 3',
                                        'description' => 'Room capacity: 7 seats',
                                        'amount' => 1]
                        ]],
                ['name' => 'Hardware',
                        'description' => 'Specific hardware for e-assessments',
                        'id' => 2,
                        'resources' => [
                                ['id' => 4,
                                        'name' => 'Headphones',
                                        'description' => 'Sennheiser ULTRASOUND 2000 XXL',
                                        'amount' => 270],
                                ['id' => 5,
                                        'name' => 'Keyboard',
                                        'description' => 'Typemaster 1337 Haxx0r',
                                        'amount' => 270]
                        ]],
                ['name' => 'Magic Creatures',
                        'description' => 'For a little magic...',
                        'id' => 3,
                        'resources' => [
                                ['id' => 6,
                                        'name' => 'Unicorn',
                                        'description' => 'Just fabulous!',
                                        'amount' => 270],
                                ['id' => 7,
                                        'name' => 'Moodlicorn',
                                        'description' => 'Just fabulous aaand magic!',
                                        'amount' => 270],
                                ['id' => 8,
                                        'name' => 'Fairy',
                                        'description' => 'For extra luck and glitter.',
                                        'amount' => 270]
                        ]],
        ];
    }
}