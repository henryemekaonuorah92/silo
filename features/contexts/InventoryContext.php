<?php

namespace Silo\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\ArrayCollection;
use Silo\Inventory\Model as Inventory;

/**
 * Silo\Inventory context
 */
class InventoryContext extends BehatContext implements AppAwareContextInterface, ClientContextInterface
{
    use AppAwareContextTrait;

    use ClientContextTrait;

    /**
     * @Given /^a Product "([^"]*)"$/
     */
    public function aProduct($sku)
    {
        $this->app['em']->persist(new Inventory\Product($sku));
        $this->app['em']->flush();
    }

    /**
     * @Given /^(?:a )?Locations? ([\w-,]+)(?: with:)?$/
     */
    public function aLocation($codes, TableNode $table = null)
    {
        foreach (explode(',', $codes) as $code) {
            $this->oneAddChildLocationTo($code);
            if ($table) {
                $this->oneFillLocationWith($code, $table);
            }
        }
    }

    /**
     * @When /^one add a child Location (\w+) to (\w+)$/
     */
    public function oneAddChildLocationTo($code, $parentCode = \Silo\Inventory\Model\Location::CODE_ROOT)
    {
        $this->client->request('POST', "/silo/inventory/location/$parentCode/child", ['name' => $code]);
        $response = $this->client->getResponse();
        $this->assertSuccessful($response);
    }

    /**
     * @Given /^one move (\w+) to (\w+)$/
     */
    public function oneMoveCToB($code, $parentCode)
    {
        $this->client->request('PATCH', "/silo/inventory/location/$parentCode/child", [$code]);
        $response = $this->client->getResponse();
        $this->assertSuccessful($response);
    }

    /**
     * @When /^one fill Location (\w+) with:$/
     */
    public function oneFillLocationWith($code, TableNode $table)
    {
        $this->client->request(
            'PATCH',
            "/silo/inventory/location/$code/batches",
            $this->transformBatch($table)
        );
        $response = $this->client->getResponse();
        $this->assertSuccessful($response);
    }

    /**
     * @When /^one assign modifier (\w+) to ([\w-]+)(?: with:)?$/
     */
    public function oneAddModifierTo($name, $code, \Behat\Gherkin\Node\PyStringNode $value = null)
    {
        if (!is_null($value)) {
            $value = ['value' => json_decode($value, true)];
        } else {
            $value = [];
        }
        $this->client->request(
            'POST',
            "/silo/inventory/location/$code/modifiers",
            ['name' => $name] + $value
        );
        $response = $this->client->getResponse();
        $this->assertSuccessful($response);
    }

    private function transformBatch(TableNode $table)
    {
        return array_map(function($row){
            if (count($row) != 2) {throw new \Exception("Cannot parse batch");}
            $qtyFirst = is_numeric($row[0]);
            return [
                'product' => $qtyFirst ? $row[1] : $row[0],
                'quantity' => $qtyFirst ? $row[0] : $row[1]
            ];
        }, $table->getRows());
    }

    public function assertSuccessful(\Symfony\Component\HttpFoundation\Response $response)
    {
        if (!$response->isSuccessful()) {
            throw new \Exception($response);
        }
    }

    public function assertClientError(\Symfony\Component\HttpFoundation\Response $response)
    {
        if (!$response->isClientError()) {
            throw new \Exception($response);
        }
    }


    /**
     * @param TableNode $table
     *
     * @return ArrayCollection
     */
    private function tableNodeToProductQuantities(TableNode $table)
    {
        $result = new \Silo\Inventory\Collection\BatchCollection();

        foreach ($table->getRows() as $row) {
            $product = $this->app['em']->getRepository('Inventory:Product')
                ->findOneBy(['sku' => $row[0]]);
            $result->add(new Inventory\Batch(
                $product,
                $row[1]
            ));
        }

        return $result;
    }

    /**
     * @Given /^an Operation "([^"]*)"(?: to (\w+)) with:$/
     */
    public function anOperationToAWith($ref, $to, TableNode $table)
    {
        $this->anOperationFromToWith($ref, null, $to, $table);
    }

    /**
     * @Given /^an Operation "([^"]*)"(?: from (\w+)) with:$/
     */
    public function anOperationFromAWith($ref, $from, TableNode $table)
    {
        $this->anOperationFromToWith($ref, $from, null, $table);
    }

    /**
     * @Given /^an Operation "([^"]*)"(?: to (\w+)) moving (\w+)$/
     */
    public function anOperationToAMovingB($ref, $to, $what)
    {
        $this->anOperationFromToWith($ref, null, $to, $what);
    }

    /**
     * @Given /^"([^"]*)" is typed as "([^"]*)"$/
     */
    public function isTypedAs($ref, $name)
    {
        $type = $this->app['em']->getRepository('Inventory:OperationType')->getByName($name);

        $op = $this->getRef($ref);
        $op->setType($type);
        $this->app['em']->flush();
    }

    /**
     * @Given /^an Operation "([^"]*)"(?: from (\w+))(?: to (\w+))(?: with:| moving (\w+))$/
     */
    public function anOperationFromToWith($ref, $from, $to, $table)
    {
        $locations = $this->app['em']->getRepository('Inventory:Location');
        if ($table instanceof TableNode) {
            $op = new Inventory\Operation(
                $this->app['current_user'],
                $locations->findOneBy(['code' => $from]),
                $locations->findOneBy(['code' => $to]),
                $this->tableNodeToProductQuantities($table)
            );
        } else {
            $op = new Inventory\Operation(
                $this->app['current_user'],
                $locations->findOneBy(['code' => $from]),
                $locations->findOneBy(['code' => $to]),
                $locations->findOneBy(['code' => $table])
            );
        }

        $this->app['em']->persist($op);
        $this->app['em']->flush();

        $this->getMainContext()->setRef($ref, $op->getId());
    }

    /**
     * @When /^one execute Operation "(\w+)"(?: with:)?$/
     */
    public function oneExecuteOperation($opRef, TableNode $table = null)
    {
        $data = [];
        if ($table) {
            $data = $this->transformBatch($table);
        }

        $id = $this->getMainContext()->getRef($opRef);

        $this->client->request(
            'POST',
            "/silo/inventory/operation/$id/execute",
            $data
        );
        $response = $this->client->getResponse();
        $this->assertSuccessful($response);
    }

    /**
     * @Given /^Operation "(\w+)" contains:$/
     */
    public function operationContains($opRef, TableNode $table)
    {
        $expected = $this->transformBatch($table);
        $id = $this->getMainContext()->getRef($opRef);

        $this->client->request(
            'GET',
            "/silo/inventory/operation/$id"
        );
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertSame($expected, $data['batches']);
    }

    private function assertSame($expected, $actual)
    {
        function arrayRecursiveDiff($aArray1, $aArray2) {
            $aReturn = array();

            foreach ($aArray1 as $mKey => $mValue) {
                if (array_key_exists($mKey, $aArray2)) {
                    if (is_array($mValue)) {
                        $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                        if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                    } else {
                        if ($mValue != $aArray2[$mKey]) {
                            $aReturn[$mKey] = $mValue;
                        }
                    }
                } else {
                    $aReturn[$mKey] = $mValue;
                }
            }
            return $aReturn;
        }

        if(!empty(arrayRecursiveDiff($expected, $actual))) {
            throw new \Exception("Arrays are not identical. Expected ".var_export($expected, true)." but got ".var_export($actual, true));
        }
    }
}
