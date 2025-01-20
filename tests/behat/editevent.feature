@mod @mod_bookit @javascript
Feature: Edit the event form
  In order to change the data of an event
  As a serviceteam
  I need to be able to edit the event form.

  Background:
    Given the following "users" exist:
      | username    |
      | susiservice |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "roles" exist:
      | shortname   | name         | archetype |
      | serviceteam | Service-Team | student   |
    And the following "role capability" exists:
      | role                             | serviceteam |
      | mod/bookit:viewalldetailsofevent | allow       |
      | mod/bookit:addevent              | allow       |
      | mod/bookit:editevent             | allow       |
    And the following "course enrolments" exist:
      | user        | course | role        |
      | susiservice | C1     | serviceteam |
    And the following "activities" exist:
      | activity | name   |  course | idnumber |
      | bookit   | My BookIt Activity |  C1     | 1        |

    Scenario: Edit an event
      Given the following "mod_bookit > events" exist:
        | name            | startdate           | enddate             | bookingstatus | department         |
        | Exam Physics II | 2025-01-15 08:00:00 | 2025-01-15 10:00:00 | 1             | Physics Department |
      When I am on the "My BookIt Activity" "mod_bookit > view" page logged in as "susiservice"
      #Then I should see "My BookIt Activity"
      And I click on "[datetime=\"2025-01-15T08:00:00\"]" "css_element"
      Then I should see "Physics Department"

