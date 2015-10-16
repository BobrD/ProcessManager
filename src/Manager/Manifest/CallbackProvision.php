<?php

namespace Simples\ProcessManager\Manager\Manifest;

use Simples\ProcessManager\Manager\Manager;

class CallbackProvision implements ProvisionInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Manager $manager
     */
    public function provision(Manager $manager)
    {
        $callback = $this->callback;

        $callback($manager);
    }
}
