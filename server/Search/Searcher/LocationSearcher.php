<?php

namespace Silo\Search\Searcher;

use Silo\Inventory\Model\Location;

/**
 * Search for a location code.
 */
class LocationSearcher extends AbstractSearcher
{
    /** {@inheritdoc} */
    public function search($query)
    {
        $l = $this->em->getRepository(Location::class)
            ->findOneBy(['code' => strtoupper($query)]);

        if ($l) {
            return [new SearchResult(
                $this->urlGenerator->generate("location", ["location"=>$l->getCode()]),
                (string) $l
            )];
        }

        return null;
    }
}
