@mod @mod_bookit @javascript
Feature: Enforce role-based visibility and editing for booking requests
  In order to keep booking access aligned with the workflow
  As a Bookit participant
  I need visibility and edit actions to respect my role and the booking status.

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname |
      | bookinguser   | Bella     | Booker   |
      | supportuser   | Sam       | Support  |
      | observeruser  | Olivia    | Observer |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname        | name               | archetype |
      | bookitparticipant | Bookit Participant | student   |
      | bookitobserver   | Bookit Observer    | student   |
    And the following "role capability" exists:
      | role                              | bookitparticipant |
      | mod/bookit:view                   | allow             |
      | mod/bookit:viewownoverview        | allow             |
      | mod/bookit:viewalldetailsofownevent | allow           |
    And the following "role capability" exists:
      | role                             | bookitobserver |
      | mod/bookit:view                  | allow          |
      | mod/bookit:viewrestrictedobserver | allow         |
    And the following "course enrolments" exist:
      | user        | course | role              |
      | bookinguser | C1     | bookitparticipant |
      | supportuser | C1     | bookitparticipant |
      | observeruser | C1    | bookitobserver    |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |
    And the following "mod_bookit > institutions" exist:
      | name                 |
      | Standard-Institution |
    And the following "mod_bookit > rooms" exist:
      | name         | shortname | seats |
      | Default room | DEF       | 0     |

  Scenario: Support person sees assigned new and confirmed bookings and may edit only internal notes
    Given the following "mod_bookit > events" exist:
      | name                    | username    | supportperson_usernames | startdate                         | enddate                              | bookingstatus | institution |
      | Accepted support exam   | bookinguser | supportuser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 2 | 1 |
      | New support exam        | bookinguser | supportuser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 15:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "supportuser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Accepted support exam"
    And I should see "New support exam"
    When I open the Bookit event details for "Accepted support exam"
    Then the Bookit event details control "name" should be disabled
    And the Bookit event details control "institutionid" should be disabled
    And the Bookit event details control "starttime" should be disabled
    And the Bookit event details control "bookingstatus" should be disabled
    And the Bookit event details control "internalnotes" should be enabled

  Scenario: Booking person cancels a new request from the overview row
    Given the following "mod_bookit > events" exist:
      | name                    | username    | personincharge_username | startdate                         | enddate                              | bookingstatus | institution |
      | Overview cancel booking | bookinguser | bookinguser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I cancel the booking "Overview cancel booking" from the Bookit overview
    And I confirm the Bookit overview cancel dialog
    Then the Bookit overview should list only the events ""

  Scenario: Booking person cancels an accepted booking from the overview row
    Given the following "mod_bookit > events" exist:
      | name                         | username    | personincharge_username | startdate                         | enddate                              | bookingstatus | institution |
      | Overview cancel-only booking | bookinguser | bookinguser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 15:00##%Y-%m-%dT%H:%M:%S## | 2 | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I cancel the booking "Overview cancel-only booking" from the Bookit overview
    And I confirm the Bookit overview cancel dialog
    Then the Bookit overview should list only the events ""

  Scenario: Booking person may edit only while status is New and may later only cancel
    Given the following "mod_bookit > events" exist:
      | name                    | username    | personincharge_username | startdate                         | enddate                              | bookingstatus | institution |
      | Editable booking        | bookinguser | bookinguser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
      | Cancel-only booking     | bookinguser | bookinguser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 15:00##%Y-%m-%dT%H:%M:%S## | 2 | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I open the Bookit event details for "Editable booking"
    Then the Bookit event details control "name" should be enabled
    And the Bookit event details control "bookingstatus" should be enabled
    When I close the currently open dialog
    And I open the Bookit event details for "Cancel-only booking"
    Then the Bookit event details control "name" should be disabled
    And the Bookit event details control "bookingstatus" should be enabled
    And the Bookit event details primary action should be "Save changes"
    And I submit the Bookit event details modal
    Then I should see "Canceled"

  Scenario: Booking person may self-cancel a new request and it leaves the active overview
    Given the following "mod_bookit > events" exist:
      | name                    | username    | personincharge_username | startdate                         | enddate                              | bookingstatus | institution |
      | Self-cancel booking     | bookinguser | bookinguser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I cancel the booking "Self-cancel booking" from the Bookit overview
    And I confirm the Bookit overview cancel dialog
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview should list only the events ""

  Scenario: Observer sees only accepted bookings in a neutral reserved projection
    Given the following "mod_bookit > events" exist:
      | name                    | username    | startdate                         | enddate                              | bookingstatus | institution |
      | Observer visible exam   | bookinguser | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 2 | 1 |
      | Observer hidden request | bookinguser | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 15:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "observeruser"
    Then the Bookit calendar projection for user "observeruser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should contain "Reserved"
    And the Bookit calendar projection for user "observeruser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should not contain "Observer visible exam"
    And the Bookit calendar projection for user "observeruser" in "My BookIt Activity" from "tomorrow 00:00" to "tomorrow 23:59" should not contain "Observer hidden request"
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview should list only the events "Reserved"
    And the Bookit overview should not show a cancel action for event "Reserved"

  Scenario: Observer overview rows do not open event details
    Given the following "mod_bookit > events" exist:
      | name                  | username    | startdate                         | enddate                              | bookingstatus | institution |
      | Observer detail exam  | bookinguser | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 2 | 1 |
    When I log in as "observeruser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview should not expose a detail link for event "Reserved"

  Scenario: Observer export route shows a clear denied outcome
    Given the following "mod_bookit > events" exist:
      | name                  | username    | startdate                         | enddate                              | bookingstatus | institution |
      | Observer export exam  | bookinguser | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 2 | 1 |
    When I log in as "observeruser"
    And I open the Bookit export endpoint for "My BookIt Activity"
    Then I should see "Event export is not available for your role."
    And I should not see "Debug info"

  Scenario: Observer overview has no personal navigation and shows the restricted empty state
    When I log in as "observeruser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview navigation should not contain "My booked events"
    And the Bookit overview navigation should not contain "History"
    And I should see "No accepted bookings are currently available for your role."
