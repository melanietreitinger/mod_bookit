@mod @mod_bookit @javascript
Feature: Filter reporting data and block past-date saves
  In order to trust reporting results and booking validation
  As a service-team user
  I need filterable overview results and consistent past-date rejection.

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
      | mod/bookit:addevent              | allow       |
      | mod/bookit:editevent             | allow       |
    And the following "course enrolments" exist:
      | user        | course | role        |
      | susiservice | C1     | serviceteam |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Reporting overview filters by date range and semester
    Given the following "mod_bookit > events" exist:
      | name                | username    | startdate            | enddate              | bookingstatus | institution |
      | Summer review exam  | susiservice | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S##  | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S##  | 2             | 1 |
      | Winter review exam  | susiservice | ##+180 days 09:00##%Y-%m-%dT%H:%M:%S## | ##+180 days 11:00##%Y-%m-%dT%H:%M:%S## | 2             | 1 |
    When I log in as "susiservice"
    And I open the Bookit reporting overview for "My BookIt Activity" from "-30 days" to "+30 days" with semesters "current"
    Then I should see "Summer review exam"
    And I should not see "Winter review exam"
    And I should see "1 events"

  Scenario: Saving an event with a forced past start time is rejected
    Given the following "mod_bookit > events" exist:
      | name                | username    | startdate            | enddate              | bookingstatus | institution |
      | Future validation exam | susiservice | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S## | 0             | 1 |
    When I log in as "susiservice"
    And I open the Bookit reporting overview for "My BookIt Activity" from "-30 days" to "+30 days" with semesters "current"
    And I open the Bookit event details for "Future validation exam"
    And I set the Bookit event details control "starttime" to a past timestamp
    And I submit the Bookit event details modal
    Then I should see "You cannot enter events in the past."
