@mod @mod_bookit @javascript

Feature: Create master checklist category
  In order to manage master checklists for bookit activities
  As an administrator and user with the role bookit_serviceteam
  I need to be able to view and edit master checklists

  Background:
    Given the following "users" exist:
      | username     | firstname | lastname | email                    |
      | serviceteam1 | Service   | Team     | serviceteam@example.com |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Checklist" "link"
    And I click on "Run install helper" "link"
    And the following "role assigns" exist:
      | user         | role               | contextlevel | reference |
      | serviceteam1 | bookit_serviceteam | System       |           |
    And I log out

  Scenario: Admin can create a new master checklist category
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Checklist" "link"
    And I click on "Master checklist" "link"
    And I should see "Master checklist" in the "#page-header" "css_element"
    When I click on "add-checklist-category-button" "button"
    And I should see "Category name"
    And I should see "Required"
    And I set the following fields to these values:
      | Category name | My Test Category Admin |
    And I press "Save changes"
    Then I should see "My Test Category Admin"

  Scenario: Service-Team can create a new master checklist category
    Given I log in as "serviceteam1"
    And I change window size to "large"
    And I click on "BookIt" "link" in the ".primary-navigation" "css_element"
    And I click on "Master checklist" "link"
    And I should see "Master checklist" in the "#page-header" "css_element"
    When I click on "add-checklist-category-button" "button"
    And I should see "Category name"
    And I should see "Required"
    And I set the following fields to these values:
      | Category name | My Test Category ServiceTeam |
    And I press "Save changes"
    Then I should see "My Test Category ServiceTeam"
