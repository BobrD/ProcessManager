<?php

namespace Simples\ProcessManager\Control\Command;

use Simples\ProcessManager\Manager\Manager;
use Simples\ProcessManager\Manager\Manifest\CallbackProvision;
use Simples\ProcessManager\Message\Request;

class StopCommand extends Request
{
    public function __construct()
    {
        $closure = function (Manager $manager) {
            $manager->addNextProvision(new CallbackProvision(function (Manager $manager){
                $manager->stop();
            }));
        };

        parent::__construct($closure);
    }
}
