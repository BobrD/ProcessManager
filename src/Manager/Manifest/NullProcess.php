<?php

namespace Simples\ProcessManager\Manager\Manifest;

use Symfony\Component\Process\Process;

class NullProcess extends Process
{
    public function __construct()
    {
        parent::__construct('');
    }
}
