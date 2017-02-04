<?php

use Behat\Behat\Context\BehatContext;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;

/**
 * @see Silex\WebTestCase
 */
class SilexContext extends BehatContext implements AppAwareContextInterface
{
    /** @var \Silex\Application */
    private $app;

    public function setApp(\Silex\Application $app)
    {
        $this->app = $app;
    }

    private $client;

    public function getClient(array $server = array())
    {
        if (!$this->client) {
            $this->client = new Client($this->app, $server);
        }

        return $this->client;
    }

    /**
     * @When /^one delete Location (\w+)$/
     */
    public function oneDeleteLocation($code)
    {
        $this->getClient()->request(
            'DELETE',
            "/silo/inventory/location/$code"
        );
        $response = $this->getClient()->getResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @When /^one assign modifier (\w+) to (\w+)(?: with:)?$/
     */
    public function oneAddModifierTo($name, $code, \Behat\Gherkin\Node\PyStringNode $value = null)
    {
        if (!is_null($value)) {
            $value = ['value' => json_decode($value, true)];
        } else {
            $value = [];
        }
        $this->getClient()->request(
            'POST',
            "/silo/inventory/location/$code/modifiers",
            ['name' => $name] + $value
        );
        $response = $this->getClient()->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @Then /^(\w+) has (\w+|no) modifier$/
     */
    public function aHasModifier($code, $name)
    {
        $this->getClient()->request('GET', "/silo/inventory/location/$code/modifiers");
        $response = $this->getClient()->getResponse();
        $data = json_decode($response->getContent(), true);
        if ($name == 'no') {
            $this->assertEmpty($data);
        } else {
            $this->assertNotEmpty($data);
            $this->assertContainsKeyWithValue($data[0], 'name', $name);
        }
    }

    /**
     * @When /^one remove modifier (\w+) from (\w+)$/
     */
    public function oneRemoveModifierFromA($name, $code)
    {
        $this->getClient()->request(
            'DELETE',
            "/silo/inventory/location/$code/modifiers",
            ['name' => $name]
        );
        $response = $this->getClient()->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @Then /^Location (\w+) (exists|does not exist)$/
     */
    public function locationExists($code, $what)
    {
        $this->getClient()->request('GET', "/silo/inventory/location/$code");
        $response = $this->getClient()->getResponse();
        switch($what){
            case 'exists':
                $this->assertSuccessful($response); break;
            case 'does not exist':
                $this->assertClientError($response); break;
        }
    }

    /**
     * @Given /^(?:a )?Locations? ([\w,]+)(?: with:)?$/
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
        $this->getClient()->request('POST', "/silo/inventory/location/$parentCode/child", ['name' => $code]);
        $response = $this->getClient()->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @Given /^one move (\w+) to (\w+)$/
     */
    public function oneMoveCToB($code, $parentCode)
    {
        $this->getClient()->request('PATCH', "/silo/inventory/location/$parentCode/child", [$code]);
        $response = $this->getClient()->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @Then /^(\w+) is in (\w+)$/
     */
    public function cIsInB($code, $parentCode)
    {
        $this->getClient()->request('GET', "/silo/inventory/location/$code");
        $response = $this->getClient()->getResponse();
        $data = json_decode($response->getContent(), true);
        if ($data['parent'] !== $parentCode) {
            throw new \Exception("Wrong parent");
        }
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

    /**
     * @When /^one fill Location (\w+) with:$/
     */
    public function oneFillLocationWith($code, TableNode $table)
    {
        $this->getClient()->request(
            'PATCH',
            "/silo/inventory/location/$code/batches",
            $this->transformBatch($table)
        );
        $response = $this->getClient()->getResponse();
        $this->assertSuccessful($response);
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

        $this->getClient()->request(
            'POST',
            "/silo/inventory/operation/$id/execute",
            $data
        );
        $response = $this->getClient()->getResponse();
        $this->assertSuccessful($response);
    }

    /**
     * @Given /^Operation "(\w+)" contains:$/
     */
    public function operationContains($opRef, TableNode $table)
    {
        $expected = $this->transformBatch($table);
        $id = $this->getMainContext()->getRef($opRef);

        $this->getClient()->request(
            'GET',
            "/silo/inventory/operation/$id"
        );
        $response = $this->getClient()->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertSame($expected, $data['batches']);
    }

    private function assertTrue($flag)
    {
        if ($flag !== true) {
            throw new \Exception('Expected true but got '.var_export($flag, true));
        }
    }

    private function assertContainsKey($array, $key)
    {
        if (array_key_exists($key, $array) !== true) {
            throw new \Exception('Expected array contained '.$key);
        }
    }

    private function assertContainsKeyWithValue($array, $key, $value)
    {
        if (array_key_exists($key, $array) !== true) {
            throw new \Exception('Expected array contained '.$key);
        }
        if ($array[$key] !== $value) {
            throw new \Exception('Value is not right');
        }
    }

    private function assertNotEmpty($data)
    {
        if (empty($data)) {
            throw new \Exception('should not be empty');
        }
    }

    public function assertEmpty($data)
    {
        if (!empty($data)) {
            throw new \Exception('should be empty');
        }
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

    /**
     * @Then /^(\w+) has (\d+) related Operations$/
     */
    public function hasRelatedOperations($code, $expected)
    {
        $this->getClient()->request(
            'GET',
            "/silo/inventory/operation/",
            ['location' => $code]
        );
        $response = $this->getClient()->getResponse();
        $data = json_decode($response->getContent(), true);

        $actual = count($data);
        if ($actual != $expected) {
            throw new \Exception("Found $actual Operations");
        }
    }
}
