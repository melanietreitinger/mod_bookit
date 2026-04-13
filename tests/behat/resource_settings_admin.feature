@mod @mod_bookit @javascript
Feature: Manage resource settings in the admin area
  In order to set up settings for resource bookings
  As an administrator
  I need to be able to view and manage the resource settings

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I log out

  Scenario: Admin can open the resource settings modal
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "AV Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Beamer"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    When I click on "[data-action='open-settings']" "css_element"
    Then I should see "Resource checklist settings"

  Scenario: Admin sees resource name in the settings modal
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "AV Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Beamer"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    When I click on "[data-action='open-settings']" "css_element"
    Then I should see "Beamer"

  Scenario: Admin sees due date options in the settings modal
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "AV Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Projector"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    When I click on "[data-action='open-settings']" "css_element"
    Then I should see "Due date"

  Scenario: Admin can close the settings modal without saving
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "AV Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Projector"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I click on "[data-action='open-settings']" "css_element"
    And I should see "Resource checklist settings"
    When I click on "button[data-action='cancel']" "css_element"
    Then I should see "Projector"
