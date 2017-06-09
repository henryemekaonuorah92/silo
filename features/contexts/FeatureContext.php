<?php

namespace Silo\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\ArrayCollection;
use Silo\Inventory\Model as Inventory;

/**
 * Features context.
 */
class FeatureContext extends BehatContext implements AppAwareContextInterface, ClientContextInterface
{
    protected $app;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    public function setApp(\Silex\Application $app)
    {
        $this->app = $app;
        $this->em = $app['em'];
    }

    use ClientContextTrait;

    public function getRef($name)
    {
        return $this->getSubcontext('app')->getRef($name);
    }

    public function setRef($name, $object)
    {
        return $this->getSubcontext('app')->setRef($name, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(array $parameters)
    {
        if (isset($parameters['coverage']) && $parameters['coverage']) {
            $this->useContext('coverage', new CoverageContext($parameters));
        }

        // $this->useContext('ranking', $ranking);
        $this->useContext('app', new AppContext($parameters));
        $this->useContext('inventory', new InventoryContext());
        $this->useContext('then', new ThenContext());
        $this->useContext('unit', new UnitContext());
        $this->useContext('silex', new SilexContext());
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
            $product = $this->em->getRepository('Inventory:Product')
                ->findOneBy(['sku' => $row[0]]);
            $result->add(new Inventory\Batch(
                $product,
                $row[1]
            ));
        }

        return $result;
    }








    /**
     * @Then /^Operation "([^"]*)" is cancelled$/
     */
    public function operationIsCancelled($ref)
    {
        /** @var Inventory\Operation $op */
        $op = $this->em->find('Inventory:Operation', $this->getRef($ref));

        if (!$op->getStatus()->toArray()['isCancelled']) {
            throw new \Exception("$op should be cancelled");
        }
    }

    /**
     * @When /^"([^"]*)" is executed$/
     */
    public function isExecuted($ref)
    {
        $op = $this->em->find('Inventory:Operation', $this->getRef($ref));
        $op->execute($this->app['current_user']);
        $this->app['em']->flush();
        sleep(1);
    }

    // Check type
    private function assertException($closure)
    {
        $exceptionCaught = false;
        try {
            // execute closure
        } catch (\Exception $e) {
            $exceptionCaught = true;
        }

        if (!$exceptionCaught) {
            throw new \Exception('Should have got an exception');
        }
    }


    /**
     * @Then /^Playbacker for (.+) at "([^"]*)" gives:$/
     */
    public function playbackerAtGives($code, $opRef, TableNode $table)
    {
        $location = $this->em->getRepository('Inventory:Location')->forceFindOneByCode($code);
        $op = $this->em->getRepository('Inventory:Operation')->find($this->getRef($opRef));;
        $found = $this->app['Playbacker']->getBatchesAt($location, $op->getStatus()->getRequestedAt());
        $expected = $this->tableNodeToProductQuantities($table);

        $this->getSubcontext('then')->exclusiveDiff($expected, $found);
    }
}
