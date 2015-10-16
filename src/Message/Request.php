<?php

namespace Simples\ProcessManager\Message;

class Request
{
    /**
     * @var \Closure
     */
    private $closure;

    public function __construct($closure)
    {
        $this->closure = $closure;
    }

    /**
     * @return \Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }
}
