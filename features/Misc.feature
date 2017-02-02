Feature: I don't have a place for these yet

  Background:
    Given a Product "X"
    And a Product "Y"

  Scenario: Inspector can walk a hierarchy of Location
    Given a Location A with:
    | X | 10 |
    And a Location B with:
    | X | 1 |
    And an Operation "four" to A moving B
    And "four" is executed
    Then Walker's inclusive total for A is:
    | X | 11 |

  Scenario: Batches with nothing left are removed from Location
  Scenario: Replay to any point in time for stock calculation