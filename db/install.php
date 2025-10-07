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
use mod_bookit\local\entity\bookit_event;
use mod_bookit\local\entity\bookit_resource_categories;
use mod_bookit\local\entity\bookit_resource;
use mod_bookit\local\install_helper;
/**
 * This function is executed after the installation of the plugin.
 * @return void
 */
function xmldb_bookit_install() {
    global $CFG;

    if (defined('BEHAT_SITE_RUNNING') || PHPUNIT_TEST) {
        return;
    }

    // Load install helper for checklist data.
    require_once($CFG->dirroot . '/mod/bookit/classes/local/install_helper.php');

    // Create categories and resources.
    $category = new bookit_resource_categories('Rooms', 'Examrooms');
    $category->save(2);
    for ($i = 1; $i <= 5; $i++) {
        $resource = new bookit_resource('Exam room ' . $i, 'Capacity ' . rand(20, 255) . ' seats', 1, $category->id);
        $resource->save(2);
    }

    $category = new bookit_resource_categories('Hardware', 'Hardware Resources');
    $category->save(2);
    $resource = new bookit_resource('Keyboard', 'Cherry Ultra Silent', 255, $category->id);
    $resource->save(2);
    $resource = new bookit_resource('Headphone', 'Sennheiser Best Listening', 177, $category->id);
    $resource->save(2);

    $category = new bookit_resource_categories('Magic Creatures', 'For a little magic...');
    $category->save(2);
    $resource = new bookit_resource('Unicorn', 'Rainbow colored unicorns', 13, $category->id);
    $resource->save(2);
    $resource = new bookit_resource('Moodlicorn', 'Just fabulous aaand magic!', 99, $category->id);
    $resource->save(2);
    $resource = new bookit_resource('Fairy', 'For extra luck and glitter!', 199, $category->id);
    $resource->save(2);

    set_config('installhelperfinished', 0, 'mod_bookit');
}
