@mod @mod_bookit
Feature: Fresh-install baseline and role preset setup
  In order to validate the third-pass standalone setup
  As a site administrator
  I need to see the baseline action and the shipped role preset downloads in BookIt settings

  Scenario: Admin can open the standalone setup section and trigger it once
    Given I log in as "admin"
    When I navigate to "Plugins > Activity modules > BookIt" in site administration
    Then I should see "Role presets"
    And I should see "Run install helper"
    And I should see "bookit_bookingperson.xml"
    And I should see "bookit_examiner.xml"
    And I should see "bookit_observer.xml"
    And I should see "bookit_serviceteam.xml"
    And I should see "bookit_supportonsite.xml"
    When I click on "Run install helper" "link"
    Then I should see "Install helper completed successfully."
