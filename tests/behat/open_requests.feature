@mod @mod_bookit @javascript
Feature: Process open booking requests
  In order to handle incoming booking requests efficiently
  As a service-team user
  I need a dedicated open-requests view with direct workflow actions.

  Background:
    Given the following "users" exist:
      | username    | firstname | lastname |
      | susiservice | Susi      | Service  |
      | bertbooking | Bert      | Booking  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname   | name         | archetype |
      | serviceteam | Service-Team | student   |
      | bookingrole | Booking Role | student   |
    And the following "role capability" exists:
      | role                             | serviceteam |
      | mod/bookit:view                  | allow       |
      | mod/bookit:viewownoverview       | allow       |
      | mod/bookit:managebasics          | allow       |
      | mod/bookit:viewalldetailsofevent | allow       |
    And the following "role capability" exists:
      | role                                | bookingrole |
      | mod/bookit:view                     | allow       |
      | mod/bookit:viewownoverview          | allow       |
      | mod/bookit:viewalldetailsofownevent | allow       |
    And the following "course enrolments" exist:
      | user        | course | role        |
      | susiservice | C1     | serviceteam |
      | bertbooking | C1     | bookingrole |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |

  Scenario: Service team sees open requests in the main activity tabs without a second tab row
    Given the following "mod_bookit > events" exist:
      | name            | startdate                         | enddate                              | bookingstatus | institution |
      | Exam Physics II | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "susiservice"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    Then the Bookit main tabs should contain "Open requests"
    And the Bookit main tabs should not contain "Rejected requests"
    And the Bookit overview should show 1 activity tab row
    And the Bookit request workspace switch should contain "Open requests"
    And the Bookit request workspace switch should contain "Accepted bookings"
    And the Bookit request workspace switch should contain "Rejected requests"
    And the Bookit request workspace tab "Open requests" should be active
    And the Bookit overview should not show legacy inner navigation
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
    And the Bookit main tabs should contain "Open requests"
    And the Bookit overview should show 1 activity tab row
    When I open the Bookit overview "acceptedrequests" for "My BookIt Activity"
    Then I should see "Exam Chemistry I"
    And I should see "Confirmed"
    And the Bookit request workspace tab "Accepted bookings" should be active
    When I open the Bookit overview "myevents" for "My BookIt Activity"
    Then I should see "Exam Chemistry I"
    And I should see "Confirmed"

  Scenario: Service team reaches rejected requests inside the request workspace and keeps direct routes working
    Given the following "mod_bookit > events" exist:
      | name              | startdate                         | enddate                              | bookingstatus | institution |
      | Rejected oral exam | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 4 | 1 |
    When I log in as "susiservice"
    And I open the Bookit overview "rejectedrequests" for "My BookIt Activity"
    Then the Bookit main tabs should contain "Open requests"
    And the Bookit main tabs should not contain "Rejected requests"
    And the Bookit overview should show 1 activity tab row
    And the Bookit request workspace switch should contain "Open requests"
    And the Bookit request workspace switch should contain "Accepted bookings"
    And the Bookit request workspace switch should contain "Rejected requests"
    And the Bookit request workspace tab "Rejected requests" should be active
    And the Bookit overview should not show legacy inner navigation
    Then I should see "Rejected oral exam"
    And I should see "Workflow history"
    When I click the open request action "Reactivate as new request" for event "Rejected oral exam"
    When I open the Bookit overview "rejectedrequests" for "My BookIt Activity"
    Then I should not see "Rejected oral exam"
    And I should see "There are currently no rejected booking requests in the trash queue."
    And the Bookit request workspace switch should contain "Rejected requests"
    When I open the Bookit overview "openrequests" for "My BookIt Activity"
    Then I should see "Rejected oral exam"
    And the Bookit request workspace tab "Open requests" should be active
    And I should see "Reactivated as new request"

  Scenario: Service team rejects an open request and the governed workspace refresh moves it to rejected requests
    Given the following "mod_bookit > events" exist:
      | name               | startdate                         | enddate                              | bookingstatus | institution |
      | Reject me directly | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 0             | 1           |
    When I log in as "susiservice"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    And I click the open request action "Reject" for event "Reject me directly"
    Then I should see "There are currently no open booking requests."
    And the Bookit main tabs should contain "Open requests"
    When I open the Bookit overview "rejectedrequests" for "My BookIt Activity"
    Then I should see "Reject me directly"
    And I should see "Rejected"

  Scenario: Non-service-team users do not see request navigation even on direct request routes
    Given the following "mod_bookit > events" exist:
      | name              | startdate                         | enddate                              | bookingstatus | institution |
      | Hidden service request | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 0 | 1 |
    When I log in as "bertbooking"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    Then the Bookit main tabs should not contain "Open requests"
    And the Bookit request workspace switch should not contain "Rejected requests"
    And the Bookit overview should show 1 activity tab row
    When I open the Bookit overview "rejectedrequests" for "My BookIt Activity"
    Then the Bookit main tabs should not contain "Open requests"
    And the Bookit request workspace switch should not contain "Rejected requests"
    When I open the Bookit overview "acceptedrequests" for "My BookIt Activity"
    Then the Bookit main tabs should not contain "Open requests"
    And the Bookit request workspace switch should not contain "Accepted bookings"
