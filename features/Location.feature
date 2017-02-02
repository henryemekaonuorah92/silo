Feature: Basic and special Location actions

  Background:
    Given a Product "X"

    # These scenarios use /inventory/location endpoints

    # Location root is a special Location
    # as the name suggests, it is the root of the Location tree
    # Henceforth, you cannot move it or change it
    # It can only contain children Locations and no batches
  Scenario: Location root exists at all time
    Then Location root exists

    # The only way to create a Location is to attach it
    # to a parent with an Operation
  Scenario: Location can be created
    When one add a child Location A to root
    Then Location A exists

  Scenario: Location can be moved around
    Given Locations A,B
    When one add a child Location C to A
    And one move C to B
    Then C is in B

    # Removing a Location will cancel all pending Operations
    # related to it
  Scenario: Location can be deleted
    Given a Location A
    And an Operation "one" to A with:
      | X | 10 |
    When one delete Location A
    Then Operation "one" is cancelled
    And Location A does not exist

    # Modifiers can be added to any Location
  Scenario: Add a modifier to a Location
    Given a Location A
    When one assign modifier "surplus" to A
    Then A has "surplus" modifier
    When one remove modifier "surplus" from A
    Then A has no "surplus" modifier