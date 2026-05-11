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
      | Accepted support exam | bookinguser | supportuser             | 2026-05-10T09:00:00 | 2026-05-10T11:00:00 | 2             | 1           |
      | Hidden support exam   | bookinguser | supportuser             | 2026-05-10T12:00:00 | 2026-05-10T14:00:00 | 0             | 1           |
    When I log in as "supportuser"
    Then the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "2026-05-10T00:00:00" to "2026-05-11T00:00:00" should contain "Accepted support exam"
    And the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "2026-05-10T00:00:00" to "2026-05-11T00:00:00" should not contain "Hidden support exam"
    And the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "2026-05-10T00:00:00" to "2026-05-11T00:00:00" should not contain "Reserved"
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Accepted support exam"
    And I should not see "Hidden support exam"

  Scenario: Mixed-role user keeps participant visibility in both surfaces
    Given the following "mod_bookit > events" exist:
      | name                | username    | otherexaminer_usernames | supportperson_usernames | startdate            | enddate              | bookingstatus | institution |
      | Mixed role exam     | bookinguser | mixeduser               | mixeduser               | 2026-05-10T15:00:00 | 2026-05-10T17:00:00 | 1             | 1           |
      | Accepted helper exam | bookinguser |                         | mixeduser               | 2026-05-11T09:00:00 | 2026-05-11T11:00:00 | 2             | 1           |
    When I log in as "mixeduser"
    Then the Bookit calendar projection for user "mixeduser" in "My BookIt Activity" from "2026-05-10T00:00:00" to "2026-05-12T00:00:00" should contain "Mixed role exam"
    And the Bookit calendar projection for user "mixeduser" in "My BookIt Activity" from "2026-05-10T00:00:00" to "2026-05-12T00:00:00" should contain "Accepted helper exam"
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Mixed role exam"
    And I should see "Accepted helper exam"
