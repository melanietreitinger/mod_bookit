@mod @mod_bookit @javascript
Feature: Keep calendar and overview visibility aligned
  In order to avoid leaking hidden bookings
  As a restricted or mixed-role participant
  I need the same visible booking set in the calendar feed and overview.

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname |
      | bookinguser   | Bella     | Booker   |
      | supportuser   | Sam       | Support  |
      | mixeduser     | Mira      | Mixed    |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname         | name               | archetype |
      | bookitparticipant | Bookit Participant | student   |
    And the following "role capability" exists:
      | role                                | bookitparticipant |
      | mod/bookit:view                     | allow             |
      | mod/bookit:viewownoverview         | allow             |
      | mod/bookit:viewalldetailsofownevent | allow            |
    And the following "course enrolments" exist:
      | user        | course | role              |
      | bookinguser | C1     | bookitparticipant |
      | supportuser | C1     | bookitparticipant |
      | mixeduser   | C1     | bookitparticipant |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Support person sees only accepted bookings across calendar and overview
    Given the following "mod_bookit > events" exist:
      | name                  | username    | supportperson_usernames | startdate            | enddate              | bookingstatus | institution |
      | Accepted support exam | bookinguser | supportuser             | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S## | 2             | 1 |
      | Hidden support exam   | bookinguser | supportuser             | ##tomorrow 12:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 0             | 1 |
    When I log in as "supportuser"
    Then the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should contain "Accepted support exam"
    And the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should not contain "Hidden support exam"
    And the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should not contain "Reserved"
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Accepted support exam"
    And I should not see "Hidden support exam"

  Scenario: Mixed-role user keeps participant visibility in both surfaces
    Given the following "mod_bookit > events" exist:
      | name                | username    | otherexaminer_usernames | supportperson_usernames | startdate            | enddate              | bookingstatus | institution |
      | Mixed role exam      | bookinguser | mixeduser               | mixeduser               | ##tomorrow 15:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 17:00##%Y-%m-%dT%H:%M:%S## | 1             | 1 |
      | Accepted helper exam | bookinguser |                         | mixeduser               | ##+2 days 09:00##%Y-%m-%dT%H:%M:%S##  | ##+2 days 11:00##%Y-%m-%dT%H:%M:%S##  | 2             | 1 |
    When I log in as "mixeduser"
    Then the Bookit calendar projection for user "mixeduser" in "My BookIt Activity" from "tomorrow 00:00" to "+2 days 23:59" should contain "Mixed role exam"
    And the Bookit calendar projection for user "mixeduser" in "My BookIt Activity" from "tomorrow 00:00" to "+2 days 23:59" should contain "Accepted helper exam"
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Mixed role exam"
    And I should see "Accepted helper exam"

  Scenario: Booking person keeps visibility after service-team status transition
    Given the following "users" exist:
      | username    | firstname | lastname |
      | serviceteam | Service   | Team     |
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
      | serviceteam | C1     | serviceteam |
    Given the following "mod_bookit > events" exist:
      | name                  | username    | startdate                            | enddate                              | bookingstatus | institution |
      | Transition visibility | bookinguser | ##tomorrow 10:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 12:00##%Y-%m-%dT%H:%M:%S## | 0             | 1           |
    When I log in as "serviceteam"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    And I click the open request action "Set in progress" for event "Transition visibility"
    And I click the open request action "Accept" for event "Transition visibility"
    When I log in as "bookinguser"
    Then the Bookit calendar projection for user "bookinguser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should contain "Transition visibility"
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Transition visibility"
    And I should see "Accepted"

  Scenario: Service team no longer sees canceled booking in calendar
    Given the following "users" exist:
      | username    | firstname | lastname |
      | serviceteam | Service   | Team     |
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
      | serviceteam | C1     | serviceteam |
    Given the following "mod_bookit > events" exist:
      | name                 | username    | startdate                            | enddate                              | bookingstatus | institution |
      | Canceled visibility  | bookinguser | ##tomorrow 10:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 12:00##%Y-%m-%dT%H:%M:%S## | 3             | 1           |
    When I log in as "serviceteam"
    Then the Bookit calendar projection for user "serviceteam" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should not contain "Canceled visibility"
