<?php

namespace Simples\ProcessManager\Message;

class Response
{
    const STATUS_OK = 'ok';

    const STATUS_ERROR = 'error';

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $status;

    public function __construct($data = [], $status = self::STATUS_OK)
    {
        $this->data = $data;
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function data()
    {
       return $this->data;
    }

    /**
     * @return string
     */
    public function status()
    {
        return $this->status;
    }
}
