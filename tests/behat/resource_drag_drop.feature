@mod @mod_bookit @javascript
Feature: Drag and drop reordering of resource items and categories
  In order to manage the display order of resources
  As an administrator
  I need to be able to reorder resource items and categories by dragging and dropping

  Background:
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "Category Alpha"
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Item One"
    And I set the field "Category" to "Category Alpha"
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Item Two"
    And I set the field "Category" to "Category Alpha"
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "[data-action='add-category']" "css_element"
    And I set the field "Name" to "Category Beta"
    And I click on "button[data-action='save']" "css_element"
    And I wait "1" seconds
    And I click on "[data-action='add-resource']" "css_element"
    And I set the field "Name" to "Item Three"
    And I set the field "Category" to "Category Beta"
    And I click on "button[data-action='save']" "css_element"
    And I wait "2" seconds
    And I log out

  Scenario: Drag item below the next item in the same category (drop after)
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I wait "2" seconds
    And resource item "Item One" should appear before resource item "Item Two"
    When I drag resource item "Item One" after resource item "Item Two"
    And I wait "2" seconds
    Then resource item "Item Two" should appear before resource item "Item One"
    And I reload the page
    And I wait "2" seconds
    And resource item "Item Two" should appear before resource item "Item One"

  Scenario: Drag item to appear before the first item in the same category (drop before)
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I wait "2" seconds
    And resource item "Item One" should appear before resource item "Item Two"
    When I drag resource item "Item Two" before resource item "Item One"
    And I wait "2" seconds
    Then resource item "Item Two" should appear before resource item "Item One"
    And I reload the page
    And I wait "2" seconds
    And resource item "Item Two" should appear before resource item "Item One"

  Scenario: Drag last category before the first category
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I wait "2" seconds
    When I drag resource category "Category Beta" before resource category "Category Alpha"
    And I wait "2" seconds
    Then I should see "Category Beta"
    And I should see "Category Alpha"
    And I reload the page
    And I wait "2" seconds
    And I should see "Category Beta"

  Scenario: Drag first category after the last category
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I wait "2" seconds
    When I drag resource category "Category Alpha" after resource category "Category Beta"
    And I wait "2" seconds
    Then I should see "Category Alpha"
    And I should see "Category Beta"
    And I reload the page
    And I wait "2" seconds
    And I should see "Category Alpha"

  Scenario: Dragging a category must not show drop indicators on item rows
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Plugins > Activity modules > BookIt" in site administration
    And I click on "Resources" "link"
    And I wait "2" seconds
    When I drag resource category "Category Beta" before resource category "Category Alpha"
    And I wait "1" seconds
    Then no resource item should have a drop indicator
