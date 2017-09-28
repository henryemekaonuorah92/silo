<?php

namespace Silo\Search\Searcher;
use Doctrine\ORM\QueryBuilder;
use Silo\Inventory\Model\Product;

/**
 * Search for a product given its SKU.
 */
class ProductSkuSearcher extends AbstractSearcher
{
    /** {@inheritdoc} */
    public function search($query)
    {
        if (!preg_match('/^[\d\w]+[-_]+[\d\w]+.*/', $query)) {
            return null;
        }

        /** @var QueryBuilder $q */
        $q = $this->em->createQueryBuilder();
        $q->select('p')
            ->from(Product::class, 'p')
            ->where($q->expr()->like('p.sku', ':sku'))
            ->setParameter('sku', "$query%");

        $results = [];
        foreach($q->getQuery()->getResult() as $result) {
            array_push($results, new SearchResult(
                $this->urlGenerator->generate('product', ['product' => $result->getSku()]),
                (string) $result
            ));
        }

        if (empty($results)) {
            return null;
        }

        return $results;
    }
}
