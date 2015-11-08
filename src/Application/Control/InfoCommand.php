<?php

namespace Simples\ProcessManager\Application\Control;

use Simples\ProcessManager\Application\AbstractControlCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractControlCommand
{
    protected function configure()
    {
        $this->setName('control:info');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $response = $this->getControl()->info();

        /** @var Table $table */
        $table = new Table($output);
        $table
            ->setHeaders(['Manifest', 'Pid', 'Memory (MB)', 'CPU (%)']);

        foreach ($response->data() as $manifest => $processesInfo) {
            foreach ($processesInfo as $pid => $processInfo) {
                $table->addRow([$manifest, $pid, $processInfo['MEM'] / 1024, $processInfo['CPU']]);
            }
        }

        $table->render();
    }
}
