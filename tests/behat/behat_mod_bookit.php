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
 * Custom Behat step definitions for mod_bookit.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Behat\Mink\Exception\ExpectationException;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Custom Behat step definitions for mod_bookit.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_bookit extends behat_base {
    /**
     * Checks that the given resource row has the bookit-resource-disabled class (is greyed out).
     *
     * This is the primary regression check for the room filter: after selecting a room in the
     * booking form, resources not assigned to that room must receive the .bookit-resource-disabled
     * CSS class which sets opacity:0.4 and pointer-events:none.
     *
     * @Then the resource :name should be disabled in the booking form
     * @param string $name Visible resource name shown in the booking form
     * @throws ExpectationException
     */
    public function the_resource_should_be_disabled_in_the_booking_form(string $name): void {
        $this->assert_resource_state($name, true);
    }

    /**
     * Checks that the given resource row does NOT have the bookit-resource-disabled class (is enabled).
     *
     * @Then the resource :name should be enabled in the booking form
     * @param string $name Visible resource name shown in the booking form
     * @throws ExpectationException
     */
    public function the_resource_should_be_enabled_in_the_booking_form(string $name): void {
        $this->assert_resource_state($name, false);
    }

    /**
     * Assert whether a resource row in the booking form is disabled or enabled.
     *
     * Uses JavaScript to find the resource group row by its label text, then checks
     * whether the ancestor .fgroup container has the .bookit-resource-disabled class.
     *
     * @param string $name Resource label text.
     * @param bool $expectdisabled True to assert disabled, false to assert enabled.
     * @throws ExpectationException
     */
    private function assert_resource_state(string $name, bool $expectdisabled): void {
        // In Moodle 4.5/Boost, addGroup() renders the group label as a <p id="fgroup_id_..._label">.
        // The bookit-resource-disabled class is applied to the outer [id^="fgroup_id_resourcegroup_"] div.
        $js = <<<JS
            (function(resourceName) {
                var groups = document.querySelectorAll('[id^="fgroup_id_resourcegroup_"]');
                for (var i = 0; i < groups.length; i++) {
                    var labelEl = groups[i].querySelector('[id$="_label"]');
                    if (labelEl && labelEl.textContent.trim() === resourceName) {
                        return groups[i].classList.contains('bookit-resource-disabled') ? 'disabled' : 'enabled';
                    }
                }
                var found = Array.from(groups).map(function(g) {
                    var l = g.querySelector('[id$="_label"]');
                    return l ? l.textContent.trim() : '(no label)';
                });
                return 'not_found:labels=' + JSON.stringify(found);
            })('$name')
        JS;

        $result = $this->getSession()->evaluateScript($js);

        if (strpos($result, 'not_found') === 0) {
            throw new ExpectationException(
                "Resource \"$name\" was not found in the booking form. JS info: $result",
                $this->getSession()
            );
        }

        $isdisabled = ($result === 'disabled');
        if ($expectdisabled && !$isdisabled) {
            throw new ExpectationException(
                "Resource \"$name\" was expected to be disabled (greyed out) but it is enabled.",
                $this->getSession()
            );
        }
        if (!$expectdisabled && $isdisabled) {
            throw new ExpectationException(
                "Resource \"$name\" was expected to be enabled but it is disabled (greyed out).",
                $this->getSession()
            );
        }
    }

    /**
     * Selects an option from a named select field in the booking form.
     *
     * This step selects a room (or other option) from a Moodle select element identified
     * by its visible label. It is equivalent to the built-in "I select ... from the ... field"
     * but targets the Moodle form element by label text.
     *
     * @When I select :value from the :field field
     * @param string $value The option text to select.
     * @param string $field The visible label of the select field.
     */
    public function i_select_from_the_field(string $value, string $field): void {
        $selectnode = $this->find_field($field);
        $selectnode->selectOption($value);
    }

    // Resource drag-and-drop step definitions.

    /**
     * Drags a resource item to appear immediately after another resource item.
     *
     * Uses JavaScript to dispatch HTML5 drag events. Both items must be visible
     * on the resource catalog page. Waits 3 seconds after drag for reactive updates.
     *
     * @When I drag resource item :source after resource item :target
     * @param string $source Visible name of the item to drag.
     * @param string $target Visible name of the item to drop after.
     * @throws ExpectationException
     */
    public function i_drag_resource_item_after(string $source, string $target): void {
        $this->drag_resource_item($source, $target, false);
    }

    /**
     * Drags a resource item to appear immediately before another resource item.
     *
     * @When I drag resource item :source before resource item :target
     * @param string $source Visible name of the item to drag.
     * @param string $target Visible name of the item to drop before.
     * @throws ExpectationException
     */
    public function i_drag_resource_item_before(string $source, string $target): void {
        $this->drag_resource_item($source, $target, true);
    }

    /**
     * Drags a resource category to appear immediately after another category.
     *
     * @When I drag resource category :source after resource category :target
     * @param string $source Visible name of the category to drag.
     * @param string $target Visible name of the category to drop after.
     * @throws ExpectationException
     */
    public function i_drag_resource_category_after(string $source, string $target): void {
        $this->drag_resource_category($source, $target, false);
    }

    /**
     * Drags a resource category to appear immediately before another category.
     *
     * @When I drag resource category :source before resource category :target
     * @param string $source Visible name of the category to drag.
     * @param string $target Visible name of the category to drop before.
     * @throws ExpectationException
     */
    public function i_drag_resource_category_before(string $source, string $target): void {
        $this->drag_resource_category($source, $target, true);
    }

    /**
     * Asserts that a resource item appears before another in the catalog.
     *
     * @Then resource item :first should appear before resource item :second
     * @param string $first Visible name of the item expected to appear first.
     * @param string $second Visible name of the item expected to appear second.
     * @throws ExpectationException
     */
    public function resource_item_should_appear_before(string $first, string $second): void {
        $js = <<<JS
            (function(a, b) {
                var rows = document.querySelectorAll('tr[id^="resource-item-row-"]');
                var ai = -1, bi = -1;
                for (var i = 0; i < rows.length; i++) {
                    var span = rows[i].querySelector('span[data-bookit-resource-tabledata-name-id]');
                    if (!span) continue;
                    var name = span.textContent.trim();
                    if (name === a) ai = i;
                    if (name === b) bi = i;
                }
                if (ai === -1) return 'not_found_a';
                if (bi === -1) return 'not_found_b';
                return ai < bi ? 'ok' : 'fail:a=' + ai + ',b=' + bi;
            })('$first', '$second')
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'ok') {
            throw new ExpectationException(
                "Expected \"$first\" to appear before \"$second\" but got: $result",
                $this->getSession()
            );
        }
    }

    /**
     * Asserts that no resource item row currently shows a drag-drop indicator.
     *
     * During a category drag, item rows must not display any box-shadow indicator.
     * This step should be called after initiating a category drag and before releasing.
     *
     * @Then no resource item should have a drop indicator
     * @throws ExpectationException
     */
    public function no_resource_item_should_have_a_drop_indicator(): void {
        $js = <<<'JS'
            (function() {
                var rows = document.querySelectorAll('tr[id^="resource-item-row-"]');
                var stray = [];
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].style.boxShadow && rows[i].style.boxShadow !== '') {
                        stray.push(rows[i].id);
                    }
                }
                return stray.length === 0 ? 'ok' : 'stray:' + stray.join(',');
            })()
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'ok') {
            throw new ExpectationException(
                "Expected no resource item drop indicators, but found: $result",
                $this->getSession()
            );
        }
    }

    /**
     * Simulate a drag of a resource item using HTML5 DragEvents via JavaScript.
     *
     * Temporarily patches DataTransfer.prototype.setDragImage to avoid a NotSupportedError
     * that browsers may throw when setDragImage is called on a synthetic DragEvent.
     *
     * @param string $source Visible name of the item to drag.
     * @param string $target Visible name of the item to drop on.
     * @param bool $dropbefore True to drop before the target, false to drop after.
     * @throws ExpectationException
     */
    private function drag_resource_item(string $source, string $target, bool $dropbefore): void {
        $droptop = $dropbefore ? 'true' : 'false';
        $src = addslashes($source);
        $tgt = addslashes($target);

        $js = <<<JS
            (function(srcName, tgtName, dropBefore) {
                function findItemRow(name) {
                    var rows = document.querySelectorAll('tr[id^="resource-item-row-"]');
                    for (var i = 0; i < rows.length; i++) {
                        var span = rows[i].querySelector('span[data-bookit-resource-tabledata-name-id]');
                        if (span && span.textContent.trim() === name) return rows[i];
                    }
                    return null;
                }
                var srcRow = findItemRow(srcName);
                var tgtRow = findItemRow(tgtName);
                if (!srcRow) return 'not_found_src:' + srcName;
                if (!tgtRow) return 'not_found_tgt:' + tgtName;
                var handle = srcRow.querySelector('button[data-action="drag-handle"]');
                if (!handle) return 'no_handle';

                tgtRow.scrollIntoView({block: 'center'});
                handle.scrollIntoView({block: 'nearest'});

                var hr = handle.getBoundingClientRect();
                var tr = tgtRow.getBoundingClientRect();
                var sx = hr.left + hr.width / 2;
                var sy = hr.top + hr.height / 2;
                var tx = tr.left + tr.width / 2;
                var ty = dropBefore ? tr.top + tr.height * 0.2 : tr.top + tr.height * 0.8;

                var origSDI = DataTransfer.prototype.setDragImage;
                DataTransfer.prototype.setDragImage = function() {};
                var dt;
                try { dt = new DataTransfer(); } catch(e) { dt = null; }
                function fire(el, type, x, y) {
                    el.dispatchEvent(new DragEvent(type, {
                        bubbles: true, cancelable: true,
                        clientX: x || 0, clientY: y || 0,
                        dataTransfer: dt
                    }));
                }
                fire(handle, 'dragstart', sx, sy);
                fire(tgtRow, 'dragover', tx, ty);
                fire(tgtRow, 'drop', tx, ty);
                fire(handle, 'dragend', sx, sy);
                DataTransfer.prototype.setDragImage = origSDI;
                return 'ok';
            })('$src', '$tgt', $droptop)
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'ok') {
            throw new ExpectationException(
                "Could not drag resource item \"$source\" to \"$target\": $result",
                $this->getSession()
            );
        }
        $this->getSession()->wait(3000);
    }

    /**
     * Simulate a drag of a resource category using HTML5 DragEvents via JavaScript.
     *
     * @param string $source Visible name of the category to drag.
     * @param string $target Visible name of the category to drop on.
     * @param bool $dropbefore True to drop before the target, false to drop after.
     * @throws ExpectationException
     */
    private function drag_resource_category(string $source, string $target, bool $dropbefore): void {
        $droptop = $dropbefore ? 'true' : 'false';
        $src = addslashes($source);
        $tgt = addslashes($target);

        $js = <<<JS
            (function(srcName, tgtName, dropBefore) {
                function findCategoryRow(name) {
                    var rows = document.querySelectorAll('tr[id^="resource-category-row-"]');
                    for (var i = 0; i < rows.length; i++) {
                        var span = rows[i].querySelector('td:first-child > div > span');
                        if (span && span.textContent.trim() === name) return rows[i];
                    }
                    return null;
                }
                var srcRow = findCategoryRow(srcName);
                var tgtRow = findCategoryRow(tgtName);
                if (!srcRow) return 'not_found_src:' + srcName;
                if (!tgtRow) return 'not_found_tgt:' + tgtName;
                var handle = srcRow.querySelector('button[data-action="drag-handle"]');
                if (!handle) return 'no_handle';

                tgtRow.scrollIntoView({block: 'center'});
                handle.scrollIntoView({block: 'nearest'});

                var hr = handle.getBoundingClientRect();
                var tr = tgtRow.getBoundingClientRect();
                var sx = hr.left + hr.width / 2;
                var sy = hr.top + hr.height / 2;
                var tx = tr.left + tr.width / 2;
                var ty = dropBefore ? tr.top + tr.height * 0.2 : tr.top + tr.height * 0.8;

                var origSDI = DataTransfer.prototype.setDragImage;
                DataTransfer.prototype.setDragImage = function() {};
                var dt;
                try { dt = new DataTransfer(); } catch(e) { dt = null; }
                function fire(el, type, x, y) {
                    el.dispatchEvent(new DragEvent(type, {
                        bubbles: true, cancelable: true,
                        clientX: x || 0, clientY: y || 0,
                        dataTransfer: dt
                    }));
                }
                fire(handle, 'dragstart', sx, sy);
                fire(tgtRow, 'dragover', tx, ty);
                fire(tgtRow, 'drop', tx, ty);
                fire(handle, 'dragend', sx, sy);
                DataTransfer.prototype.setDragImage = origSDI;
                return 'ok';
            })('$src', '$tgt', $droptop)
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'ok') {
            throw new ExpectationException(
                "Could not drag resource category \"$source\" to \"$target\": $result",
                $this->getSession()
            );
        }
        $this->getSession()->wait(3000);
    }
}
