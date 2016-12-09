Feature: Inventory basic movements

  Background:
    Given a Product "X"

  Scenario: Location can be moved with an Operation
    Given Locations A,B
    And B has no parent
    And an Operation "one" to A moving B
    When "one" is executed
    Then B parent is A

  Scenario: Batch can be created in a Location with an Operation
    Given Locations A
    And an Operation "two" to A with:
      | X | 10 |
    When "two" is executed
    Then A contains:
      | X | 10 |

  Scenario: Moving Batch from one Location to another
    Given a Location A with:
      | X | 10 |
    And a Location B with:
      | X | 1 |
    And an Operation "three" from A to B with:
      | X | 6 |
    When "three" is executed
    # Then show Inventory:Location,Inventory:Batch,Inventory:Operation
    Then A contains:
      | X | 4 |
    And B contains:
      | X | 7 |

  Scenario: Inspector can walk a hierarchy of Location
    Given a Location A with:
      | X | 10 |
    And a Location B with:
      | X | 1 |
    And an Operation "four" to A moving B
    And "four" is executed
    Then Walker's inclusive total for A is:
      | X | 11 |

  Scenario: Location has to be moved with a credible Operation
  Scenario: Replay to any point in time for stock calculation
