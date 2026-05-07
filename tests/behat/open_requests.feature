@mod @mod_bookit @javascript
Feature: Process open booking requests
  In order to handle incoming booking requests efficiently
  As a service-team user
  I need a dedicated open-requests view with direct workflow actions.

  Background:
    Given the following "users" exist:
      | username    | firstname | lastname |
      | susiservice | Susi      | Service  |
    And the following "courses" exist:
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
      | susiservice | C1     | serviceteam |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Service team sees open requests with count
    Given the following "mod_bookit > events" exist:
      | name            | startdate                         | enddate                              | bookingstatus | institution |
      | Exam Physics II | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "susiservice"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    Then I should see "Open requests"
    And I should see "1 open requests"
    And I should see "Exam Physics II"

  Scenario: Service team accepts an open request directly from the list
    Given the following "mod_bookit > events" exist:
      | name            | startdate                         | enddate                              | bookingstatus | institution |
      | Exam Chemistry I | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "susiservice"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    And I click the open request action "Accept" for event "Exam Chemistry I"
    Then I should see "There are currently no open booking requests."
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Exam Chemistry I"
    And I should see "Accepted"
