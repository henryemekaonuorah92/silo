<?php

namespace Silo\Search\Searcher;

use Doctrine\ORM\EntityManager;
use Silo\Base\EntityManagerAware;
use Silo\Base\EntityManagerAwareTrait;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Part of the Searcher searching algorithm.
 */
abstract class AbstractSearcher implements EntityManagerAware
{
    use EntityManagerAwareTrait;

    /** @var UrlGenerator */
    protected $urlGenerator;

    /**
     * @param UrlGenerator $urlGenerator
     */
    public function setUrlGenerator(UrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $query
     *
     * @return null|SearchResult[] Matching results
     */
    abstract public function search($query);
}
