@mod @mod_bookit @javascript
Feature: Delete resource item
  In order to manage resources for bookit activities
  As an administrator
  I need to be able to delete resource items reactively

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Run install helper" "link"
    And I log out

  Scenario: Admin can delete a resource item
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I should see "Microphone"
    When I click on "button[id^='edit-item-']" "css_element" in the "Microphone" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I wait "1" seconds
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Microphone"
