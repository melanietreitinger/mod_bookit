@mod @mod_bookit @javascript
Feature: Governed calendar and export read contract stay aligned
  In order to trust export previews during the read-model rollout
  As a privileged Bookit user
  I need the calendar projection and export selection surface to expose the same authorized bookings.

  Background:
    Given the following "users" exist:
      | username    | firstname | lastname |
      | supportuser | Sam       | Support  |
      | bookinguser | Bella     | Booker   |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname        | name               | archetype |
      | bookitparticipant | Bookit Participant | student   |
    And the following "role capability" exists:
      | role                                | bookitparticipant |
      | mod/bookit:view                     | allow             |
      | mod/bookit:viewownoverview         | allow             |
      | mod/bookit:viewalldetailsofownevent | allow            |
    And the following "course enrolments" exist:
      | user        | course | role              |
      | supportuser | C1     | bookitparticipant |
      | bookinguser | C1     | bookitparticipant |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Export preview only exposes the same authorized calendar booking set
    Given the following "mod_bookit > events" exist:
      | name                 | username    | supportperson_usernames | startdate                               | enddate                                 | bookingstatus | institution |
      | Accepted export exam | bookinguser | supportuser             | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S##   | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S##   | 2             | 1           |
      | Hidden export exam   | bookinguser | supportuser             | ##tomorrow 12:00##%Y-%m-%dT%H:%M:%S##   | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S##   | 0             | 1           |
    When I log in as "supportuser"
    Then the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should contain "Accepted export exam"
    And the Bookit calendar projection for user "supportuser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should not contain "Hidden export exam"
    When I am on the "My BookIt Activity" "bookit activity" page
    And I click on "Export events" "button"
    Then the Bookit export modal should contain "Accepted export exam"
    And I should not see "Hidden export exam"
