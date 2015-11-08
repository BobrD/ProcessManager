<?php

namespace Simples\ProcessManager\Application\Control;

use Simples\ProcessManager\Application\AbstractControlCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends AbstractControlCommand
{
    protected function configure()
    {
        $this->setName('control:stop');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getControl()->start();
    }
}
