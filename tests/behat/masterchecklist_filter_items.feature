@mod @mod_bookit @javascript

Feature: Filter master checklist items
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

  Scenario Outline: Admin and Service-Team can filter master checklist items by role and rooms
    Given I log in as "<user>"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Checklist" "link"
    And I click on "Master checklist" "link"
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I set the field "roomids[]" to "Lecture Hall A"
    And I set the field "roleids[]" to "BookIt_Observer"
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I set the field "roomid" to "Computer Lab C"
    And I should not see "Reserve room"
    And I wait "1" seconds
    And I set the field "roomid" to "No selection"
    And I should see "Reserve room"
    And I wait "1" seconds
    And I set the field "roleid" to "BookIt_Examiner"
    And I should not see "Reserve room"
    And I wait "1" seconds
    And I set the field "roleid" to "BookIt_Observer"
    And I should see "Reserve room"
    And I wait "1" seconds
    And I set the field "roomid" to "Computer Lab C"
    And I set the field "roleid" to "BookIt_Examiner"
    And I click on "add-checklist-item-button" "button"
    And I should see "Checklist item"
    And I set the following fields to these values:
      | Checklist item name      | My Test Item          |
      | Checklist category       | Exam Preparation      |
    And I set the field "roomids[]" to "Computer Lab"
    And I set the field "roleids[]" to "BookIt_Examiner"
    And I press "Save changes"
    And I should see "My Test Item"
    And I wait "1" seconds
    And I click on "add-checklist-item-button" "button"
    And I should see "Checklist item"
    And I set the following fields to these values:
      | Checklist item name      | My Second Test Item   |
      | Checklist category       | Exam Preparation      |
    And I set the field "roomids[]" to "Lecture Hall A"
    And I set the field "roleids[]" to "BookIt_Observer"
    And I press "Save changes"
    And I should not see "My Second Test Item"
    And I set the field "roomid" to "No selection"
    And I set the field "roleid" to "No selection"
    And I change window size to "large"
    And I should see "My Test Item"
    Then I should see "My Second Test Item"

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |
