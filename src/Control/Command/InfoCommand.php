<?php

namespace Simples\ProcessManager\Control\Command;

use Simples\ProcessManager\Manager\Manager;
use Simples\ProcessManager\Message\Request;

class InfoCommand extends Request
{
    public function __construct()
    {
        $closure = function (Manager $manager) {
            $response = [];
            foreach ($manager->getManifests() as $manifest) {
                $processesInfo = [];
                foreach ($manifest->getProcesses() as $process) {
                    $pid = $process->getPid();
                    if (!$process->isRunning()) {
                        $processInfo = [
                            'CPU' => 0,
                            'MEM' => 0
                        ];
                    } else {
                        $output = exec('ps -p '  . $pid . ' -o %cpu,rss');
                        $output = preg_split('@\s+@', trim($output), 7);
                        $processInfo = [
                            'CPU' => $output[0],
                            'MEM' => $output[1]
                        ];
                    }

                    $processesInfo[$pid] = $processInfo;
                }
                $response[$manifest->getName()] = $processesInfo;
            }

            return $response;
        };

        parent::__construct($closure);
    }
}
