<?php

use Simples\ProcessManager\Manager\Manager;
use Simples\ProcessManager\Manager\Manifest\CallbackProvision;
use Simples\ProcessManager\Manager\Manifest\DefaultManifest;
use Symfony\Component\Process\Process;

return [
    new DefaultManifest(
        'test',
        new CallbackProvision(
            function (Manager $manger) {
                foreach ($manger->getManifests() as $manifest) {

                    if ($manifest->getName() !== 'test') {
                        continue;
                    }

                    foreach ($manifest->getProcesses() as $process) {
                        if (!$process->isRunning()) {
                            $manger->runProcess($process);
                            $manger->getLogger()->alert('Процесс ' . $manifest->getName() . ' умер и был перезапущен.');
                        }
                    }
                }
        }),
        new Process('php /home/bobrd/Projects/simplePm/examples/worker.php')
    )
];