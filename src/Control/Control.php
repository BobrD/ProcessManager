<?php

namespace Simples\ProcessManager\Control;

use Psr\Log\LoggerInterface;
use React\Promise\DeferredPromise;
use React\Stomp\Protocol\Frame;
use Simples\ProcessManager\Configuration;
use Simples\ProcessManager\Control\Command\InfoCommand;
use Simples\ProcessManager\Control\Command\AddProcessCommand;
use Simples\ProcessManager\Control\Command\RestartCommand;
use Simples\ProcessManager\Control\Command\StopCommand;
use Simples\ProcessManager\Exception\AbstractProcessManagerException;
use Simples\ProcessManager\Exception\ControlException;
use Simples\ProcessManager\Manager\ManagerFactory;
use Simples\ProcessManager\Manager\Manifest\ManifestInterface;
use Simples\ProcessManager\Message\MessageTransformer;
use Simples\ProcessManager\Message\Request;
use Simples\ProcessManager\Message\Response;
use Simples\ProcessManager\Rpc\RpcClient;

class Control
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RpcClient
     */
    private $rpcClient;

    /**
     * @var MessageTransformer
     */
    private $transformer;

    public function __construct(LoggerInterface $logger, RpcClient $rpcClient, MessageTransformer $transformer)
    {
        $this->logger = $logger;
        $this->rpcClient = $rpcClient;
        $this->transformer = $transformer;
    }

    public function start()
    {
        try {
            $config = $this->getConfig();

            $manager = ManagerFactory::create($config);

            $manifestFile = $config['manifestBuilderPath'];

            foreach ($this->loadManifests($manifestFile) as $manifest) {
                $manager->addManifest($manifest);
            }

            $manager->start();
        } catch (AbstractProcessManagerException $e) {
            $error = [
                'Message' => 'Trouble with start process manager',
                'ExceptionClass' => get_class($e),
                'ExceptionMessage' => $e->getMessage(),
                'ExceptionTrace' => $e->getTraceAsString()
            ];

            $this->logger->critical(implode('; ', $error));
        }
    }

    public function stop()
    {
        $this
            ->call(new StopCommand())
            ->then(function () {
                $this->logger->info('Manager stopped');
            }, function () {
                $this->logger->info('Error with sopping manager');
            });
    }

    public function restart()
    {
        $this
            ->call(new RestartCommand())
            ->then(function () {
                $this->logger->info('Manager restarted');
            }, function () {
                $this->logger->info('Error with restarting manager');
            });
    }

    public function addProcess($manifest)
    {
        $this
            ->call(new AddProcessCommand($manifest))
            ->then(function () use ($manifest) {
                $this->logger->info(sprintf('Process for %s added.', $manifest));
            }, function ()  use ($manifest) {
                $this->logger->info(sprintf('Error with adding process for %s', $manifest));
            });
    }

    /**
     * @return null|Response
     */
    public function info()
    {
        $response = null;
        $this
            ->call(new InfoCommand())
            ->then(function (Frame $frame) use (&$response){
                $response = $this->transformer->decodeResponse($frame->body);
            }, function () {
                $this->logger->info('Error with get info');
            });

        $await = 0;
        while (null === $response) {
            sleep(1);

            if (++$await > 3) {
                break;
            }
        }

        return $response;
    }

    private function getConfig()
    {
        if (empty($this->config)) {
            $this->config = Configuration::loadConfig();
        }

        return $this->config;
    }

    /**
     * @param Request $request
     * @return DeferredPromise
     */
    private function call(Request $request)
    {
        $message = $this->transformer->encodeRequest($request);

        return $this->rpcClient->call('manager', $message);
    }

    /**
     * @param $manifestFile
     * @return ManifestInterface[]
     * @throws ControlException
     */
    private function loadManifests($manifestFile)
    {
        if (empty($manifestFile) && !file_exists($manifestFile)) {
            throw ControlException::manifestFileNotResolved($manifestFile);
        }

        $manifests = include $manifestFile;

        foreach ($manifests as $manifest) {
            if (!$manifest instanceof ManifestInterface) {
                throw ControlException::invalidManifestClass();
            }
        }

        return $manifests;
    }
}
