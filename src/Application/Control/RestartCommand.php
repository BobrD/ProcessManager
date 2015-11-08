<?php

namespace Simples\ProcessManager\Application\Control;

use Simples\ProcessManager\Application\AbstractControlCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartCommand extends AbstractControlCommand
{
    protected function configure()
    {
        $this->setName('control:restart');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getControl()->restart();
    }
}