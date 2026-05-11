@mod @mod_bookit @javascript
Feature: Complete overview defaults, history and role-specific columns
  In order to work with the second-pass overview model
  As a service-team user or participant
  I need default semester filtering, separate history access and personal filters.

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname |
      | bookinguser   | Bella     | Booker   |
      | serviceteam   | Susi      | Service  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname         | name               | archetype |
      | bookitparticipant | Bookit Participant | student   |
      | serviceteamrole   | Service-Team       | student   |
    And the following "role capability" exists:
      | role                                | bookitparticipant |
      | mod/bookit:view                     | allow             |
      | mod/bookit:viewownoverview         | allow             |
      | mod/bookit:viewalldetailsofownevent | allow            |
    And the following "role capability" exists:
      | role                                | serviceteamrole |
      | mod/bookit:view                     | allow           |
      | mod/bookit:viewownoverview         | allow           |
      | mod/bookit:managebasics            | allow           |
      | mod/bookit:viewalldetailsofevent   | allow           |
    And the following "course enrolments" exist:
      | user        | course | role              |
      | bookinguser | C1     | bookitparticipant |
      | serviceteam | C1     | serviceteamrole   |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Service team starts in the current semester and sees the ID column
    Given the following "mod_bookit > events" exist:
      | name                | username    | startdate            | enddate              | bookingstatus | institution | semester |
      | Summer review exam  | serviceteam | 2026-05-10T09:00:00 | 2026-05-10T11:00:00 | 2             | 1           | 20261    |
      | Winter review exam  | serviceteam | 2026-11-10T09:00:00 | 2026-11-10T11:00:00 | 2             | 1           | 20262    |
    When I log in as "serviceteam"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Summer review exam"
    And I should not see "Winter review exam"
    And I should see "1 events"
    And the Bookit overview should show the ID column

  Scenario: Participant history is separated from active events
    Given the following "mod_bookit > events" exist:
      | name             | username    | startdate                                | enddate                                  | bookingstatus | institution | semester |
      | Future own exam  | bookinguser | ##tomorrow noon##%Y-%m-%dT%H:%M:%S##     | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S##    | 2             | 1           | 20261    |
      | Past own exam    | bookinguser | ##yesterday noon##%Y-%m-%dT%H:%M:%S##    | ##yesterday 14:00##%Y-%m-%dT%H:%M:%S##   | 2             | 1           | 20261    |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Future own exam"
    And I should not see "Past own exam"
    And the Bookit overview should not show the ID column
    When I open the Bookit overview "history" for "My BookIt Activity"
    Then I should see "Past own exam"
    And I should not see "Future own exam"
