Feature: Basic and special Operation actions

  # These scenarios use /inventory/operation endpoints

  Background:
    Given a Product "X"
    And a Product "Y"
    And a Location A with:
      | X | 10 |
    And a Location B
      | X | 1  |
    And an Operation "one" from A to B with:
      | X | 5  |

  Scenario: Operation can be executed
    When one execute Operation "one"
    Then A contains:
      | X | 5  |
    And B contains:
      | X | 6 |

  Scenario: Operation's Batches can be changed on execution
    When one execute Operation "one" with:
      | X | 2 |
    Then A contains:
      | X | 8  |
    And B contains:
      | X | 3 |
    And Operation "one" contains:
      | X | 2 |


  Scenario: Operation can transfer Batches between two Locations
  Scenario: Operation can be rollbacked
  Scenario: Operation can be cancelled
