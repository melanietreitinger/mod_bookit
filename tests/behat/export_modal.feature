@mod @mod_bookit @javascript
Feature: Open the export modal
  In order to export calendar events with the improved selection flow
  As a privileged BookIt user
  I need the export modal to expose the expected filters and selection summary.

  Background:
    Given the following "users" exist:
      | username    | firstname | lastname |
      | supportuser | Sam       | Support  |
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname   | name         | archetype |
      | serviceteam | Service-Team | student   |
    And the following "role capability" exists:
      | role                             | serviceteam |
      | mod/bookit:view                  | allow       |
      | mod/bookit:viewownoverview       | allow       |
      | mod/bookit:managebasics          | allow       |
      | mod/bookit:viewalldetailsofevent | allow       |
    And the following "course enrolments" exist:
      | user        | course | role        |
      | supportuser | C1     | serviceteam |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Export modal shows the reporting controls and initial selection state
    Given the following "mod_bookit > events" exist:
      | name            | username    | startdate                         | enddate                              | bookingstatus | institution |
      | Export Exam One | supportuser | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 2             | 1           |
    When I log in as "supportuser"
    And I am on the "My BookIt Activity" "bookit activity" page
    And I click on "Export events" "button"
    Then I should see "Please tick the events to export:"
    And I should see "From"
    And I should see "To"
    And I should see "Reset range"
    And I should see "Selected: 0"
