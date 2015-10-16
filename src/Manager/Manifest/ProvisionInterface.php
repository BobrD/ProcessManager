<?php

namespace Simples\ProcessManager\Manager\Manifest;

use Simples\ProcessManager\Manager\Manager;

interface ProvisionInterface
{
    public function provision(Manager $manager);
}

