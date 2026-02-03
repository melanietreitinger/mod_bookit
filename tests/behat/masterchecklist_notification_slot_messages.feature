@mod @mod_bookit @javascript

Feature: Preserve notification slot messages
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

  Scenario: Admin - Edited notification slot messages are preserved regardless if the slot is active or not
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Master checklist" "link"
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I click on "Notifications" "link"
    And I set the field "Before due" to "1"
    And I wait "1" seconds
    And I set the field "before_due_time[number]" to "7"
    And I set the field "before_due_messagetext[text]" to "This is my behat notification edit test message. Cool."
    And I set the field "Recipient" to "BookIt_Observer, BookIt_Service-Team"
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I wait "1" seconds
    And the field "before_due_messagetext[text]" matches value "This is my behat notification edit test message. Cool."
    And I set the field "Before due" to "0"
    And I wait "1" seconds
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I click on "Notifications" "link"
    And I set the field "Before due" to "1"
    And the field "before_due_messagetext[text]" matches value "This is my behat notification edit test message. Cool."

  Scenario: Service-Team - Edited notification slot messages are preserved regardless if the slot is active or not
    Given I log in as "serviceteam1"
    And I change window size to "large"
    And I click on "BookIt" "link" in the ".primary-navigation" "css_element"
    And I click on "Master checklist" "link"
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I click on "Notifications" "link"
    And I set the field "Before due" to "1"
    And I wait "1" seconds
    And I set the field "before_due_time[number]" to "7"
    And I set the field "before_due_messagetext[text]" to "This is my behat notification edit test message. Cool."
    And I set the field "Recipient" to "BookIt_Observer, BookIt_Service-Team"
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I wait "1" seconds
    And the field "before_due_messagetext[text]" matches value "This is my behat notification edit test message. Cool."
    And I set the field "Before due" to "0"
    And I wait "1" seconds
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I click on "Notifications" "link"
    And I set the field "Before due" to "1"
    And the field "before_due_messagetext[text]" matches value "This is my behat notification edit test message. Cool."
