<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\ArrayCollection;
use Silo\Inventory\Model as Inventory;
use Doctrine\Common\Util\Debug;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//
require_once __DIR__.'/../../../../../../vendor/autoload.php';
require_once __DIR__.'/CoverageContext.php';
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

        $this->setRef("User", $user);

        $this->printDebug("Created the database");
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
        foreach (explode(",", $codes) as $code){
            $l = new Inventory\Location($code);

            if ($table) {
                $op = new Inventory\Operation(
                    $this->getRef("User"),
                    null,
                    $l,
                    $this->tableNodeToProductQuantities($table)
                );
                $op->execute($this->getRef("User"));
                $this->em->persist($op);
            }

            $this->em->persist($l);
        }
        $this->em->flush();
    }

    /**
     * @param TableNode $table
     * @return ArrayCollection
     */
    private function tableNodeToProductQuantities(TableNode $table)
    {
        $result = new ArrayCollection();

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
     * @Given /^an Operation "([^"]*)"(?: from (\w+))?(?: to (\w+))?(?: with:| moving (\w+))$/
     */
    public function anOperationFromToWith($ref, $from, $to, $table)
    {
        $locations = $this->em->getRepository('Inventory:Location');
        if ($table instanceof TableNode) {
            $op = new Inventory\Operation(
                $this->getRef("User"),
                $locations->findOneBy(['code' => $from]),
                $locations->findOneBy(['code' => $to]),
                $this->tableNodeToProductQuantities($table)
            );
        } else {
            $op = new Inventory\Operation(
                $this->getRef("User"),
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
        $op->execute($this->getRef("User"));
        $this->app['em']->flush();
    }

    // Check type
    private function assertException($closure)
    {
        $exceptionCaught = false;
        try{
            // execute closure
        } catch(\Exception $e) {
            $exceptionCaught = true;
        }

        if (!$exceptionCaught) {
            throw new \Exception("Should have got an exception");
        }
    }

    /**
     * @Then /^(\w*) contains(?: nothing|:)$/
     */
    public function containsNothing($code, TableNode $table = null)
    {
        $em = $this->app['em'];
        $locations = $em->getRepository('Inventory:Location');
        $location = $locations->findOneBy(['code' => $code]);

        if ($table) {
            foreach ($this->tableNodeToProductQuantities($table) as $expected) {
                if (!$location->contains($expected)) {
                    throw new \Exception(sprintf(
                        "$code should contain %s x %s",
                        $expected->getQuantity(),
                        $expected->getProduct()->getSku()
                    ));
                }
            }
        } else {
            // nothing
            if (count($location->getBatches()) > 0) {
                throw new \Exception("$code should not contain something");
            }
        }
    }

    /**
     * @Then /^(\w+) (?:parent is (\w+)|has no parent)$/
     */
    public function parentIs($child, $expectedParent = null)
    {
        $locations = $this->em->getRepository('Inventory:Location');
        $parent = $locations->findOneBy(['code'=>$child])->getParent();
        if (!Inventory\Location::compare(
            $parent,
            $expectedParent ? $locations->findOneBy(['code'=>$expectedParent]) : null
        )){
            throw new \Exception("$child parent should be $expectedParent, but got $parent");
        }
    }

    /**
     * @Given /^show ([\w:,]+)$/
     */
    public function showInventoryLocation($tables)
    {
        $output = new Symfony\Component\Console\Output\BufferedOutput();

        foreach(explode(',', $tables) as $table) {
            $tableName = $this->em->getClassMetadata($table)->getTableName();
            $sql = "SELECT * FROM $tableName";
            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($result)) {
                $this->printDebug("No data in $table");

                return;
            }

            $rows = array_map(function ($row) {
                return array_values($row);
            }, $result);

            $headers = array_keys($result[0]);

            $output->writeln("$table");
            $table = new Symfony\Component\Console\Helper\Table($output);
            $table
                ->setHeaders($headers)
                ->setRows($rows);
            $table->render();
        }

        $this->printDebug($output->fetch());
    }

    /**
     * @Then /^Walker\'s inclusive total for (\w+) is:$/
     */
    public function walkerSInclusiveTotalForAIs($code, TableNode $table)
    {
        $locations = $this->em->getRepository('Inventory:Location');
        $location = $locations->findOneBy(['code'=>$code]);

        $result = $this->app['LocationWalker']->mapReduce(
            $location,
            function(Inventory\Location $location){
                return $location->getBatches();
            },
            function($a, $b){
                return $a->incrementBy($b);
            },
            new ArrayCollection()
        );

        Debug::dump($result);
    }
}
