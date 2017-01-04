Feature: Inventory basic movements

  Background:
    Given a Product "X"
    And a Product "Y"

  Scenario: Location can be moved with an Operation
    Given Locations A,B
    And B has no parent
    And an Operation "one" to A moving B
    And "one" is typed as "stuff"
    When "one" is executed
    Then show Inventory:Location,Inventory:Batch,Inventory:Operation,Inventory:OperationType
    Then B parent is A

  Scenario: Batch can be created in a Location with an Operation
    Given Locations A
    And an Operation "two" to A with:
      | X | 10 |
    When "two" is executed
    Then A contains:
      | X | 10 |

  Scenario: Moving Batch from one Location to another
    # We also test that only wanted Batch is moved
    Given a Location A with:
      | X | 10 |
      | Y |  1 |
    And a Location B with:
      | X | 1 |
    And an Operation "three" from A to B with:
      | X | 6 |
    When "three" is executed
    # Then show Inventory:Location,Inventory:Batch,Inventory:Operation
    Then A contains:
      | X | 4 |
      | Y | 1 |
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

  # This seems to yield perf issues in Doctrine
  #Scenario: You can remove things from a Location
  #  Given a Location A with:
  #    | X | 1 |
  #  And an Operation "five" from A with:
  #    | X | 1 |
  #  Then A is empty

  Scenario: Batches with nothing left are removed from Location
  Scenario: Location has to be moved with a credible Operation
  Scenario: Replay to any point in time for stock calculation
