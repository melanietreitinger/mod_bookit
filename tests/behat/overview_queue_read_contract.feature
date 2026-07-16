@mod @mod_bookit @javascript
Feature: Governed overview queue reads keep request workspaces consistent
  In order to trust operational request queues during the rollout
  As a service-team user
  I need open and rejected request workspaces to remain permission-aware and internally consistent.

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

  Scenario: Service team can switch between governed request workspaces
    Given the following "mod_bookit > events" exist:
      | name                 | startdate                               | enddate                                 | bookingstatus | institution |
      | Open queue exam      | ##today noon##%Y-%m-%dT%H:%M:%S##       | ##tomorrow noon##%Y-%m-%dT%H:%M:%S##    | 0             | 1           |
      | Rejected queue exam  | ##+2 days noon##%Y-%m-%dT%H:%M:%S##     | ##+3 days noon##%Y-%m-%dT%H:%M:%S##     | 4             | 1           |
    When I log in as "susiservice"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    Then the Bookit main tabs should contain "Request workspace"
    And the Bookit request workspace switch should contain "Open requests"
    And the Bookit request workspace switch should contain "Rejected and cancelled"
    And the Bookit request workspace tab "Open requests" should be active
    And the Bookit overview should not show legacy inner navigation
    And I should see "Open queue exam"
    When I open the Bookit overview "rejectedcancelled" for "My BookIt Activity"
    Then I should see "Rejected queue exam"
    And the Bookit request workspace tab "Rejected and cancelled" should be active
    And I should not see "Open queue exam"

  Scenario: Non-service users still do not gain request-workspace visibility
    Given the following "mod_bookit > events" exist:
      | name              | startdate                               | enddate                                 | bookingstatus | institution |
      | Hidden request    | ##today noon##%Y-%m-%dT%H:%M:%S##       | ##tomorrow noon##%Y-%m-%dT%H:%M:%S##    | 0             | 1           |
    When I log in as "bertbooking"
    And I open the Bookit overview "openrequests" for "My BookIt Activity"
    Then the Bookit request workspace switch should not contain "Rejected and cancelled"
    And the Bookit main tabs should not contain "Request workspace"
