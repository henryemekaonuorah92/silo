<?php

namespace Silo\Base;

use Monolog\Logger;

class CallbackHandler extends \Monolog\Handler\AbstractHandler
{
    private $callback;

    /**
     * @param int $level The minimum logging level at which this handler will be triggered
     */
    public function __construct($callback, $level = Logger::DEBUG)
    {
        parent::__construct($level, false);
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if ($record['level'] < $this->level) {
            return false;
        }

        call_user_func($this->callback, $record);

        return true;
    }
}
