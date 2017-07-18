<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silo\Base;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\Handler\AbstractHandler;

class RegexFilterHandler extends AbstractHandler
{
    /**
     * @var callable|\Monolog\Handler\HandlerInterface Handler or factory callable($record, $this)
     */
    protected $handler;

    /**
     * @var string[] List fo regex that will get rejected
     */
    protected $excludedRegexes;

    /**
     * @var bool Whether the messages that are handled can bubble up the stack or not
     */
    protected $bubble;

    /**
     * @param callable|HandlerInterface $handler         Handler or factory callable($record, $this).
     * @param array                     $excludedRegexes
     * @param Boolean                   $bubble          Whether the messages that are handled can bubble up the
     * stack or not
     */
    public function __construct($handler, array $excludedRegexes = [], $bubble = true)
    {
        $this->handler  = $handler;
        $this->bubble   = $bubble;
        $this->excludedRegexes = $excludedRegexes;

        if (!$this->handler instanceof HandlerInterface && !is_callable($this->handler)) {
            throw new \RuntimeException("The given handler (".json_encode($this->handler).") is not a callable nor a Monolog\Handler\HandlerInterface object");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return $this->handler->isHandling($record);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $excluded = array_reduce(
            array_map(function($regex)use($record){
                return preg_match($regex, $record['message']);
            }, $this->excludedRegexes),
            function($a, $b){return $a || $b;},
            false
        );

        if ($excluded) {
            return false;
        }

        // The same logic as in FingersCrossedHandler
        if (!$this->handler instanceof HandlerInterface) {
            $this->handler = call_user_func($this->handler, $record, $this);
            if (!$this->handler instanceof HandlerInterface) {
                throw new \RuntimeException("The factory callable should return a HandlerInterface");
            }
        }

        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        $this->handler->handle($record);

        return false === $this->bubble;
    }

    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records)
    {
        $filtered = array();
        foreach ($records as $record) {
            if ($this->isHandling($record)) {
                $filtered[] = $record;
            }
        }

        $this->handler->handleBatch($filtered);
    }
}
