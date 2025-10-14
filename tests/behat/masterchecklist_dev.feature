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

  Scenario: Admin can delete a master checklist item
    Given I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I wait "1" seconds
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I wait "1" seconds
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    And I wait "1" seconds
    Then I should not see "Reserve room"
