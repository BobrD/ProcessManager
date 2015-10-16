<?php

namespace Simples\ProcessManager\Rpc;

use Psr\Log\LoggerInterface;
use React\EventLoop\Factory as LoopFactory;
use React\Promise\Deferred;
use React\Promise\DeferredPromise;
use React\Stomp\Client;
use React\Stomp\Protocol\Frame;
use Simples\ProcessManager\Exception\RpcTimeIsOutException;
use Simples\ProcessManager\Message\MessageTransformer;

class RpcClient
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $channel
     * @param string $message
     * @return DeferredPromise
     */
    public function call($channel, $message)
    {
        $loop = LoopFactory::create();
        $factory = new StompFactory($loop);
        $client = $factory->createClient();

        $deferred = new Deferred();

        $timer = $loop->addTimer(2, function () use ($deferred, $client) {
            $client->disconnect();
            $deferred->reject(new RpcTimeIsOutException());
        });

        $client
            ->connect()
            ->then(
                function (StompClient $client) use ($message, $channel, $loop, $deferred, $timer) {
                    $rpcReceiver = function (Frame $frame) use ($deferred, $timer, $client) {
                        $timer->cancel();
                        $client->disconnect();
                        try {
                            $deferred->resolve($frame);
                        } catch (\Exception $e) {
                            $deferred->reject($e);
                        }
                    };

                    $client->sendToTemp($channel, $message, [], $rpcReceiver);
                },
                function () use ($deferred, $client) {
                    $client->disconnect();
                    $deferred->reject(new \RuntimeException('Error start rpc connection'));
                }
            );

        $loop->run();

        return $deferred->promise();
    }
}
