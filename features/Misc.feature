Feature: I don't have a place for these yet

  @todo
  Scenario: Inspector can walk a hierarchy of Location
    Given a Product "X"
    And a Product "Y"
    And a Location A with:
      | X | 10 |
    And one add a child Location B to A
    And one fill Location B with:
      | X | 1  |
    Then Walker's inclusive total for A is:
      | X | 11 |

  @todo
  Scenario: Inspector can walk a hierarchy of Location
    Given a Product "X"
    And a Product "Y"
    And a Location A with:
      | X | 10 |
    And one add a child Location B to A
    And one fill Location B with:
      | X | 1  |
    And one add a child Location C to B
    And one fill Location C with:
      | X | 1  |
    # Let's create a nice loop here !
    And one move A to C
    Then Walker's inclusive total for A is:
      | X | 12 |

  Scenario: Batches with nothing left are removed from Location
  Scenario: Replay to any point in time for stock calculation