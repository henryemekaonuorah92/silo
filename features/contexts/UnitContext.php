<?php

use Behat\Behat\Context\BehatContext;
use Silo\Inventory\Model as Inventory;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//
require_once __DIR__.'/../../../../../../vendor/autoload.php';

/**
 * Features context.
 */
class UnitContext extends BehatContext
{
    /**
     * @Then /^BatchCollection can Diff another one$/
     */
    public function batchcollectionCanDiffAnotherOne()
    {
        $a = new Inventory\Product('a');
        $b = new Inventory\Product('b');

        $collection = new Inventory\BatchCollection();
        $collection->addProduct($a, 5);

        $operand = new Inventory\BatchCollection();
        $operand->addProduct($b, 5);
        $operand->addProduct($a, 3);

        $result = $collection->diff($operand);
        $this->assertTrue($result->contains(new Inventory\Batch($a, 2)));
        $this->assertTrue($result->contains(new Inventory\Batch($b, -5)));
    }

    private function assertTrue($flag)
    {
        if ($flag !== true) {
            throw new \Exception('Expected true but got '.var_export($flag, true));
        }
    }
}
