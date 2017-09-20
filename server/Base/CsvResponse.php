<?php

namespace Silo\Base;

use Symfony\Component\HttpFoundation\Response;

class CsvResponse extends Response
{
    public function __construct($content, $filename = "export.csv", $status = 200, $headers = array())
    {
        parent::__construct('', $status, $headers);

        $this->headers->set('Cache-Control', 'private');
        $this->headers->set('Content-type', 'text/csv' );
        $this->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'";');

        $this->setContent($content);
    }
}
