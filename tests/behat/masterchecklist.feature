@mod @mod_bookit @javascript

Feature: Edit the master checklist
  In order to manage master checklists for bookit activities
  As an administrator
  I need to be able to view and edit master checklists

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt > General Settings" in site administration
    And I click on "Run install helper" "link"
    And I wait "1" seconds
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration


  Scenario: Admin can create a new master checklist category
    Given I wait "1" seconds
    Then I should see "Master checklist" in the "#page-header" "css_element"
    When I click on "add-checklist-category-button" "button"
    And I should see "Category name"
    And I should see "Required"
    And I set the following fields to these values:
      | Category name | My Test Category |
    And I press "Save changes"
    And I wait "1" seconds
    Then I should see "My Test Category"
