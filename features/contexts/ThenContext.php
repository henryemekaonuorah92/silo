<?php

namespace Silo\Context;

use \Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\ArrayCollection;
use Silo\Inventory\Model as Inventory;

class ThenContext extends BehatContext implements AppAwareContextInterface
{
    /** @var \Silex\Application */
    private $app;

    public function setApp(\Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * @Then /^Walker\'s inclusive total for (\w+) is:$/
     */
    public function walkerSInclusiveTotalForAIs($code, TableNode $table)
    {
        $locations = $this->app['em']->getRepository('Inventory:Location');
        $location = $locations->findOneBy(['code' => $code]);

        $this->exclusiveDiff(
            $this->tableNodeToProductQuantities($table),
            $locations->getInclusiveContent($location)
        );
    }

    public function exclusiveDiff($expecteds, $currents)
    {
        foreach ($currents as $current) {
            if (!$expecteds->contains($current)) {
                throw new \Exception(sprintf(
                    'Should not contain %s x %s',
                    $current->getProduct()->getSku(),
                    $current->getQuantity()
                ));
            }
        }
        foreach ($expecteds as $expected) {
            if (!$currents->contains($expected)) {
                throw new \Exception(sprintf(
                    'Should contain %s x %s',
                    $expected->getProduct()->getSku(),
                    $expected->getQuantity()
                ));
            }
        }
    }

    /**
     * @Then /^(\w+) is empty$/
     * @Then /^(\w+) contains(?: nothing|:)$/
     */
    public function containsNothing($code, TableNode $table = null)
    {
        $locations = $this->app['em']->getRepository('Inventory:Location');
        $location = $locations->findOneBy(['code' => $code]);

        if ($table) {
            $this->exclusiveDiff(
                $this->tableNodeToProductQuantities($table),
                $location->getBatches()
            );
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
        $locations = $this->app['em']->getRepository('Inventory:Location');
        $parent = $locations->findOneBy(['code' => $child])->getParent();
        if (!Inventory\Location::compare(
            $parent,
            $expectedParent ? $locations->findOneBy(['code' => $expectedParent]) : null
        )) {
            throw new \Exception("$child parent should be $expectedParent, but got $parent");
        }
    }

    /**
     * @todo copypasta from FeatureContext, find something better :/
     *
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
     * @Given /^show ([\w:,]+)$/
     *
     * @todo This is a debugging function, should be somewhere else
     */
    public function showTable($tables)
    {
        $em = $this->app['em'];
        $output = new Symfony\Component\Console\Output\BufferedOutput();

        foreach (explode(',', $tables) as $table) {
            $tableName = $em->getClassMetadata($table)->getTableName();
            $sql = "SELECT * FROM $tableName";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($result)) {
                $this->printDebug("No data in $table");

                continue;
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
     * @Given /^show raw ([\w:,]+)$/
     *
     * @todo This is a debugging function, should be somewhere else
     */
    public function showRawTable($tables)
    {
        $em = $this->app['em'];
        $output = new Symfony\Component\Console\Output\BufferedOutput();

        foreach (explode(',', $tables) as $table) {
            $sql = "SELECT * FROM $table";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($result)) {
                $this->printDebug("No data in $table");

                continue;
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
}
