@mod @mod_bookit @javascript
Feature: Open the export modal
  In order to export calendar events with the improved selection flow
  As a privileged BookIt user
  I need the export modal to expose the expected filters and selection summary.

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Export modal shows the reporting controls and initial selection state
    Given the following "mod_bookit > events" exist:
      | name            | startdate                         | enddate                              | bookingstatus | institution |
      | Export Exam One | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 2             | 1           |
    When I log in as "admin"
    And I am on the "My BookIt Activity" "bookit activity" page
    And I click on "Export events" "button"
    Then I should see "Please tick the events to export:"
    And I should see "From"
    And I should see "To"
    And I should see "Reset range"
    And I should see "Selected: 0"
