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

// phpcs:disable moodle.Commenting.ValidTags.Invalid
/**
 * Custom Behat step definitions for mod_bookit.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @SuppressWarnings(PHPMD)
 */
class behat_mod_bookit extends behat_base {
// phpcs:enable moodle.Commenting.ValidTags.Invalid
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
     * Opens the Bookit overview with explicit personal filter parameters.
     *
     * @When I open the filtered Bookit overview :tab for :activity with status :status faculty :faculty and semesters :semesters
     * @param string $tab
     * @param string $activity
     * @param string $status
     * @param string $faculty
     * @param string $semesters
     */
    public function i_open_the_filtered_bookit_overview_for(
        string $tab,
        string $activity,
        string $status,
        string $faculty,
        string $semesters
    ): void {
        global $DB;

        $bookit = $DB->get_record('bookit', ['name' => $activity], 'id, course', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bookit', $bookit->id, $bookit->course, false, MUST_EXIST);
        $params = [
            'id' => $cm->id,
            'tab' => $tab,
            'bookingstatusfilter' => $status,
            'facultyid' => $faculty,
        ];
        $query = http_build_query($params);
        foreach ($this->resolve_semester_filter_values($semesters) as $semester) {
            $query .= '&semesterids[]=' . rawurlencode($semester);
        }

        $this->getSession()->visit($this->locate_path('/mod/bookit/overview.php?' . $query));
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
            'reportstart' => $this->resolve_filter_date_value($start),
            'reportend' => $this->resolve_filter_date_value($end),
        ];
        $query = http_build_query($params);
        foreach ($this->resolve_semester_filter_values($semesters) as $semester) {
            $query .= '&semesterids[]=' . rawurlencode($semester);
        }

        $this->getSession()->visit($this->locate_path('/mod/bookit/overview.php?' . $query));
    }

    /**
     * Resolve semester filter tokens used in Behat scenarios.
     *
     * @param string $semesters
     * @return string[]
     */
    private function resolve_semester_filter_values(string $semesters): array {
        $currentsemester = \mod_bookit\local\manager\event_manager::get_current_semester();

        return array_values(array_filter(array_map(static function (string $semester) use ($currentsemester): string {
            return match (trim($semester)) {
                'current' => (string)$currentsemester,
                'next' => (string)($currentsemester % 10 === 1 ? $currentsemester + 1 : $currentsemester + 9),
                'previous' => (string)($currentsemester % 10 === 1 ? $currentsemester - 9 : $currentsemester - 1),
                default => trim($semester),
            };
        }, explode(',', $semesters))));
    }

    /**
     * Resolve a reporting filter date token into a Y-m-d string.
     *
     * @param string $value
     * @return string
     */
    private function resolve_filter_date_value(string $value): string {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new \coding_exception('Unsupported Behat reporting date token: ' . $value);
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Resolve a Behat datetime token into an ISO-like datetime string.
     *
     * @param string $value
     * @return string
     */
    private function resolve_datetime_value(string $value): string {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new \coding_exception('Unsupported Behat datetime token: ' . $value);
        }

        return date('Y-m-d\\TH:i:s', $timestamp);
    }

    /**
     * Opens the calendar feed for the given activity and range.
     *
     * @When I request the Bookit calendar feed for :activity from :start to :end
     * @param string $activity
     * @param string $start
     * @param string $end
     */
    public function i_request_the_bookit_calendar_feed_for(string $activity, string $start, string $end): void {
        global $DB;

        $bookit = $DB->get_record('bookit', ['name' => $activity], 'id, course', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bookit', $bookit->id, $bookit->course, false, MUST_EXIST);
        $url = (new moodle_url('/mod/bookit/events.php', [
            'id' => $cm->id,
            'start' => $this->resolve_datetime_value($start),
            'end' => $this->resolve_datetime_value($end),
            'export' => 1,
        ]))->out(false);

        $js = <<<JS
            (function(targetUrl) {
                var request = new XMLHttpRequest();
                request.open('GET', targetUrl, false);
                request.send(null);
                window.bookitLastFeedResponse = request.responseText;
                return request.status;
            })('$url');
        JS;

        $status = $this->getSession()->evaluateScript($js);
        if ((int)$status !== 200) {
            throw new ExpectationException(
                "Could not fetch the calendar feed. HTTP status: $status",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that the server-side calendar projection contains a booking for the given user.
     *
     * @Then the Bookit calendar projection for user :username in :activity from :start to :end should contain :text
     * @param string $username
     * @param string $activity
     * @param string $start
     * @param string $end
     * @param string $text
     * @throws ExpectationException
     */
    public function the_bookit_calendar_projection_for_user_should_contain(
        string $username,
        string $activity,
        string $start,
        string $end,
        string $text
    ): void {
        $content = $this->get_calendar_projection_content($username, $activity, $start, $end);
        if (mb_strpos($content, $text) === false) {
            throw new ExpectationException(
                "The calendar projection did not contain \"$text\".",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that the server-side calendar projection hides a booking for the given user.
     *
     * @Then the Bookit calendar projection for user :username in :activity from :start to :end should not contain :text
     * @param string $username
     * @param string $activity
     * @param string $start
     * @param string $end
     * @param string $text
     * @throws ExpectationException
     */
    public function the_bookit_calendar_projection_for_user_should_not_contain(
        string $username,
        string $activity,
        string $start,
        string $end,
        string $text
    ): void {
        $content = $this->get_calendar_projection_content($username, $activity, $start, $end);
        if (mb_strpos($content, $text) !== false) {
            throw new ExpectationException(
                "The calendar projection unexpectedly contained \"$text\".",
                $this->getSession()
            );
        }
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
     * Assert that a modal select/autocomplete control contains a specific option label.
     *
     * @Then the Bookit event details control :controlname should contain option :optionlabel
     * @param string $controlname
     * @param string $optionlabel
     * @throws ExpectationException
     */
    public function the_bookit_event_details_control_should_contain_option(
        string $controlname,
        string $optionlabel
    ): void {
        $this->assert_modal_control_option($controlname, $optionlabel, true);
    }

    /**
     * Assert that a modal select/autocomplete control does not contain a specific option label.
     *
     * @Then the Bookit event details control :controlname should not contain option :optionlabel
     * @param string $controlname
     * @param string $optionlabel
     * @throws ExpectationException
     */
    public function the_bookit_event_details_control_should_not_contain_option(
        string $controlname,
        string $optionlabel
    ): void {
        $this->assert_modal_control_option($controlname, $optionlabel, false);
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
                        control.value = control.options[i].value;
                        control.selectedIndex = i;
                        control.options[i].selected = true;
                        control.dispatchEvent(new Event('input', {bubbles: true}));
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
     * Click the save action in the currently visible event details modal.
     *
     * @When I click the save action in the Bookit event details modal
     * @throws ExpectationException
     */
    public function i_click_the_save_action_in_the_bookit_event_details_modal(): void {
        $js = <<<'JS'
            (function() {
                var root = document.querySelector('.modal.show');
                if (!root) {
                    return 'modal-not-found';
                }
                window.skipClientValidation = true;
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
                "Could not click the event details modal save action. Result: $result",
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
        $this->i_click_the_save_action_in_the_bookit_event_details_modal();

        $this->getSession()->wait(10000, "document.querySelector('.modal.show') === null");
        $modalstillopen = $this->getSession()->evaluateScript("document.querySelector('.modal.show') !== null");
        if ($modalstillopen) {
            $details = $this->getSession()->evaluateScript(<<<'JS'
                (function() {
                    var root = document.querySelector('.modal.show');
                    if (!root) {
                        return '';
                    }
                    var texts = [];
                    root.querySelectorAll('.invalid-feedback, .form-control-feedback').forEach(function(node) {
                        var text = (node.textContent || '').trim();
                        if (!text) {
                            return;
                        }
                        var field = node.closest('.fitem, .mb-3, .form-group');
                        var label = '';
                        if (field) {
                            var labelnode = field.querySelector('label, .col-form-label, .fitemtitle');
                            label = labelnode ? (labelnode.textContent || '').replace(/\s+/g, ' ').trim() : '';
                        }
                        texts.push(label ? (label + ': ' + text) : text);
                    });
                    root.querySelectorAll('.alert-danger').forEach(function(node) {
                        var text = (node.textContent || '').trim();
                        if (text) {
                            texts.push(text);
                        }
                    });
                    root.querySelectorAll('input:invalid, select:invalid, textarea:invalid').forEach(function(node) {
                        var label = '';
                        if (node.id) {
                            var labelnode = root.querySelector('label[for=\"' + node.id + '\"]');
                            label = labelnode ? (labelnode.textContent || '').replace(/\s+/g, ' ').trim() : '';
                        }
                        var name = node.getAttribute('name') || node.id || 'unknown';
                        texts.push('invalid-control ' + name + (label ? ' (' + label + ')' : ''));
                    });
                    root.querySelectorAll('.is-invalid, [aria-invalid=\"true\"]').forEach(function(node) {
                        var label = '';
                        if (node.id) {
                            var labelnode = root.querySelector('label[for=\"' + node.id + '\"]');
                            label = labelnode ? (labelnode.textContent || '').replace(/\s+/g, ' ').trim() : '';
                        }
                        var name = node.getAttribute('name') || node.id || node.className || 'unknown';
                        texts.push('aria-invalid ' + name + (label ? ' (' + label + ')' : ''));
                    });
                    var saveButton = root.querySelector('button[data-action=\"save\"], footer button.btn-primary');
                    if (saveButton) {
                        texts.push('save-button disabled=' + String(!!saveButton.disabled));
                    }
                    var bookingstatus = root.querySelector('#id_bookingstatus, [name=\"bookingstatus\"]');
                    if (bookingstatus) {
                        texts.push('bookingstatus value=' + String(bookingstatus.value));
                    }
                    if (texts.length === 0) {
                        texts.push((root.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 400));
                    }
                    return texts.join(' | ');
                })();
            JS);
            throw new ExpectationException(
                'The event details modal stayed open after submit. The dynamic form did not complete successfully. '
                    . 'Visible modal feedback: ' . $details,
                $this->getSession()
            );
        }
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
                var rows = document.querySelectorAll(
                    'tr.mod-bookit-open-request-row, tr.mod-bookit-rejected-request-row'
                );
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
     * Assert that the overview table currently renders the ID column.
     *
     * @Then the Bookit overview should show the ID column
     * @throws ExpectationException
     */
    public function the_bookit_overview_should_show_the_id_column(): void {
        $this->assert_overview_id_column(true);
    }

    /**
     * Assert that the overview table currently hides the ID column.
     *
     * @Then the Bookit overview should not show the ID column
     * @throws ExpectationException
     */
    public function the_bookit_overview_should_not_show_the_id_column(): void {
        $this->assert_overview_id_column(false);
    }

    /**
     * Assert the exact set of event titles currently rendered in the overview table.
     *
     * @Then the Bookit overview should list only the events :eventlist
     * @param string $eventlist
     * @throws ExpectationException
     */
    public function the_bookit_overview_should_list_only_the_events(string $eventlist): void {
        $expected = array_values(array_filter(array_map('trim', explode(',', $eventlist))));
        $js = <<<'JS'
            (function() {
                var rows = document.querySelectorAll('#overview-table tbody tr');
                return Array.from(rows).map(function(row) {
                    var cells = row.querySelectorAll('td');
                    for (var i = 0; i < cells.length; i++) {
                        var link = cells[i].querySelector('a.bookit-event-link');
                        if (link) {
                            return link.textContent.trim();
                        }
                    }
                    return cells.length ? cells[0].textContent.trim() : '';
                }).filter(Boolean);
            })();
        JS;

        $actual = $this->getSession()->evaluateScript($js);
        sort($expected);
        sort($actual);
        if ($actual !== $expected) {
            throw new ExpectationException(
                'Unexpected overview event list. Expected ' . json_encode($expected) . ' but got ' . json_encode($actual),
                $this->getSession()
            );
        }
    }

    /**
     * Assert that the overview row for an event does not expose a detail link.
     *
     * @Then the Bookit overview should not expose a detail link for event :eventname
     * @param string $eventname
     * @throws ExpectationException
     */
    public function the_bookit_overview_should_not_expose_a_detail_link_for_event(string $eventname): void {
        $js = <<<JS
            (function(targetName) {
                var rows = document.querySelectorAll('#overview-table tbody tr');
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].textContent.indexOf(targetName) === -1) {
                        continue;
                    }
                    return rows[i].querySelector('a.bookit-event-link') ? 'has-link' : 'no-link';
                }
                return 'row-not-found';
            })('$eventname');
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result !== 'no-link') {
            throw new ExpectationException(
                "Expected no detail link for \"$eventname\" but got result: $result",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that the overview navigation tabs do not contain a specific label.
     *
     * @Then the Bookit overview navigation should not contain :text
     * @param string $text
     * @throws ExpectationException
     */
    public function the_bookit_overview_navigation_should_not_contain(string $text): void {
        $js = <<<'JS'
            (function() {
                var nav = document.querySelector('.mod_bookit-overview-examiner_overview .nav-tabs');
                return nav ? nav.textContent.replace(/\s+/g, ' ').trim() : '';
            })();
        JS;

        $content = (string)$this->getSession()->evaluateScript($js);
        if (mb_strpos($content, $text) !== false) {
            throw new ExpectationException(
                'The Bookit overview navigation unexpectedly contained "' . $text . '".',
                $this->getSession()
            );
        }
    }

    /**
     * Assert the selected semester options in the overview filter.
     *
     * @Then the Bookit overview semester filter should select :semesters
     * @param string $semesters
     * @throws ExpectationException
     */
    public function the_bookit_overview_semester_filter_should_select(string $semesters): void {
        $expectedvalues = $this->resolve_semester_filter_values($semesters);
        $js = <<<'JS'
            (function() {
                var select = document.querySelector('#bookit-semesterids');
                if (!select) {
                    return JSON.stringify({status: 'missing'});
                }
                var values = Array.from(select.options)
                    .filter(function(option) { return option.selected; })
                    .map(function(option) { return option.value; });
                return JSON.stringify({status: 'ok', values: values});
            })();
        JS;

        $result = json_decode((string)$this->getSession()->evaluateScript($js), true);
        if (!is_array($result) || ($result['status'] ?? '') !== 'ok') {
            throw new ExpectationException(
                'Could not inspect the semester filter selection. Result: ' . json_encode($result),
                $this->getSession()
            );
        }

        sort($expectedvalues);
        $actualvalues = $result['values'] ?? [];
        sort($actualvalues);
        if ($actualvalues !== $expectedvalues) {
            throw new ExpectationException(
                'Unexpected semester selection. Expected ' . json_encode($expectedvalues)
                    . ' but got ' . json_encode($actualvalues),
                $this->getSession()
            );
        }
    }

    /**
     * Assert that the raw response contains a string.
     *
     * @Then the Bookit raw response should contain :text
     * @param string $text
     * @throws ExpectationException
     */
    public function the_bookit_raw_response_should_contain(string $text): void {
        $content = (string)$this->getSession()->evaluateScript('window.bookitLastFeedResponse || ""');
        if (mb_strpos($content, $text) === false) {
            throw new ExpectationException(
                "The raw response did not contain \"$text\".",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that the raw response does not contain a string.
     *
     * @Then the Bookit raw response should not contain :text
     * @param string $text
     * @throws ExpectationException
     */
    public function the_bookit_raw_response_should_not_contain(string $text): void {
        $content = (string)$this->getSession()->evaluateScript('window.bookitLastFeedResponse || ""');
        if (mb_strpos($content, $text) !== false) {
            throw new ExpectationException(
                "The raw response unexpectedly contained \"$text\".",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that a named Bookit editor field does not equal a value, even when hidden by rich editors.
     *
     * @Then the Bookit editor field :field should not equal :value
     * @param string $field
     * @param string $value
     * @throws ExpectationException
     */
    public function the_bookit_editor_field_should_not_equal(string $field, string $value): void {
        $actual = $this->get_named_form_control_value($field);
        if ($actual === $value) {
            throw new ExpectationException(
                "The Bookit editor field \"$field\" unexpectedly matched \"$value\".",
                $this->getSession()
            );
        }
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
     * Assert whether a modal select/autocomplete control contains an option label.
     *
     * @param string $controlname
     * @param string $optionlabel
     * @param bool $shouldcontain
     * @return void
     * @throws ExpectationException
     */
    private function assert_modal_control_option(string $controlname, string $optionlabel, bool $shouldcontain): void {
        $js = <<<JS
            (function(controlName) {
                var root = document.querySelector('.modal.show');
                if (!root) {
                    return JSON.stringify({status: 'modal-not-found'});
                }
                var control = root.querySelector('#id_' + controlName + ', [name=\"' + controlName + '\"]');
                if (!control) {
                    return JSON.stringify({status: 'control-not-found'});
                }
                var options = Array.from(control.options || []).map(function(option) {
                    return option.textContent.trim();
                }).filter(function(text) {
                    return text !== '';
                });
                return JSON.stringify({status: 'ok', options: options});
            })('$controlname');
        JS;

        $result = json_decode((string)$this->getSession()->evaluateScript($js), true);
        if (!is_array($result) || ($result['status'] ?? '') !== 'ok') {
            throw new ExpectationException(
                'Could not inspect modal control options for "' . $controlname . '". Result: ' . json_encode($result),
                $this->getSession()
            );
        }

        $options = $result['options'] ?? [];
        $contains = in_array($optionlabel, $options, true);
        if ($shouldcontain && !$contains) {
            throw new ExpectationException(
                'Expected modal control "' . $controlname . '" to contain option "' . $optionlabel
                    . '" but options were ' . json_encode($options),
                $this->getSession()
            );
        }

        if (!$shouldcontain && $contains) {
            throw new ExpectationException(
                'Expected modal control "' . $controlname . '" not to contain option "' . $optionlabel
                    . '" but options were ' . json_encode($options),
                $this->getSession()
            );
        }
    }

    /**
     * Assert whether the active overview table shows the leading ID column.
     *
     * @param bool $expected
     * @return void
     * @throws ExpectationException
     */
    private function assert_overview_id_column(bool $expected): void {
        $js = <<<'JS'
            (function() {
                var table = document.querySelector('#overview-table, #open-requests-table');
                if (!table) {
                    return 'table-not-found';
                }
                var firstHeader = table.querySelector('thead th');
                if (!firstHeader) {
                    return 'header-not-found';
                }
                return firstHeader.textContent.trim();
            })();
        JS;

        $result = $this->getSession()->evaluateScript($js);
        if ($result === 'table-not-found' || $result === 'header-not-found') {
            throw new ExpectationException(
                "Could not resolve overview table header state. Result: $result",
                $this->getSession()
            );
        }

        $actual = str_starts_with($result, 'ID');
        if ($actual !== $expected) {
            $message = $expected
                ? 'Expected the overview to show the ID column, but it did not.'
                : 'Expected the overview to hide the ID column, but it was visible.';
            throw new ExpectationException($message, $this->getSession());
        }
    }

    /**
     * Build the raw calendar projection string for a given user and activity.
     *
     * @param string $username
     * @param string $activity
     * @param string $start
     * @param string $end
     * @return string
     */
    private function get_calendar_projection_content(string $username, string $activity, string $start, string $end): string {
        global $DB, $USER;

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $bookit = $DB->get_record('bookit', ['name' => $activity], 'id, course', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bookit', $bookit->id, $bookit->course, false, MUST_EXIST);

        $previoususer = clone($USER);
        \core\session\manager::set_user($user);
        \accesslib_clear_all_caches(true);
        load_all_capabilities();
        $events = \mod_bookit\local\manager\event_manager::get_events_in_timerange(
            (new \DateTime($this->resolve_datetime_value($start)))->format('Y-m-d H:i'),
            (new \DateTime($this->resolve_datetime_value($end)))->format('Y-m-d H:i'),
            $cm->id
        );
        \core\session\manager::set_user($previoususer);
        \accesslib_clear_all_caches(true);
        load_all_capabilities();

        return json_encode($events);
    }

    /**
     * Return the value of a form control by name, including hidden editor-backed fields.
     *
     * @param string $name
     * @return string
     * @throws ExpectationException
     */
    private function get_named_form_control_value(string $name): string {
        $script = <<<JS
            (function(fieldName) {
                var elements = document.querySelectorAll('textarea, input, select');
                for (var i = 0; i < elements.length; i++) {
                    if (elements[i].getAttribute('name') === fieldName) {
                        if (window.tinymce && elements[i].id) {
                            var editor = window.tinymce.get(elements[i].id);
                            if (editor) {
                                editor.save();
                            }
                        }
                        return elements[i].value;
                    }
                }
                return null;
            })(%s);
        JS;
        $value = $this->getSession()->evaluateScript(sprintf($script, json_encode($name)));
        if ($value === null) {
            throw new ExpectationException(
                "The Bookit editor field \"$name\" was not found in the DOM.",
                $this->getSession()
            );
        }

        return (string)$value;
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
