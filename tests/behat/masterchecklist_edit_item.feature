@mod @mod_bookit @javascript

Feature: Edit master checklist item
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

  Scenario: Admin can edit a master checklist item
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Checklist" "link"
    And I click on "Master checklist" "link"
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Save"
    And I set the following fields to these values:
      | Checklist item name | Reserve room EDITED |
      | Checklist category  | Exam Day            |
      | Before exam         | 1                   |
    And I wait "1" seconds
    And I set the field "Time" to "14"
    And I set the field "roomids[]" to "Lecture Hall A, Seminar Room B"
    And I set the field "roleids[]" to "BookIt_Observer, BookIt_Service-Team"
    And I set the field "Before due" to "1"
    And I wait "1" seconds
    And I set the field "before_due_time[number]" to "7"
    And I set the field "before_due_messagetext[text]" to "This is my behat notification edit test message. Cool."
    And I set the field "Recipient" to "BookIt_Observer, BookIt_Service-Team"
    And I click on "button[data-action='save']" "css_element"
    Then I should see "Reserve room EDITED"
    And I should see "Lecture Hall A" in the "Reserve room EDITED" "table_row"
    And I should see "Seminar Room B" in the "Reserve room EDITED" "table_row"
    And I should see "BookIt_Observer" in the "Reserve room EDITED" "table_row"
    And I should see "BookIt_Service-Team" in the "Reserve room EDITED" "table_row"
    And I wait "1" seconds
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room EDITED" "table_row"
    And the field "before_due_messagetext[text]" matches value "This is my behat notification edit test message. Cool."
    And I click on "button[name='before_due_reset']" "css_element"
    And I wait "1" seconds
    And I should see "Confirm"
    And I wait "1" seconds
    And I click on "Reset" "button" in the "Confirm" "dialogue"
    And I wait "1" seconds
    Then the field "before_due_messagetext[text]" does not match value "This is my behat notification edit test message. Cool."

  Scenario: Service-Team can edit a master checklist item
    Given I log in as "serviceteam1"
    And I change window size to "large"
    And I click on "BookIt" "link" in the ".primary-navigation" "css_element"
    And I click on "Master checklist" "link"
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Save"
    And I set the following fields to these values:
      | Checklist item name | Reserve room EDITED ServiceTeam |
      | Checklist category  | Exam Day                        |
      | Before exam         | 1                               |
    And I wait "1" seconds
    And I set the field "Time" to "14"
    And I set the field "roomids[]" to "Lecture Hall A, Seminar Room B"
    And I set the field "roleids[]" to "BookIt_Observer, BookIt_Service-Team"
    And I set the field "Before due" to "1"
    And I wait "1" seconds
    And I set the field "before_due_time[number]" to "7"
    And I set the field "before_due_messagetext[text]" to "This is my behat notification edit test message. Cool."
    And I set the field "Recipient" to "BookIt_Observer, BookIt_Service-Team"
    And I click on "button[data-action='save']" "css_element"
    Then I should see "Reserve room EDITED ServiceTeam"
    And I should see "Lecture Hall A" in the "Reserve room EDITED ServiceTeam" "table_row"
    And I should see "Seminar Room B" in the "Reserve room EDITED ServiceTeam" "table_row"
    And I should see "BookIt_Observer" in the "Reserve room EDITED ServiceTeam" "table_row"
    And I should see "BookIt_Service-Team" in the "Reserve room EDITED ServiceTeam" "table_row"
    And I wait "1" seconds
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room EDITED ServiceTeam" "table_row"
    And the field "before_due_messagetext[text]" matches value "This is my behat notification edit test message. Cool."
    And I click on "button[name='before_due_reset']" "css_element"
    And I wait "1" seconds
    And I should see "Confirm"
    And I wait "1" seconds
    And I click on "Reset" "button" in the "Confirm" "dialogue"
    And I wait "1" seconds
    Then the field "before_due_messagetext[text]" does not match value "This is my behat notification edit test message. Cool."
