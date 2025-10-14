@mod @mod_bookit @javascript

Feature: Edit the master checklist
  In order to manage master checklists for bookit activities
  As an administrator
  I need to be able to view and edit master checklists

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt > General Settings" in site administration
    And I click on "Run install helper" "link"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration

  Scenario: Admin can edit a master checklist item
    Given I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Save"
    And I set the following fields to these values:
      | Checklist item name | Reserve room EDITED |
      | Rooms               | Lecture Hall A      |
      | Role               | BookIt_Observer |
    And I click on "button[data-action='save']" "css_element"
    Then I should see "Reserve room EDITED"
    And I should see "Lecture Hall A"
    And I should see "BookIt_Observer"
