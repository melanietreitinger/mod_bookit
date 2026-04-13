@mod @mod_bookit @javascript
Feature: Manage resources in the admin area
  In order to manage resources for bookit activities
  As an administrator
  I need to be able to create, edit, and delete resource categories and resources

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I log out

  Scenario: Admin can create a new resource category
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I should see "Add Category"
    When I click on "[data-action='add-category']" "css_element"
    Then I should see "Add Category"
    And I set the field "Name" to "Test Category"
    And I set the field "Description" to "A test category"
    And I click on "button[data-action='save']" "css_element"
    And I should see "Test Category"

  Scenario: Admin can edit a resource category
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "Original Category"
    And I click on "button[data-action='save']" "css_element"
    And I should see "Original Category"
    When I click on "[data-action='edit-category']" "css_element"
    Then I should see "Edit Category"
    And I set the field "Name" to "Renamed Category"
    And I click on "button[data-action='save']" "css_element"
    And I wait "3" seconds
    And I reload the page
    And I should see "Renamed Category"
    And I should not see "Original Category"

  Scenario: Admin can create a new resource
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I should see "Equipment"
    When I click on "[data-action='add-resource']" "css_element"
    Then I should see "Add Resource"
    And I set the field "Name" to "Projector"
    And I set the field "Category" to "Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I should see "Projector"

  Scenario: Admin can edit a resource
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I should see "Equipment"
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Laptop"
    And I set the field "Category" to "Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I should see "Laptop"
    When I click on "[data-action='edit-item']" "css_element"
    Then I should see "Edit Resource"
    And I set the field "Name" to "Desktop"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I should see "Desktop"
    And I should not see "Laptop" in the "#mod-bookit-resource-table" "css_element"

  Scenario: Admin can toggle a resource active status
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I should see "Equipment"
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Camera"
    And I set the field "Category" to "Equipment"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I should see "Camera"
    When I click on "[data-action='toggle-active']" "css_element"
    And I wait "2" seconds
    Then I should see "Camera"
