@mod @mod_bookit @javascript

Feature: Create master checklist item
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

  Scenario: Admin can create a new master checklist item
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Master checklist" "link"
    And I click on "add-checklist-item-button" "button"
    And I should see "Checklist item"
    And I set the following fields to these values:
      | Checklist item name      | My Test Item Admin    |
      | Checklist category       | Exam Preparation      |
    And I set the field "roomids[]" to "Lecture Hall A"
    And I set the field "roleids[]" to "BookIt_Booking Person"
    And I press "Save changes"
    Then I should see "My Test Item Admin"

  Scenario: Service-Team can create a new master checklist item
    Given I log in as "serviceteam1"
    And I change window size to "large"
    And I click on "BookIt" "link" in the ".primary-navigation" "css_element"
    And I click on "Master checklist" "link"
    And I click on "add-checklist-item-button" "button"
    And I should see "Checklist item"
    And I set the following fields to these values:
      | Checklist item name      | My Test Item ServiceTeam |
      | Checklist category       | Exam Preparation         |
    And I set the field "roomids[]" to "Lecture Hall A"
    And I set the field "roleids[]" to "BookIt_Booking Person"
    And I press "Save changes"
    Then I should see "My Test Item ServiceTeam"
