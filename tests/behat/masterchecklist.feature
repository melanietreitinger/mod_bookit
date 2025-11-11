@mod @mod_bookit @javascript

Feature: Edit the master checklist
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

  Scenario Outline: Admin and Service-Team can create a new master checklist category
    Given I log in as "<user>"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
    And I should see "Master checklist" in the "#page-header" "css_element"
    When I click on "add-checklist-category-button" "button"
    And I should see "Category name"
    And I should see "Required"
    And I set the following fields to these values:
      | Category name | My Test Category |
    And I press "Save changes"
    Then I should see "My Test Category"

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |

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

  Scenario Outline: Admin and Service-Team can delete a master checklist category
    Given I log in as "<user>"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
    And I should see "Exam Preparation"
    And I click on "button[id^='edit-checklistcategory-']" "css_element" in the "Exam Preparation" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Exam Preparation"

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |

  Scenario Outline: Admin and Service-Team can create a new master checklist item
    Given I log in as "<user>"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
    And I click on "add-checklist-item-button" "button"
    And I should see "Checklist item"
    And I set the following fields to these values:
      | Checklist item name      | My Test Item          |
      | Checklist category       | Exam Preparation      |
    And I set the field "roomids[]" to "Lecture Hall A"
    And I set the field "roleids[]" to "BookIt_Booking Person"
    And I press "Save changes"
    Then I should see "My Test Item"

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |

  Scenario Outline: Admin and Service-Team can edit a master checklist item
    Given I log in as "<user>"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
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

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |

  Scenario Outline: Admin and Service-Team can delete a master checklist item
    Given I log in as "<user>"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
    And I should see "Reserve room"
    And I click on "button[id^='edit-checklistitem-']" "css_element" in the "Reserve room" "table_row"
    And I should see "Delete"
    And I click on "button[data-action='delete']" "css_element"
    And I should see "Confirm"
    And I click on "button[data-action='delete']" "css_element"
    Then I should not see "Reserve room"

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |

  Scenario Outline: Edited notification slot messages are preserved regardless if the slot is active or not
    Given I log in as "<user>"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
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

    Examples:
      | user         |
      | admin        |
      | serviceteam1 |

  Scenario Outline: Admin and Service-Team can filter master checklist items by role and rooms
    Given I log in as "<user>"
    And I navigate to "Plugins > Activity modules > BookIt > Master checklist" in site administration
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

  # The existing drag and drop test steps are not sufficient for this use case and do not work properly.
  # Currently, manual testing is required for drag and drop.

  # Scenario: Admin and Service-Team can sort master checklist categories by drag and drop

  # Scenario: Admin and Service-Team can sort master checklist items by drag and drop