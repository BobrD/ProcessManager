<?php

namespace Simples\ProcessManager\Manager;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Simples\ProcessManager\Message\MessageTransformer;
use Simples\ProcessManager\Rpc\RpcClient;
use Simples\ProcessManager\Rpc\StompFactory;
use SuperClosure\Serializer;
use React\EventLoop\Factory;

class ManagerFactory
{
    public static function create(array $configuration)
    {
        $logHandler = [new StreamHandler($configuration['logDirPath'] . '/processManager.log')];
        $managerLogger = new Logger('manager', $logHandler);
        $rpcLogger = new Logger('rpc', $logHandler);
        $rpcClient = new RpcClient($rpcLogger);
        $messageTransformer = new MessageTransformer(new Serializer());

        $loop = Factory::create();
        $stompFactory = new StompFactory($loop);

        return new Manager($managerLogger, $rpcClient, $messageTransformer, $stompFactory, $loop);
    }
}
