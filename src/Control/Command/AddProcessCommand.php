<?php

namespace Simples\ProcessManager\Control\Command;

use Simples\ProcessManager\Manager\Manager;
use Simples\ProcessManager\Manager\Manifest\CallbackProvision;
use Simples\ProcessManager\Message\Request;

class AddProcessCommand extends Request
{
    public function __construct($manifest)
    {
        $closure = function (Manager $manager) use ($manifest) {
            $manager->addNextProvision(new CallbackProvision(function (Manager $manager) use ($manifest) {
                $manager->startNewProcess($manifest);
            }));
        };

        parent::__construct($closure);
    }
}
