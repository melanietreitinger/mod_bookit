@mod @mod_bookit @javascript
Feature: Room filter on the resources overview page
  In order to find resources available in a specific room
  As an administrator
  I need to be able to filter the resources list by room

  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I log out

  @javascript
  Scenario: Resources assigned to a room are visible when that room filter is active
    Given the following "mod_bookit > rooms" exist:
      | name   | shortname |
      | Room F | RF        |
      | Room G | RG        |
    And the following "mod_bookit > resource_categories" exist:
      | name        |
      | Filter Test |
    And the following "mod_bookit > resources" exist:
      | name       | category_name | rooms  |
      | Resource F | Filter Test   | Room F |
      | Resource G | Filter Test   | Room G |
    And I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    Then I should see "Resource F"
    And I should see "Resource G"
    When I click on "Room F" "text"
    Then I should see "Resource F"
    And I should not see "Resource G"

  @javascript
  Scenario: Resources with null roomids (all rooms) remain visible with any room filter
    Given the following "mod_bookit > rooms" exist:
      | name   | shortname |
      | Room H | RH        |
    And the following "mod_bookit > resource_categories" exist:
      | name          |
      | AllRooms Test |
    And the following "mod_bookit > resources" exist:
      | name             | category_name | rooms  |
      | Room H Only      | AllRooms Test | Room H |
      | Universal Res    | AllRooms Test |        |
    And I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    Then I should see "Room H Only"
    And I should see "Universal Res"
    When I click on "Room H" "text"
    Then I should see "Room H Only"
    And I should see "Universal Res"

  @javascript
  Scenario: Deselecting a room filter restores all resources
    Given the following "mod_bookit > rooms" exist:
      | name   | shortname |
      | Room J | RJ        |
      | Room K | RK        |
    And the following "mod_bookit > resource_categories" exist:
      | name       |
      | Toggle Cat |
    And the following "mod_bookit > resources" exist:
      | name       | category_name | rooms  |
      | Resource J | Toggle Cat    | Room J |
      | Resource K | Toggle Cat    | Room K |
    And I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    When I click on "Room J" "text"
    Then I should see "Resource J"
    And I should not see "Resource K"
    When I click on "Room J" "text"
    Then I should see "Resource J"
    And I should see "Resource K"
