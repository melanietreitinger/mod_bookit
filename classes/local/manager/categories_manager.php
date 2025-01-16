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
        global $DB;

        // Hole alle Ressourcen-Kategorien aus der Datenbank
        $categories = $DB->get_records('bookit_resource_categories');
        $result = [];

        foreach ($categories as $category) {
            $categoryData = [
                'name' => $category->name,
                'description' => $category->description,
                'id' => $category->id,
                'resources' => []
            ];

            // Hole alle Ressourcen für diese Kategorie
            $resources = $DB->get_records('bookit_resource', ['categoryid' => $category->id]);
            foreach ($resources as $resource) {
                $categoryData['resources'][] = [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'description' => $resource->description,
                    'amount' => $resource->amount
                ];
            }

            $result[] = $categoryData;
        }

        return $result;
    }
}
