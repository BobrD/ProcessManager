<?php

namespace Simples\ProcessManager\Application;

use Simples\ProcessManager\Application\Control\AddProcessCommand;
use Simples\ProcessManager\Application\Control\InfoCommand;
use Simples\ProcessManager\Application\Control\RestartCommand;
use Simples\ProcessManager\Application\Control\StopCommand;
use Simples\ProcessManager\Application\Control\StartCommand;
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
        $this->add(new InfoCommand());
        $this->add(new StopCommand());
        $this->add(new RestartCommand());
        $this->add(new AddProcessCommand());
    }
}
