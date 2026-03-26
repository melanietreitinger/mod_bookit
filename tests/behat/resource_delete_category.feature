@mod @mod_bookit @javascript
Feature: Delete resource category
  In order to manage resources for bookit activities
  As an administrator
  I need to be able to delete empty resource categories reactively

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Run install helper" "link"
    And I log out

  Scenario: Admin can delete an empty resource category
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I should see "Add Category"
    And I set the field "Name" to "Category to Delete"
    And I click on "button[data-action='save']" "css_element"
    And I should see "Category to Delete"
    When I click on "button[data-action='edit-category']" "css_element" in the "Category to Delete" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Category to Delete"

  Scenario: Admin cannot delete a category that has resources
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I should see "Technical Equipment"
    When I click on "button[data-action='edit-category']" "css_element" in the "Technical Equipment" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    Then I should see "Error"
