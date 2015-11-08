<?php

namespace Simples\ProcessManager\Application\Control;

use Simples\ProcessManager\Application\AbstractControlCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddProcessCommand extends AbstractControlCommand
{
    protected function configure()
    {
        $this
            ->setName('control:addprocess')
            ->addArgument('manifest', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manifest = $input->getArgument('manifest');

        $this->getControl()->addProcess($manifest);
    }
}