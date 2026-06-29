@mod @mod_bookit @javascript
Feature: Filter reporting data and block past-date saves
  In order to trust reporting results and booking validation
  As a Bookit user
  I need filterable overview results and role-safe past-date handling.

  Background:
    Given the following "users" exist:
      | username    | firstname | lastname |
      | susiservice | Susi      | Service  |
      | bookinguser | Bella     | Booker   |
      | examineruser | Eddie    | Examiner |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname   | name         | archetype |
      | serviceteam | Service-Team | student   |
      | bookitparticipant | Bookit Participant | student |
    And the following "role capability" exists:
      | role                             | serviceteam |
      | mod/bookit:view                  | allow       |
      | mod/bookit:viewownoverview       | allow       |
      | mod/bookit:managebasics          | allow       |
      | mod/bookit:viewalldetailsofevent | allow       |
      | mod/bookit:addevent              | allow       |
      | mod/bookit:editevent             | allow       |
    And the following "role capability" exists:
      | role                              | bookitparticipant |
      | mod/bookit:view                   | allow             |
      | mod/bookit:viewownoverview        | allow             |
      | mod/bookit:viewalldetailsofownevent | allow           |
    And the following "course enrolments" exist:
      | user        | course | role        |
      | susiservice | C1     | serviceteam |
      | bookinguser | C1     | bookitparticipant |
      | examineruser | C1    | bookitparticipant |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |
    And the following "mod_bookit > institutions" exist:
      | name                 |
      | Standard-Institution |
    And the following "mod_bookit > rooms" exist:
      | name         | shortname | seats |
      | Default room | DEF       | 0     |

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

  Scenario: Booking person saving an event with a forced past start time is rejected
    Given the following "mod_bookit > events" exist:
      | name                | username    | startdate            | enddate              | bookingstatus | institution |
      | Future validation exam | bookinguser | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S## | 0             | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I open the Bookit event details for "Future validation exam"
    And I set the Bookit event details control "starttime" to a past timestamp
    And I align the Bookit event details startdate with the selected starttime
    And I submit the Bookit event details modal expecting validation error "You cannot enter events in the past."

  Scenario: Examiner saving an event with a forced past start time is rejected
    Given the following "mod_bookit > events" exist:
      | name                | username    | otherexaminer_usernames | startdate            | enddate              | bookingstatus | institution |
      | Future examiner exam | bookinguser | examineruser           | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S## | 0             | 1 |
    When I log in as "examineruser"
    And I open the Bookit overview "myevents" for "My BookIt Activity"
    And I open the Bookit event details for "Future examiner exam"
    And I set the Bookit event details control "starttime" to a past timestamp
    And I align the Bookit event details startdate with the selected starttime
    And I submit the Bookit event details modal expecting validation error "You cannot enter events in the past."

  Scenario: Service-team may save an event with a forced past start time
    Given the following "mod_bookit > events" exist:
      | name                | username    | personincharge_username | startdate            | enddate              | bookingstatus | institution |
      | Future service exam | susiservice | susiservice             | ##tomorrow 09:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S## | 0             | 1 |
    When I log in as "susiservice"
    And I open the Bookit reporting overview for "My BookIt Activity" from "-30 days" to "+30 days" with semesters "current"
    And I open the Bookit event details for "Future service exam"
    Then the Bookit event details control "name" should be enabled
    And the Bookit event details control "starttime" should be enabled
    And I close the currently open dialog
    And I open the Bookit event details for "Future service exam"
    And I set the Bookit event details control "starttime" to a past timestamp
    And I align the Bookit event details startdate with the selected starttime
    And I submit the Bookit event details modal
    And I open the Bookit reporting overview for "My BookIt Activity" from "-30 days" to "+30 days" with semesters "current"
    Then I should see "Future service exam"

  Scenario: Service-team may resubmit a historical event without a refreshed slot
    Given the following "mod_bookit > events" exist:
      | name                    | username    | personincharge_username | startdate                      | enddate                        | bookingstatus | institution |
      | Historical service exam | susiservice | susiservice             | ##-1 day 09:00##%Y-%m-%dT%H:%M:%S## | ##-1 day 11:00##%Y-%m-%dT%H:%M:%S## | 4 | 1 |
    When I log in as "susiservice"
    And I open the Bookit overview "rejectedrequests" for "My BookIt Activity"
    And I open the Bookit event details for "Historical service exam"
    And I restore the Bookit event details starttime selection after slot refresh
    And I submit the Bookit event details modal
    And I open the Bookit overview "rejectedrequests" for "My BookIt Activity"
    Then I should see "Historical service exam"

  Scenario: Booking person sees a blocked historical modal instead of a free edit path
    Given the following "mod_bookit > events" exist:
      | name                 | username    | personincharge_username | startdate                      | enddate                        | bookingstatus | institution |
      | Historical booking   | bookinguser | bookinguser             | ##-1 day 09:00##%Y-%m-%dT%H:%M:%S## | ##-1 day 11:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "bookinguser"
    And I open the Bookit overview "history" for "My BookIt Activity"
    And I set the Bookit overview booking status filter to "0"
    And I apply the Bookit overview filters
    Then I should see "Historical booking"
    And I open the Bookit event details for "Historical booking"
    Then the Bookit event details control "name" should be disabled
    And the Bookit event details control "starttime" should be disabled
    And the Bookit event details primary action should be "OK"
    And I should see "This booking already started and can no longer be changed by participants."

  Scenario: Examiner sees the same historical blocked state as the booking person
    Given the following "mod_bookit > events" exist:
      | name                    | username    | otherexaminer_usernames | startdate                      | enddate                        | bookingstatus | institution |
      | Historical examiner exam | bookinguser | examineruser            | ##-1 day 09:00##%Y-%m-%dT%H:%M:%S## | ##-1 day 11:00##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "examineruser"
    And I open the Bookit overview "history" for "My BookIt Activity"
    And I set the Bookit overview booking status filter to "0"
    And I apply the Bookit overview filters
    And I open the Bookit event details for "Historical examiner exam"
    Then the Bookit event details control "name" should be disabled
    And the Bookit event details control "starttime" should be disabled
    And the Bookit event details primary action should be "OK"
    And I should see "This booking already started and can no longer be changed by participants."
