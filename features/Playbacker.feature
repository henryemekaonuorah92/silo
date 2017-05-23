Feature: Playbacker

  Background:
    Given a Product "X"

  Scenario: Replay a  few operation
    Given a Location A
    And an Operation "one" to A with:
      | X | 10 |
    And "one" is executed
    And an Operation "two" from A with:
      | X | 3 |
    And "two" is executed
    And an Operation "three" from A with:
      | X | 2 |
    And "three" is executed
    Then Playbacker for A at "one" gives:
      | X | 10 |
    And Playbacker for A at "two" gives:
      | X | 10 |
    And Playbacker for A at "three" gives:
      | X | 7 |
