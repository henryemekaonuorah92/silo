<?php

namespace Silo\Search\Searcher;

class SearchResult
{
    private $url;

    private $description;

    public function __construct($url, $description)
    {
        $this->url = $url;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}