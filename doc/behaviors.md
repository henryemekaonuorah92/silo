### Silo\Inventory\Collection
Doctrine is used for the ORM.

Most 1-to-n relationships return a specialized Collection, like BatchCollection or OperationCollection. They provide usefull abstractions to perform computations.

### Silo\Inventory\Finder
Finders are wrappers around QueryBuilders providing business domain querying. For example:

    // Write this...
    $finder->isPending();
    
    // ...instead of this
    $query->andWhere($query->expr()->isNull('isDoneBy'));
    $query->andWhere($query->expr()->isNull('isCancelledBy'));

### Silo\Inventory\GC
Silo enforces Garbage Collection for all tables.