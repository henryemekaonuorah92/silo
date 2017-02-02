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
     * @When /^one assign modifier "([^"]*)" to (\w+)$/
     */
    public function oneAssignModifierToA($name, $code)
    {
        $this->getClient()->request(
            'POST',
            "/silo/inventory/location/$code/modifiers",
            ['name' => $name]
        );
        $response = $this->getClient()->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @Then /^(\w+) has "([^"]*)" modifier$/
     */
    public function aHasModifier($code, $name)
    {
        $this->getClient()->request('GET', "/silo/inventory/location/$code/modifiers");
        $response = $this->getClient()->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data);
        $this->assertContainsKeyWithValue($data[0], 'name', $name);
    }

    /**
     * @When /^one remove modifier "([^"]*)" from (\w+)$/
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
     * @Then /^A has no "([^"]*)" modifier$/
     */
    public function aHasNoModifier($code)
    {
        $this->getClient()->request('GET', "/silo/inventory/location/$code/modifiers");
        $response = $this->getClient()->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data);
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
                throw new \Behat\Behat\Exception\PendingException();
                $op = new Inventory\Operation(
                    $this->getRef('User'),
                    null,
                    $l,
                    $this->tableNodeToProductQuantities($table)
                );
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
        $this->getClient()->request('PATCH', "/silo/inventory/location/$parentCode/child", ['codes' => [$code]]);
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
                'sku' => $qtyFirst ? $row[1] : $row[0],
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

    private function assertEmpty($data)
    {
        if (!empty($data)) {
            throw new \Exception('should be empty');
        }
    }

    private function assertSuccessful(\Symfony\Component\HttpFoundation\Response $response)
    {
        if (!$response->isSuccessful()) {
            throw new \Exception($response);
        }
    }

    private function assertClientError(\Symfony\Component\HttpFoundation\Response $response)
    {
        if (!$response->isClientError()) {
            throw new \Exception($response);
        }
    }
}
