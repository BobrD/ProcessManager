<?php

namespace Simples\ProcessManager\Manager\Manifest;

use Symfony\Component\Process\Process;

class DefaultManifest implements ManifestInterface
{
    /**
     * @var ProvisionInterface
     */
    private $provision;

    /**
     * @var Process[]|array
     */
    private $processes = [];

    /**
     * @var string
     */
    private $name;

    /**
     * @param $name
     * @param ProvisionInterface|null $provision
     * @param Process $process
     */
    public function __construct($name, ProvisionInterface $provision = null, Process $process = null)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Manifest name should be string');
        }

        $this->name = $name;
        $this->provision = $provision ? $provision : new NullProvision();
        $this->processes[] = $process ? $process : new NullProcess();
    }

    /**
     * @return ProvisionInterface
     */
    public function getProvision()
    {
        return $this->provision;
    }

    /**
     * @return Process[]|array
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * Создаёт новый процесс
     *
     * @return Process
     */
    public function newProcess()
    {
        return $this->processes[] = clone $this->processes[0];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
