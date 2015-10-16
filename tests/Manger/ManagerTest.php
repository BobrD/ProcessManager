<?php

namespace Simles\ProcessManager\Tests\Manager;

use Monolog\Logger;
use React\EventLoop\Factory;
use Simples\ProcessManager\Manager\Manager;
use Simples\ProcessManager\Manager\Manifest\DefaultManifest;
use Simples\ProcessManager\Manager\Manifest\CallbackProvision;
use Simples\ProcessManager\Message\MessageTransformer;
use Simples\ProcessManager\Rpc\RpcClient;
use Simples\ProcessManager\Rpc\StompFactory;
use SuperClosure\Serializer;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $managerLogger = new Logger('manager');
        $rpcLogger = new Logger('rpc');
        $rpcClient = new RpcClient($rpcLogger);
        $messageTransformer = new MessageTransformer(new Serializer);

        $loop = Factory::create();
        $stompFactory = new StompFactory($loop);

        $manager = new Manager($managerLogger, $rpcClient, $messageTransformer, $stompFactory, $loop);

        $manager->addManifest(new DefaultManifest('test', new CallbackProvision(function (Manager $manager) {
            $manager->stop();
        })));

        $manager->run();

        $running = $manager->getRunningProcess();

        $this->assertCount(1, $running);
    }
}
