@mod @mod_bookit @javascript
Feature: Manage resource settings in the admin area
  In order to set up settings for resource bookings
  As an administrator
  I need to be able to view and manage the resource settings

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I log out

  Scenario: Admin can view the resource settings page
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    When I click on "Resource Checklist Settings" "link"
    Then I should see "Resource Settings"

  Scenario: Admin sees empty checklist message when no resources exist
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "Resource Checklist Settings" "link"
    Then I should see "No categories yet"

  Scenario: Admin can view auto-generated checklist after creating a resource
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
    And I click on "Resource Checklist Settings" "link"
    Then I should see "Beamer"

  Scenario: Admin can edit a resource settings item
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
    And I click on "Resource Checklist Settings" "link"
    And I should see "Projector"
    When I click on "[data-action='edit-item']" "css_element"
    Then I should see "Save"
    And I click on "button[data-action='cancel']" "css_element"
    And I should see "Projector"
