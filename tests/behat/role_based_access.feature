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
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname        | name               | archetype |
      | bookitparticipant | Bookit Participant | student   |
    And the following "role capability" exists:
      | role                              | bookitparticipant |
      | mod/bookit:view                   | allow             |
      | mod/bookit:viewownoverview        | allow             |
      | mod/bookit:viewalldetailsofownevent | allow           |
    And the following "course enrolments" exist:
      | user        | course | role              |
      | bookinguser | C1     | bookitparticipant |
      | supportuser | C1     | bookitparticipant |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |
    And the following "mod_bookit > institutions" exist:
      | name                 |
      | Standard-Institution |
    And the following "mod_bookit > rooms" exist:
      | name         | shortname | seats |
      | Default room | DEF       | 0     |

  Scenario: Support person sees only accepted bookings and may edit only internal notes
    Given the following "mod_bookit > events" exist:
      | name                    | username    | supportperson_usernames | startdate                         | enddate                              | bookingstatus | institution |
      | Accepted support exam   | bookinguser | supportuser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 2 | 1 |
      | New support exam        | bookinguser | supportuser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 15:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "supportuser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Accepted support exam"
    And I should not see "New support exam"
    When I open the Bookit event details for "Accepted support exam"
    Then the Bookit event details control "name" should be disabled
    And the Bookit event details control "institutionid" should be disabled
    And the Bookit event details control "starttime" should be disabled
    And the Bookit event details control "bookingstatus" should be disabled
    And the Bookit event details control "internalnotes" should be enabled

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
    When I select "Canceled" in the Bookit event details control "bookingstatus"
    And I submit the Bookit event details modal
    Then I should see "Canceled"

  Scenario: Booking person may self-cancel a new request and it leaves the active overview
    Given the following "mod_bookit > events" exist:
      | name                    | username    | personincharge_username | startdate                         | enddate                              | bookingstatus | institution |
      | Self-cancel booking     | bookinguser | bookinguser             | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow 14:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I open the Bookit event details for "Self-cancel booking"
    Then the Bookit event details control "bookingstatus" should be enabled
    When I select "Canceled" in the Bookit event details control "bookingstatus"
    And I submit the Bookit event details modal
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    Then the Bookit overview should list only the events ""
