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
    Then I should see "Master checklist" in the "#page-header" "css_element"
    When I click on "add-checklist-category-button" "button"
    And I should see "Category name"
    And I should see "Required"
    And I set the following fields to these values:
      | Category name | My Test Category |
    And I press "Save changes"
    Then I should see "My Test Category"

  Scenario: Admin can delete a master checklist category
    Given I should see "Exam Preparation"
    And I click on "button[id^='edit-checklistcategory-']" "css_element" in the "Exam Preparation" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Exam Preparation"

  Scenario: Admin can create a new master checklist item
    When I click on "add-checklist-item-button" "button"
    And I should see "Checklist item"
    And I set the following fields to these values:
      | Checklist item name      | My Test Item          |
      | Checklist category       | Exam Preparation      |
      | Rooms                    | Lecture Hall A        |
      | Role                    | BookIt_Booking Person  |
    And I press "Save changes"
    Then I should see "My Test Item"

  Scenario: Admin can edit a master checklist item
    Given I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Save"
    And I set the field "Checklist item name" to "Reserve room EDITED"
    And I click on "button[data-action='save']" "css_element"
    Then I should see "Reserve room EDITED"

  Scenario: Admin can delete a master checklist item
    Given I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Reserve room"
