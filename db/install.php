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
 * Post-install script.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\entity\category;

/**
 * This function is executed after the installation of the plugin.
 * @return void
 */
function xmldb_bookit_install() {
    global $DB;

    $category = new category('Rooms', 'Examrooms');
    $category->save(2);

    $resources = [];
    $events = [];

    $subjects = ['Mathematics', 'Physics', 'Chemistry', 'Biology', 'Computer Science', 'History', 'Geography', 'English Literature',
            'Psychology', 'Sociology'];

    for ($i = 1; $i <= 10; $i++) {
        $resource = new \mod_bookit\local\entity\resource('Exam room ' . $i, 'Capacity 255 seats', 1, $category->id);
        $resource->save(2);
        $resources[] = $resource;

        // Generate random date and time in the current week between 07:00 and 20:00.
        // Changed from 0-6 to 0-5 to exclude Sundays.
        $dayofweek = rand(0, 5);
        $hour = rand(7, 20);
        $minute = rand(0, 59);
        $second = rand(0, 59);
        $startdate = strtotime("last Monday +$dayofweek days $hour:$minute:$second");
        // Add 2 hours to start date.
        $enddate = $startdate + 7200;

        // Select a random subject.
        $subject = $subjects[array_rand($subjects)];

        $event = new \mod_bookit\local\entity\event(
                'Exam ' . $subject,
                20241,
                'IT',
                $startdate,
                $enddate,
                90,
                85,
                '3 Zeitverlängerungen',
                1,
                2,
                'Prof. Superprof',
                'superprof@example.com',
                4,
                'Internal lorem ipsum',
                'Lorem Ipsum dolor...',
                'Susi Support',
                [
                        (object) ['resourceid' => $resource->id, 'amount' => 2],
                ],
                null,
                2
        );
        $event->save(2);
        $events[] = $event;
    }
}
