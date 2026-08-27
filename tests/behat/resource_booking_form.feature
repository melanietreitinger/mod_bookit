@mod @mod_bookit @javascript
Feature: Resource integration in the BookIt booking workflow
  In order to request resources for a booked event
  As a serviceteam member or examiner
  I need to see available resources and access event resource pages

  Background:
    Given the following "users" exist:
      | username    | firstname | lastname   |
      | susiservice | Susi      | Service    |
      | bookinguser | Bella     | Booker     |
      | examiner1   | Emma      | Examiner   |
      | examiner2   | Otto      | Observer   |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname   | name         | archetype |
      | serviceteam | Service-Team | student   |
      | examiner    | Examiner     | student   |
      | requester   | Requester    | student   |
    And the following "role capability" exists:
      | role                             | serviceteam |
      | mod/bookit:addevent              | allow       |
      | mod/bookit:editevent             | allow       |
      | mod/bookit:view                  | allow       |
      | mod/bookit:viewalldetailsofevent | allow       |
      | mod/bookit:managebasics          | allow       |
      | mod/bookit:viewownoverview       | allow       |
    And the following "role capability" exists:
      | role                             | examiner |
      | mod/bookit:view                  | allow    |
      | mod/bookit:viewownoverview       | allow    |
    And the following "role capability" exists:
      | role                             | requester |
      | mod/bookit:addevent              | allow     |
      | mod/bookit:view                  | allow     |
      | mod/bookit:viewownoverview       | allow     |
    And the following "course enrolments" exist:
      | user        | course | role        |
      | susiservice | C1     | serviceteam |
      | examiner1   | C1     | examiner    |
      | examiner2   | C1     | examiner    |
      | bookinguser | C1     | requester   |
    And the following "activities" exist:
      | activity | name               | course | idnumber |
      | bookit   | My BookIt Activity | C1     | 1        |
    And the following "mod_bookit > institutions" exist:
      | name                 |
      | Standard-Institution |
    And the following "mod_bookit > rooms" exist:
      | name         | shortname | seats |
      | Default room | DEF       | 0     |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I check "Enable resources module"
    And I press "Save changes"
    And I log out

  # The booking form is a JavaScript modal opened from the calendar.
  # Direct calendar interaction is not reliably testable in Behat
  # (see also editevent.feature TODO). These scenarios test what is accessible.

  Scenario: Serviceteam user sees the Request booking button in the calendar view
    Given I log in as "susiservice"
    And I am on "Course 1" course homepage
    When I follow "My BookIt Activity"
    Then I should see "My BookIt Activity"
    And I should see "Request booking"

  Scenario: User without addevent capability does not see Request booking button
    Given I log in as "examiner1"
    And I am on "Course 1" course homepage
    When I follow "My BookIt Activity"
    Then I should see "My BookIt Activity"
    And I should not see "Request booking"

  Scenario: Serviceteam can navigate to the BookIt overview page
    Given I log in as "susiservice"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    When I follow "Request workspace" in the Bookit secondary navigation
    Then I should see "Request workspace"

  Scenario: Overview page shows resources column header
    Given the following "mod_bookit > events" exist:
      | name       | username     | startdate                         | enddate                              | bookingstatus | institution |
      | Test Event | susiservice  | ##today noon##%Y-%m-%dT%H:%M:%S## | ##tomorrow noon##%Y-%m-%dT%H:%M:%S## | 1             | 1           |
    And I log in as "susiservice"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    When I follow "Request workspace" in the Bookit secondary navigation
    Then I should see "Test Event"
    And I should see "Resources"

  Scenario: Admin can access resource admin pages to manage bookable resources
    Given the following "mod_bookit > resource_categories" exist:
      | name                |
      | Technical Equipment |
    And the following "mod_bookit > resources" exist:
      | name      | category_name        |
      | Projector | Technical Equipment  |
      | Laptop    | Technical Equipment  |
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    Then I should see "Technical Equipment"
    And I should see "Projector"
    And I should see "Laptop"

  @javascript
  Scenario: Resources not assigned to the selected room are disabled in the booking form
    Given the following "mod_bookit > rooms" exist:
      | name   | shortname |
      | Room A | RA        |
      | Room B | RB        |
    And the following "mod_bookit > resource_categories" exist:
      | name       |
      | Filter Cat |
    And the following "mod_bookit > resources" exist:
      | name       | category_name | rooms  |
      | Resource A | Filter Cat    | Room A |
      | Resource B | Filter Cat    | Room B |
    And I log in as "susiservice"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    And I change window size to "large"
    When I click on ".ec-addButton" "css_element"
    And I wait "2" seconds
    And I select "Room A" from the "Room" field
    And I wait "2" seconds
    Then the resource "Resource A" should be enabled in the booking form
    And the resource "Resource B" should be disabled in the booking form

  @javascript
  Scenario: All-rooms resources remain enabled regardless of room selection
    Given the following "mod_bookit > rooms" exist:
      | name   | shortname |
      | Room X | RX        |
    And the following "mod_bookit > resource_categories" exist:
      | name          |
      | AllRooms Cat  |
    And the following "mod_bookit > resources" exist:
      | name             | category_name | rooms  |
      | Room X Only      | AllRooms Cat  | Room X |
      | Universal Res    | AllRooms Cat  |        |
    And I log in as "susiservice"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    And I change window size to "large"
    When I click on ".ec-addButton" "css_element"
    And I wait "2" seconds
    And I select "Room X" from the "Room" field
    And I wait "2" seconds
    Then the resource "Room X Only" should be enabled in the booking form
    And the resource "Universal Res" should be enabled in the booking form

  @javascript
  Scenario: Switching rooms updates resource availability
    Given the following "mod_bookit > rooms" exist:
      | name   | shortname |
      | Room P | RP        |
      | Room Q | RQ        |
    And the following "mod_bookit > resource_categories" exist:
      | name      |
      | Switch Cat |
    And the following "mod_bookit > resources" exist:
      | name       | category_name | rooms  |
      | Resource P | Switch Cat    | Room P |
      | Resource Q | Switch Cat    | Room Q |
    And I log in as "susiservice"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    And I change window size to "large"
    When I click on ".ec-addButton" "css_element"
    And I wait "2" seconds
    And I select "Room P" from the "Room" field
    And I wait "2" seconds
    Then the resource "Resource P" should be enabled in the booking form
    And the resource "Resource Q" should be disabled in the booking form
    When I select "Room Q" from the "Room" field
    And I wait "2" seconds
    Then the resource "Resource P" should be disabled in the booking form
    And the resource "Resource Q" should be enabled in the booking form

  @javascript
  Scenario: Configured examiner pool is shared between requester and service team
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I set the field "Examiner pool usernames" to "examiner1"
    And I press "Save changes"
    And I log out
    And I log in as "susiservice"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    And I change window size to "large"
    When I click on ".ec-addButton" "css_element"
    And I wait "2" seconds
    Then the Bookit event details control "personinchargeid" should contain option "Emma Examiner | examiner1@example.com"
    And the Bookit event details control "personinchargeid" should not contain option "Otto Observer | examiner2@example.com"
    And the Bookit event details control "personinchargeid" should not contain option "Bella Booker | bookinguser@example.com"
    When I close the currently open dialog
    And I log out
    And I log in as "bookinguser"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    And I change window size to "large"
    When I click on ".ec-addButton" "css_element"
    And I wait "2" seconds
    Then the Bookit event details control "personinchargeid" should contain option "Emma Examiner | examiner1@example.com"
    And the Bookit event details control "personinchargeid" should not contain option "Otto Observer | examiner2@example.com"
    And the Bookit event details control "personinchargeid" should not contain option "Bella Booker | bookinguser@example.com"

  @javascript
  Scenario: Optional calendar fields stay aligned for requester and service team
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I set the field "Enabled optional calendar fields" to "notes"
    And I press "Save changes"
    And I log out
    And I log in as "susiservice"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    And I change window size to "large"
    When I click on ".ec-addButton" "css_element"
    And I wait "2" seconds
    Then the Bookit event details control "notes" should be enabled
    And the Bookit event details control "coursetemplate" should not be visible
    And the Bookit event details control "refcourseid" should not be visible
    When I close the currently open dialog
    And I log out
    And I log in as "bookinguser"
    And I am on "Course 1" course homepage
    And I follow "My BookIt Activity"
    And I change window size to "large"
    When I click on ".ec-addButton" "css_element"
    And I wait "2" seconds
    Then the Bookit event details control "notes" should be enabled
    And the Bookit event details control "coursetemplate" should not be visible
    And the Bookit event details control "refcourseid" should not be visible
