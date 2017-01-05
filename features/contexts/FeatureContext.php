<?php

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\ArrayCollection;
use Silo\Inventory\Model as Inventory;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//
require_once __DIR__.'/../../../../../../vendor/autoload.php';
require_once __DIR__.'/CoverageContext.php';
require_once __DIR__.'/ThenContext.php';
require_once __DIR__.'/UnitContext.php';

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    private $app;

    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    private $refs = [];

    private function getRef($name)
    {
        if (!isset($this->refs[$name])) {
            throw new \Exception("No such ref $name");
        }

        return $this->refs[$name];
    }

    private function setRef($name, $object)
    {
        if (isset($this->refs[$name])) {
            throw new \Exception("Ref $name is already set");
        }
        $this->refs[$name] = $object;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(array $parameters)
    {
        // $this->useContext('coverage', new CoverageContext($parameters));
        // $this->useContext('ranking', $ranking);
        $this->useContext('then', new ThenContext());
        $this->useContext('unit', new UnitContext());
    }

    /** @BeforeScenario */
    public function before($event)
    {
        $this->app = $app = new \Silo\Silo(['em.dsn' => 'sqlite:///:memory:']);
        $app->boot();
        $this->em = $em = $app['em'];

        // Generate the database
        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->app['em']);
        $tool->createSchema($metadatas);

        $user = new Inventory\User('test');
        $em->persist($user);
        $em->flush();

        $this->setRef('User', $user);

        $this->printDebug('Created the database');

        foreach ($this->getSubcontexts() as $context) {
            if ($context instanceof AppAwareContextInterface) {
                $context->setApp($app);
            }
        }
    }

    /**
     * @Given /^a Product "([^"]*)"$/
     */
    public function aProduct($sku)
    {
        $this->em->persist(new Inventory\Product($sku));
        $this->em->flush();
    }

    /**
     * @Given /^(?:a )?Locations? ([\w,]+)(?: with:)?$/
     */
    public function aLocation($codes, TableNode $table = null)
    {
        foreach (explode(',', $codes) as $code) {
            $l = new Inventory\Location($code);

            if ($table) {
                $op = new Inventory\Operation(
                    $this->getRef('User'),
                    null,
                    $l,
                    $this->tableNodeToProductQuantities($table)
                );
                $op->execute($this->getRef('User'));
                $this->em->persist($op);
            }

            $this->em->persist($l);
        }
        $this->em->flush();
    }

    /**
     * @param TableNode $table
     *
     * @return ArrayCollection
     */
    private function tableNodeToProductQuantities(TableNode $table)
    {
        $result = new Inventory\BatchCollection();

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
     * @Given /^an Operation "([^"]*)"(?: from (\w+))(?: to (\w+))(?: with:| moving (\w+))$/
     */
    public function anOperationFromToWith($ref, $from, $to, $table)
    {
        $locations = $this->em->getRepository('Inventory:Location');
        if ($table instanceof TableNode) {
            $op = new Inventory\Operation(
                $this->getRef('User'),
                $locations->findOneBy(['code' => $from]),
                $locations->findOneBy(['code' => $to]),
                $this->tableNodeToProductQuantities($table)
            );
        } else {
            $op = new Inventory\Operation(
                $this->getRef('User'),
                $locations->findOneBy(['code' => $from]),
                $locations->findOneBy(['code' => $to]),
                $locations->findOneBy(['code' => $table])
            );
        }

        $this->em->persist($op);
        $this->em->flush();

        $this->setRef($ref, $op);
    }

    /**
     * @When /^"([^"]*)" is executed$/
     */
    public function isExecuted($ref)
    {
        $op = $this->getRef($ref);
        $op->execute($this->getRef('User'));
        $this->app['em']->flush();
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
     * @Given /^"([^"]*)" is typed as "([^"]*)"$/
     */
    public function isTypedAs($ref, $name)
    {
        $type = $this->app['em']->getRepository('Inventory:OperationType')->getByName($name);

        $op = $this->getRef($ref);
        $op->setType($type);
        $this->app['em']->flush();
    }
}
