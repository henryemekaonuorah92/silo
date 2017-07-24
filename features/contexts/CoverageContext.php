<?php

namespace Silo\Context;

use \Behat\Behat\Context\BehatContext;

class CoverageContext extends BehatContext
{
    /** @var PHP_CodeCoverage */
    private static $coverage;

    private static $configuration = null;

    public function __construct(array $parameters)
    {
        if (isset($parameters['coverage']))
        {
            self::$configuration = $c = $parameters['coverage'];
            if (!isset($c['whitelist'])) {
                throw new \Exception("add coverage.whitelist to the configuration");
            }
            if (!isset($c['outputDir'])) {
                throw new \Exception("add coverage.outputDir to the configuration");
            }
        }
    }

    public static function getCoverageInstance()
    {
        if (!self::$coverage) {
            $filter = new \PHP_CodeCoverage_Filter();
            if (!is_array(self::$configuration['whitelist'])) {
                self::$configuration['whitelist'] = [self::$configuration['whitelist']];
            }
            array_walk(self::$configuration['whitelist'], function($path)use($filter){
                echo $path;
                $filter->addDirectoryToWhitelist($path);
            });

            self::$coverage = new \PHP_CodeCoverage(null, $filter);
        }

        return self::$coverage;
    }



    /** @BeforeScenario */
    public function before(\Behat\Behat\Event\ScenarioEvent $event)
    {
        if (self::$configuration) {
            $s = $event->getScenario();
            self::getCoverageInstance()->start($s->getFile().$s->getLine());
        }
    }

    /** @AfterScenario */
    public function after($event)
    {
        if (self::$configuration) {
            self::getCoverageInstance()->stop();
        }
    }

    /** @AfterSuite */
    public static function teardown(\Behat\Behat\Event\SuiteEvent $event)
    {
        if (self::$configuration) {
            $writer = new \PHP_CodeCoverage_Report_HTML();
            $writer->process(self::getCoverageInstance(), self::$configuration['outputDir']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->context->printDebug(sprintf('%s %s %s',
            $sql,
            var_export($params, true),
            var_export($types, true)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }
}
