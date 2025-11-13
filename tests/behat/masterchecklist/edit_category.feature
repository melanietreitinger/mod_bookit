@mod @mod_bookit @javascript

Feature: Edit master checklist category
  In order to manage master checklists for bookit activities
  As an administrator and user with the role bookit_serviceteam
  I need to be able to view and edit master checklists

  Background:
    Given the following "users" exist:
      | username     | firstname | lastname | email                    |
      | serviceteam1 | Service   | Team     | serviceteam@example.com |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt > General Settings" in site administration
    And I click on "Run install helper" "link"
    And the following "role assigns" exist:
      | user         | role               | contextlevel | reference |
      | serviceteam1 | bookit_serviceteam | System       |           |
    And I log out

  Scenario Outline: Admin and Service-Team can edit a master checklist category
    Given I log in as "<user>"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
    And I should see "Exam Preparation"
    And I click on "button[id^='edit-checklistcategory-']" "css_element" in the "Exam Preparation" "table_row"
    And I should see "Save"
    And I set the field "Category name" to "Exam Preparation EDITED"
    And I click on "button[data-action='save']" "css_element"
    Then I should see "Exam Preparation EDITED"

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |
