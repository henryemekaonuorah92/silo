<?php

namespace Silo\Base\Provider\DoctrineProvider;

class SQLLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    private $queries = [];

    public function startQuery($sql, array $params = null, array $types = null)
    {
        array_push($this->queries, $sql);
    }

    public function stopQuery()
    {
        // TODO: Implement stopQuery() method.
    }

    public function getQueries()
    {
        return $this->queries;
    }
}