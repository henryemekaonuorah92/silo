Feature: Inventory basic movements

  Background:
    Given a Product "X"

  Scenario: Location can be moved with an Operation
    Given Locations A,B
    And B has no parent
    And an Operation "one" to A moving B
    When "one" is executed
    Then B parent is A
    And show Inventory:Location

  Scenario: Batch can be created in a Location with an Operation
    Given Locations A
    And an Operation "two" to A with:
      | X | 10 |
    When "two" is executed
    Then A contains:
      | X | 10 |



  Scenario: Location has to be moved with a credible Operation

  Scenario: Basic movemement

    And a Location "A" with:
      | X | 10 |
    And a Location "B"
    Given an Operation "basic" from "A" to "B" with:
      | X | 10 |
    #When "basic" is executed
    Then "A" contains nothing
    And "B" contains:
      | X | 10 |

  Scenario: Replay to any point in time for stock calculation
