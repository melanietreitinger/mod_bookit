@mod @mod_bookit @javascript
Feature: Manage booking status notification settings
  In order to control booking-status notification templates clearly
  As an administrator
  I need concise guidance, prefilled templates, and per-status visibility toggles in BookIt settings

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration

  Scenario: Admin sees the concise intro and prefilled expressive templates
    Then I should see "Configure recipients and message templates for booking-status changes."
    And I should see "Supported placeholders:"
    And the field "Message subject for New" matches value "New booking request: ###EVENTNAME###"
    And the field "Message body for New" matches value "A new booking request ""###EVENTNAME###"" was submitted. Open booking: ###EVENTURL###"
    And the field "Message subject for In Progress" matches value "Booking request in progress: ###EVENTNAME###"
    And the field "Message body for In Progress" matches value "The booking request ""###EVENTNAME###"" is now in progress. Open booking: ###EVENTURL###"
    And the field "Message subject for Accepted" matches value "Booking request accepted: ###EVENTNAME###"
    And the field "Message body for Accepted" matches value "The booking request ""###EVENTNAME###"" was accepted. Open booking: ###EVENTURL###"
    And the field "Message subject for Canceled" matches value "Booking request canceled: ###EVENTNAME###"
    And the field "Message body for Canceled" matches value "The booking request ""###EVENTNAME###"" was canceled. Open booking: ###EVENTURL###"
    And the field "Message subject for Rejected" matches value "Booking request rejected: ###EVENTNAME###"
    And the field "Message body for Rejected" matches value "The booking request ""###EVENTNAME###"" was rejected. Open booking: ###EVENTURL###"

  Scenario: Admin can hide only one status template group with its own toggle
    When I uncheck "Send message for Rejected"
    And I press "Save changes"
    Then I should not see "Message subject for Rejected"
    And I should not see "Message body for Rejected"
    And I should see "Message subject for Accepted"
    And I should see "Message body for Accepted"
    When I check "Send message for Rejected"
    And I press "Save changes"
    Then the field "Message subject for Rejected" matches value "Booking request rejected: ###EVENTNAME###"
    And the field "Message body for Rejected" matches value "The booking request ""###EVENTNAME###"" was rejected. Open booking: ###EVENTURL###"
