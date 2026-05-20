@mod @mod_bookit @javascript
Feature: Governed room availability reads stay permission-aware and consistent
  In order to trust room availability during the read-model rollout
  As a Bookit administrator
  I need the room admin workspace to expose the governed slot and blocker projection consistently.

  Background:
    Given the following "mod_bookit > rooms" exist:
      | name               | shortname | location |
      | Governed Room      | GR-1      | Campus A |
      | Other Governed Room | GR-2     | Campus B |

  Scenario: Room availability shows the governed blocker projection for the selected room only
    Given the following "mod_bookit > blockers" exist:
      | name                | room              | startdate                               | enddate                                 |
      | Governed maintenance | Governed Room    | ##tomorrow 10:00##%Y-%m-%dT%H:%M:%S##   | ##tomorrow 12:00##%Y-%m-%dT%H:%M:%S##   |
      | Other room blocker  | Other Governed Room | ##tomorrow 10:00##%Y-%m-%dT%H:%M:%S## | ##tomorrow 11:00##%Y-%m-%dT%H:%M:%S##   |
    When I log in as "admin"
    And I open the Bookit room availability for "Governed Room"
    Then I should see "Governed Room"
    And the Bookit room availability projection for room "Governed Room" from "tomorrow 00:00" to "tomorrow 23:59" should contain "Governed maintenance"
    And the Bookit room availability projection for room "Governed Room" from "tomorrow 00:00" to "tomorrow 23:59" should not contain "Other room blocker"
