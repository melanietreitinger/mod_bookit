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
 * Validation tests for master checklist item form (Spec 042 / FEED2-015).
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form\masterchecklist;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/form/classes/dynamic_form.php');

/**
 * Tests for edit_checklist_item_form::validation().
 *
 * @covers \mod_bookit\local\form\masterchecklist\edit_checklist_item_form::validation
 */
final class edit_checklist_item_form_validation_test extends advanced_testcase {
    /**
     * Build a form instance with definition loaded.
     *
     * @return edit_checklist_item_form
     */
    private function create_form(): edit_checklist_item_form {
        $form = new edit_checklist_item_form();
        $form->definition();
        return $form;
    }

    /**
     * Minimal valid payload for validation checks.
     *
     * @return array
     */
    private function valid_data(): array {
        return [
            'title' => 'Sample checklist item',
            'categoryid' => 1,
            'roomids' => [1],
            'roleids' => [1],
            'duedate' => 'none',
        ];
    }

    /**
     * Empty title returns field-specific message.
     */
    public function test_validation_requires_title(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $data = $this->valid_data();
        $data['title'] = '   ';

        $errors = $this->create_form()->validation($data, []);

        $this->assertArrayHasKey('title', $errors);
        $this->assertEquals(
            get_string('checklistitemname_required', 'mod_bookit'),
            $errors['title']
        );
    }

    /**
     * Empty category returns field-specific message.
     */
    public function test_validation_requires_category(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $data = $this->valid_data();
        $data['categoryid'] = 0;

        $errors = $this->create_form()->validation($data, []);

        $this->assertArrayHasKey('categoryid', $errors);
        $this->assertEquals(
            get_string('checklistcategory_required', 'mod_bookit'),
            $errors['categoryid']
        );
    }

    /**
     * Empty rooms multi-select returns field-specific message.
     */
    public function test_validation_requires_rooms(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $data = $this->valid_data();
        $data['roomids'] = [];

        $errors = $this->create_form()->validation($data, []);

        $this->assertArrayHasKey('roomids', $errors);
        $this->assertEquals(
            get_string('checklistrooms_required', 'mod_bookit'),
            $errors['roomids']
        );
    }
}
