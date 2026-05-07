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
     * Opens the Bookit overview for the named activity and tab.
     *
     * @Given I open the Bookit overview :tab for :activity
     * @param string $tab
     * @param string $activity
     */
    public function i_open_the_bookit_overview_for(string $tab, string $activity): void {
        global $DB;

        $bookit = $DB->get_record('bookit', ['name' => $activity], 'id, course', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bookit', $bookit->id, $bookit->course, false, MUST_EXIST);
        $url = new moodle_url('/mod/bookit/overview.php', ['id' => $cm->id, 'tab' => $tab]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }

    /**
     * Opens the Bookit reporting overview with explicit filter parameters.
     *
     * @When I open the Bookit reporting overview for :activity from :start to :end with semesters :semesters
     * @param string $activity
     * @param string $start
     * @param string $end
     * @param string $semesters
     */
    public function i_open_the_bookit_reporting_overview_for_with_filters(
        string $activity,
        string $start,
        string $end,
        string $semesters
    ): void {
        global $DB;

        $bookit = $DB->get_record('bookit', ['name' => $activity], 'id, course', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bookit', $bookit->id, $bookit->course, false, MUST_EXIST);
        $params = [
            'id' => $cm->id,
            'tab' => 'myevents',
            'reportstart' => $start,
            'reportend' => $end,
        ];
        $query = http_build_query($params);
        foreach (array_filter(array_map('trim', explode(',', $semesters))) as $semester) {
            $query .= '&semesterids[]=' . rawurlencode($semester);
        }

        $this->getSession()->visit($this->locate_path('/mod/bookit/overview.php?' . $query));
    }

    /**
     * Opens the event details modal for the given event title.
     *
     * @When I open the Bookit event details for :eventname
     * @param string $eventname
     * @throws ExpectationException
     */
    public function i_open_the_bookit_event_details_for(string $eventname): void {
        $js = <<<JS
            (function(eventLabel) {
                var links = document.querySelectorAll('a.bookit-event-link');
                for (var i = 0; i < links.length; i++) {
                    if (links[i].textContent.trim() === eventLabel) {
                        links[i].click();
                        return 'clicked';
                    }
                }
                return 'link-not-found';
            })('$eventname');
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'clicked') {
            throw new ExpectationException(
                "Could not open the event details for \"$eventname\". Result: $result",
                $this->getSession()
            );
        }

        $this->getSession()->wait(3000, "document.querySelector('.modal.show') !== null");
    }

    /**
     * Assert that a modal control is enabled.
     *
     * @Then the Bookit event details control :controlname should be enabled
     * @param string $controlname
     * @throws ExpectationException
     */
    public function the_bookit_event_details_control_should_be_enabled(string $controlname): void {
        $this->assert_modal_control_state($controlname, 'enabled');
    }

    /**
     * Assert that a modal control is disabled.
     *
     * @Then the Bookit event details control :controlname should be disabled
     * @param string $controlname
     * @throws ExpectationException
     */
    public function the_bookit_event_details_control_should_be_disabled(string $controlname): void {
        $this->assert_modal_control_state($controlname, 'disabled');
    }

    /**
     * Assert that a modal control is not visible.
     *
     * @Then the Bookit event details control :controlname should not be visible
     * @param string $controlname
     * @throws ExpectationException
     */
    public function the_bookit_event_details_control_should_not_be_visible(string $controlname): void {
        $this->assert_modal_control_state($controlname, 'hidden');
    }

    /**
     * Set a select value inside the event details modal.
     *
     * @When I select :value in the Bookit event details control :controlname
     * @param string $value
     * @param string $controlname
     * @throws ExpectationException
     */
    public function i_select_in_the_bookit_event_details_control(string $value, string $controlname): void {
        $js = <<<JS
            (function(controlName, targetLabel) {
                var root = document.querySelector('.modal.show');
                if (!root) {
                    return 'modal-not-found';
                }
                var control = root.querySelector('#id_' + controlName + ', [name=\"' + controlName + '\"]');
                if (!control) {
                    return 'control-not-found';
                }
                if (control.tagName !== 'SELECT') {
                    return 'control-not-select';
                }
                for (var i = 0; i < control.options.length; i++) {
                    if (control.options[i].textContent.trim() === targetLabel) {
                        control.selectedIndex = i;
                        control.dispatchEvent(new Event('change', {bubbles: true}));
                        return 'selected';
                    }
                }
                return 'option-not-found';
            })('$controlname', '$value');
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'selected') {
            throw new ExpectationException(
                "Could not select \"$value\" in modal control \"$controlname\". Result: $result",
                $this->getSession()
            );
        }
    }

    /**
     * Force a modal select control to a timestamp in the past.
     *
     * @When I set the Bookit event details control :controlname to a past timestamp
     * @param string $controlname
     * @throws ExpectationException
     */
    public function i_set_the_bookit_event_details_control_to_a_past_timestamp(string $controlname): void {
        $js = <<<JS
            (function(controlName) {
                var root = document.querySelector('.modal.show');
                if (!root) {
                    return 'modal-not-found';
                }
                var control = root.querySelector('#id_' + controlName + ', [name=\"' + controlName + '\"]');
                if (!control) {
                    return 'control-not-found';
                }
                if (control.tagName !== 'SELECT') {
                    return 'control-not-select';
                }
                var pasttimestamp = String(Math.floor(Date.now() / 1000) - 3600);
                var option = Array.from(control.options).find(function(item) {
                    return item.value === pasttimestamp;
                });
                if (!option) {
                    option = document.createElement('option');
                    option.value = pasttimestamp;
                    option.textContent = 'Forced past option';
                    control.appendChild(option);
                }
                control.value = pasttimestamp;
                control.dispatchEvent(new Event('change', {bubbles: true}));
                return 'selected';
            })('$controlname');
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'selected') {
            throw new ExpectationException(
                "Could not set modal control \"$controlname\" to a past timestamp. Result: $result",
                $this->getSession()
            );
        }
    }

    /**
     * Submit the currently visible event details modal.
     *
     * @When I submit the Bookit event details modal
     * @throws ExpectationException
     */
    public function i_submit_the_bookit_event_details_modal(): void {
        $js = <<<'JS'
            (function() {
                var root = document.querySelector('.modal.show');
                if (!root) {
                    return 'modal-not-found';
                }
                var button = root.querySelector('button[data-action="save"], footer button.btn-primary');
                if (!button) {
                    return 'save-not-found';
                }
                button.click();
                return 'clicked';
            })();
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'clicked') {
            throw new ExpectationException(
                "Could not submit the event details modal. Result: $result",
                $this->getSession()
            );
        }

        $this->getSession()->wait(3000);
    }

    /**
     * Closes the currently visible modal dialog.
     *
     * @When I close the currently open dialog
     * @throws ExpectationException
     */
    public function i_close_the_currently_open_dialog(): void {
        $js = <<<'JS'
            (function() {
                var root = document.querySelector('.modal.show');
                if (!root) {
                    return 'modal-not-found';
                }
                var button = root.querySelector('button.btn-close, button[data-bs-dismiss="modal"], button[data-dismiss="modal"]');
                if (button) {
                    button.click();
                    return 'clicked';
                }
                document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
                return 'escape-dispatched';
            })();
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if (!in_array($result, ['clicked', 'escape-dispatched'], true)) {
            throw new ExpectationException(
                "Could not close the current dialog. Result: $result",
                $this->getSession()
            );
        }

        $this->getSession()->wait(3000, "document.querySelector('.modal.show') === null");
    }

    /**
     * Click an open-request action button in the row of the given event.
     *
     * @When I click the open request action :action for event :eventname
     * @param string $action
     * @param string $eventname
     * @throws ExpectationException
     */
    public function i_click_the_open_request_action_for_event(string $action, string $eventname): void {
        $js = <<<JS
            (function(actionLabel, eventLabel) {
                var rows = document.querySelectorAll('tr.mod-bookit-open-request-row');
                for (var i = 0; i < rows.length; i++) {
                    var link = rows[i].querySelector('a.bookit-event-link');
                    if (!link || link.textContent.trim() !== eventLabel) {
                        continue;
                    }
                    var buttons = rows[i].querySelectorAll('button[data-action="set-booking-status"]');
                    for (var j = 0; j < buttons.length; j++) {
                        if (buttons[j].textContent.trim() === actionLabel) {
                            buttons[j].click();
                            return 'clicked';
                        }
                    }
                    return 'action-not-found';
                }
                return 'row-not-found';
            })('$action', '$eventname');
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'clicked') {
            throw new ExpectationException(
                "Could not click action \"$action\" for event \"$eventname\". Result: $result",
                $this->getSession()
            );
        }
        $this->getSession()->wait(3000);
    }

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
     * Assert the state of a form control inside the currently visible event details modal.
     *
     * @param string $controlname
     * @param string $expectedstate enabled|disabled|hidden
     * @throws ExpectationException
     */
    private function assert_modal_control_state(string $controlname, string $expectedstate): void {
        $js = <<<JS
            (function(controlName) {
                var root = document.querySelector('.modal.show');
                if (!root) {
                    return 'modal-not-found';
                }
                var control = root.querySelector('#id_' + controlName + ', [name=\"' + controlName + '\"]');
                if (!control) {
                    return 'hidden';
                }
                if (control.type === 'hidden') {
                    return 'hidden';
                }
                var style = window.getComputedStyle(control);
                var visible = style.display !== 'none' &&
                    style.visibility !== 'hidden' &&
                    (control.offsetWidth > 0 || control.offsetHeight > 0 || control.getClientRects().length > 0);
                if (!visible) {
                    return 'hidden';
                }
                return control.disabled ? 'disabled' : 'enabled';
            })('$controlname');
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== $expectedstate) {
            throw new ExpectationException(
                "Expected modal control \"$controlname\" to be \"$expectedstate\" but got \"$result\".",
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
