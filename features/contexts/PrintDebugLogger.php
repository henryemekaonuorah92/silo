<?php

class PrintDebugLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    private $context;

    public function __construct(\Behat\Behat\Context\BehatContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $interpolatedSql = preg_replace_callback('/\?/', function()use(&$params, &$types){
            $p = array_shift($params); $t = array_shift($types);
            if ($p instanceof \DateTime) {
                $p = $p->format("Y-m-d H:i:s");
            }
            if (is_null($p)) return "NULL";
            if ($t === "integer") return $p;
            return "\"$p\"";
        }, $sql);

        $this->context->printDebug($interpolatedSql);
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }
}
