<?php

namespace Simples\ProcessManager\Manager\Manifest;

use Symfony\Component\Process\Process;

interface ManifestInterface
{
    /**
     * @return ProvisionInterface
     */
    public function getProvision();

    /**
     * @return Process[]|array
     */
    public function getProcesses();

    /**
     * @return string
     */
    public function getName();

    /**
     * Создаёт новый процесс
     *
     * @return Process
     */
    public function newProcess();
}

