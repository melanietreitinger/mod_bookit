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
      | name                | username    | startdate            | enddate              | bookingstatus | institution |
      | Summer review exam  | serviceteam | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S## | 2             | 1 |
      | Winter review exam  | serviceteam | ##+180 days 09:00##%Y-%m-%dT%H:%M:%S## | ##+180 days 11:00##%Y-%m-%dT%H:%M:%S## | 2             | 1 |
    When I log in as "serviceteam"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview semester filter should select "current"
    Then I should see "Summer review exam"
    And I should not see "Winter review exam"
    And I should see "1 events"
    And the Bookit overview should show the ID column

  Scenario: Explicit non-default semester selection stays active
    Given the following "mod_bookit > events" exist:
      | name                 | username    | startdate                         | enddate                              | bookingstatus | institution |
      | Current semester exam | serviceteam | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S## | 2             | 1 |
      | Next semester exam    | serviceteam | ##+180 days 09:00##%Y-%m-%dT%H:%M:%S## | ##+180 days 11:00##%Y-%m-%dT%H:%M:%S## | 2             | 1 |
    When I log in as "serviceteam"
    And I open the filtered Bookit overview "myevents" for "My BookIt Activity" with status "-1" faculty "0" and semesters "next"
    Then the Bookit overview semester filter should select "next"
    And I should see "Next semester exam"
    And I should not see "Current semester exam"

  Scenario: Participant history is separated from active events
    Given the following "mod_bookit > events" exist:
      | name             | username    | startdate                                | enddate                                  | bookingstatus | institution |
      | Future own exam  | bookinguser | ##tomorrow noon##%Y-%m-%dT%H:%M:%S##     | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S##    | 2             | 1 |
      | Past own exam    | bookinguser | ##yesterday noon##%Y-%m-%dT%H:%M:%S##    | ##yesterday 14:00##%Y-%m-%dT%H:%M:%S##   | 2             | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview inner tabs should contain "My booked events"
    And the Bookit overview inner tabs should contain "History"
    And the Bookit overview tab "My booked events" should be active
    And the Bookit overview should not show legacy inner navigation
    Then I should see "Future own exam"
    And I should not see "Past own exam"
    And the Bookit overview should not show the ID column
    When I open the Bookit overview "history" for "My BookIt Activity"
    Then the Bookit overview inner tabs should contain "My booked events"
    And the Bookit overview inner tabs should contain "History"
    And the Bookit overview tab "History" should be active
    Then I should see "Past own exam"
    And I should not see "Future own exam"

  Scenario: Self-cancelled new requests stay out of active lists and remain distinguishable in history
    Given the following "mod_bookit > events" exist:
      | name                | username    | personincharge_username | startdate                         | enddate                              | bookingstatus | institution |
      | Self-cancel history | bookinguser | bookinguser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I open the Bookit event details for "Self-cancel history"
    And I select "Canceled" in the Bookit event details control "bookingstatus"
    And I submit the Bookit event details modal
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview should list only the events ""
    When I log in as "serviceteam"
    And I open the Bookit overview "history" for "My BookIt Activity"
    Then I should see "Self-cancel history"
    And I should see "Cancelled by requester"
