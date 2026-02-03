@mod @mod_bookit @javascript

Feature: Delete master checklist item
  In order to manage master checklists for bookit activities
  As an administrator and user with the role bookit_serviceteam
  I need to be able to view and edit master checklists

  Background:
    Given the following "users" exist:
      | username     | firstname | lastname | email                    |
      | serviceteam1 | Service   | Team     | serviceteam@example.com |
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Run install helper" "link"
    And the following "role assigns" exist:
      | user         | role               | contextlevel | reference |
      | serviceteam1 | bookit_serviceteam | System       |           |
    And I log out

  Scenario: Admin can delete a master checklist item
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Master checklist" "link"
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Reserve room"

  Scenario: Service-Team can delete a master checklist item
    Given I log in as "serviceteam1"
    And I change window size to "large"
    And I click on "BookIt" "link" in the ".primary-navigation" "css_element"
    And I click on "Master checklist" "link"
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Reserve room"
