<?php

namespace Simples\ProcessManager\Application;

use Symfony\Component\Console\Application;

class ConsoleApplication extends Application
{
    const NAME = 'Php process manager for StepToTravel.com';

    const VERSION = '0.1';

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);

        $this->add(new InstallCommand());
        $this->add(new StartCommand());
    }
}
