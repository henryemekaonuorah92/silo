<?php

namespace Silo\Search\Searcher;

/**
 * Saerch for an object with a given primary key.
 */
class PrimaryKeySearcher extends AbstractSearcher
{
    private $class;

    private $routeName;

    private $context;

    public function __construct($class, $routeName, $context)
    {
        $this->class = $class;
        $this->routeName = $routeName;
        $this->context = $context;
    }

    /** {@inheritdoc} */
    public function search($query)
    {
        if (!preg_match('/^\d+$/', $query)) {
            return null;
        }

        $model = $this->em->getRepository($this->class)->find($query);
        if (! $model) {
            return null;
        }
        $class = $this->class;
        $computedContext = [];
        $data = $this->em->getUnitOfWork()->getOriginalEntityData($model);
        foreach($this->context as $key => $contextKey) {
            $computedContext[$key] = $data[$contextKey];
        }

        $url = $this->urlGenerator->generate($this->routeName, $computedContext);

        return [new SearchResult($url, (string) $model)];
    }
}
