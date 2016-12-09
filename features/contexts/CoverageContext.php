<?php

use \Behat\Behat\Context\BehatContext;

class CoverageContext extends BehatContext
{
    /** @var PHP_CodeCoverage */
    private static $coverage;

    public function __construct(array $parameters)
    {}

    public static function getCoverageInstance()
    {
        if (!self::$coverage) {
            $filter = new PHP_CodeCoverage_Filter();
            $filter->addDirectoryToWhitelist(__DIR__.'/../../Base');
            $filter->addDirectoryToWhitelist(__DIR__.'/../../Inventory');

            self::$coverage = new PHP_CodeCoverage(null, $filter);
        }

        return self::$coverage;
    }

    /** @BeforeScenario */
    public function before(\Behat\Behat\Event\ScenarioEvent $event)
    {
        $this->getCoverageInstance()->start($event->getScenario()->getTitle());
    }

    /** @AfterScenario */
    public function after($event)
    {
        $this->getCoverageInstance()->stop();
    }

    /** @AfterSuite */
    public static function teardown(\Behat\Behat\Event\SuiteEvent $event)
    {
        $writer = new PHP_CodeCoverage_Report_HTML();
        $writer->process(self::getCoverageInstance(), __DIR__.'/../../code-coverage-report');
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
