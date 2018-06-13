# Data organisation
Silo solves inventory managment problems thanks to a specifically designed data structure.

## Inventory and Product
Inventory is of course the purpose that led you reading this documentation. It is an organised collection of objects that you are trying to track and dispatch. These object are conveniently called here Products, even if you are actually inventorying books.

## Batch
A Batch is a collection of many Products of the same kind.

## Location
Location represents a physical place of the Inventory. It can hold other Locations, and of course Batches.

Observative reader would note that this designs a tree graph of Locations.

Note that each Silo installation has a root Location, which is called "root", which contains everything in your Inventory. A Location cannot exist outside of it.

@todo maybe we should call the root Location "Inventory"... 

## Operation
Operation allows you to change the Inventory organisation. It moves either a Location, or a collection of Batches, from one Location to another. They are correspondingly called source and target.

An Operation has three possible statuses, Pending, Executed and Cancelled. An Executed Operation can be also Rollbacked.

An Operation has a type.

## OperationSet

## User
Of course, things happening in the Inventory have to be tracked.

## Modifier

