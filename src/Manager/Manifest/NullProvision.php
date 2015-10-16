<?php

namespace Simples\ProcessManager\Manager\Manifest;

use Simples\ProcessManager\Manager\Manager;

class NullProvision implements ProvisionInterface
{
    public function provision(Manager $manager){}
}
