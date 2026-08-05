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
    And I should see "Supported placeholders include"
    And the field "Message subject for New" matches value "Booking request received: ###EVENTNAME### on ###BOOKINGDATE###"
    And the Bookit editor field "Message body for New" should equal "Thank you for your booking request ""###EVENTNAME###"" for ###BOOKINGDATE###. We have received your request and will review it shortly."
    And the field "Message subject for In Progress" matches value "Booking request in review: ###EVENTNAME### on ###BOOKINGDATE###"
    And the Bookit editor field "Message body for In Progress" should equal "Thank you for your request ""###EVENTNAME###"" for ###BOOKINGDATE###. Some resources are still being reviewed. You will be notified shortly."
    And the field "Message subject for Confirmed" matches value "Booking request confirmed: ###EVENTNAME### on ###BOOKINGDATE###"
    And the Bookit editor field "Message body for Confirmed" should equal "Thank you for your request ""###EVENTNAME###"" for ###BOOKINGDATE###. We are pleased to confirm your booking."
    And the field "Message subject for Canceled" matches value "Booking request canceled: ###EVENTNAME### on ###BOOKINGDATE###"
    And the Bookit editor field "Message body for Canceled" should equal "Thank you for your request ""###EVENTNAME###"" for ###BOOKINGDATE###. Unfortunately, we must decline the request due to the current circumstances."
    And the field "Message subject for Rejected" matches value "Booking request rejected: ###EVENTNAME### on ###BOOKINGDATE###"
    And the Bookit editor field "Message body for Rejected" should equal "Thank you for your request ""###EVENTNAME###"" for ###BOOKINGDATE###. Unfortunately, we must decline the request due to the current circumstances."

  Scenario: Admin can hide only one status template group with its own toggle
    When I uncheck "Send message for Rejected"
    And I press "Save changes"
    Then I should not see "Message subject for Rejected"
    And I should not see "Message body for Rejected"
    And I should see "Message subject for Confirmed"
    And I should see "Message body for Confirmed"
    When I check "Send message for Rejected"
    And I press "Save changes"
    Then the field "Message subject for Rejected" matches value "Booking request rejected: ###EVENTNAME### on ###BOOKINGDATE###"
    And the Bookit editor field "Message body for Rejected" should equal "Thank you for your request ""###EVENTNAME###"" for ###BOOKINGDATE###. Unfortunately, we must decline the request due to the current circumstances."
