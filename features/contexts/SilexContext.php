<?php

namespace Silo\Context;

use Behat\Behat\Context\BehatContext;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Silo\Context\AppAwareContextInterface;

/**
 * @see Silex\WebTestCase
 */
class SilexContext extends BehatContext implements AppAwareContextInterface, ClientContextInterface
{
    use AppAwareContextTrait;

    use ClientContextTrait;

    public function getClient()
    {
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

        $this->assertSuccessful($response);
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
