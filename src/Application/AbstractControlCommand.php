<?php

namespace Simples\ProcessManager\Application;

use Monolog\Logger;
use Simples\ProcessManager\Control\Control;
use Simples\ProcessManager\Message\MessageTransformer;
use Simples\ProcessManager\Rpc\RpcClient;
use SuperClosure\Serializer;
use Symfony\Component\Console\Command\Command;

abstract class AbstractControlCommand extends Command
{
    /**
     * @var Control|null
     */
    private $control;

    protected function getControl()
    {
        if (null === $this->control) {
            $logger = new Logger('control');

            $rpcLogger = new Logger('rpc');
            $rpcClient = new RpcClient($rpcLogger);
            $messageTransformer = new MessageTransformer(new Serializer());

            $this->control = new Control($logger, $rpcClient, $messageTransformer);
        }

        return $this->control;
    }
}
