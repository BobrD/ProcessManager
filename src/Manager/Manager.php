<?php

namespace Simples\ProcessManager\Manager;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Stomp\Client;
use React\Stomp\Protocol\Frame;
use Simples\ProcessManager\Exception\ManagerException;
use Simples\ProcessManager\Manager\Manifest\ManifestInterface;
use Simples\ProcessManager\Manager\Manifest\NullProcess;
use Simples\ProcessManager\Manager\Manifest\ProvisionInterface;
use Simples\ProcessManager\Message\MessageTransformer;
use Simples\ProcessManager\Message\Response;
use Simples\ProcessManager\Rpc\RpcClient;
use Simples\ProcessManager\Rpc\StompClient;
use Simples\ProcessManager\Rpc\StompFactory;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class Manager
{
    const PROVISION_TIME = 2;

    /**
     * Массив манифесто где ключ - имя манифеста
     *
     * @var array|ManifestInterface[]
     */
    private $manifests = [];

    /**
     * @var array|ProvisionInterface[]
     */
    private $nextProvision = [];

    /**
     * @var array|Process[]
     */
    private $runningProcess = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RpcClient
     */
    private $rpcClient;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var StompClient
     */
    private $client;

    /**
     * @var MessageTransformer
     */
    private $messageTransformer;

    /**
     * @var StompFactory
     */
    private $stompFactory;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var Pidfile
     */
    private $pidfile;

    /**
     * @param LoggerInterface $logger
     * @param RpcClient $rpcClient
     * @param MessageTransformer $messageTransformer
     * @param StompFactory $stompFactory
     * @param LoopInterface $loop
     * @param string $channel
     */
    public function __construct(
        LoggerInterface $logger,
        RpcClient $rpcClient,
        MessageTransformer $messageTransformer,
        StompFactory $stompFactory,
        LoopInterface $loop,
        $channel = 'manager'
    ) {
        $this->logger = $logger;
        $this->rpcClient = $rpcClient;
        $this->messageTransformer = $messageTransformer;
        $this->stompFactory = $stompFactory;
        $this->loop = $loop;
        $this->channel = $channel;
        $this->pidfile = new Pidfile('ppm_' . $channel);
    }

    /**
     * @param ManifestInterface $manifest
     */
    public function addManifest(ManifestInterface $manifest)
    {
        $this->manifests[$manifest->getName()] = $manifest;
    }

    public function addNextProvision(ProvisionInterface $provisionInterface)
    {
        $this->nextProvision[] = $provisionInterface;
    }

    /**
     * @return array|ManifestInterface[]
     */
    public function getManifests()
    {
        return $this->manifests;
    }

    /**
     * Запуск менеджера
     */
    public function start()
    {
        $this->fork();
        $this->pidfile->initialize();
        $this->runProcesses();
        $this->startLoop();
    }

    public function stop()
    {
        $this->stopProcess();
        $this->stopLoop();
        $this->pidfile->finalize();
    }

    /**
     * Start all process
     */
    public function runProcesses()
    {
        foreach ($this->manifests as $manifest) {
            foreach ($manifest->getProcesses() as $process) {
                $this->runProcess($process);
            }
        }
    }

    /**
     * Создаёт ещё один процесс для манифеста
     *
     * @param string $manifestName
     */
    public function startNewProcess($manifestName)
    {
        if (isset($this->manifests[$manifestName])) {
            $newProcess = $this->manifests[$manifestName]->newProcess();
            $this->runProcess($newProcess);
        }
    }

    /**
     * Reload
     */
    public function reloadProcess()
    {
        $this->stopProcess();
        $this->runProcesses();
    }

    /**
     * Stop all process
     */
    public function stopProcess()
    {
        foreach ($this->getRunningProcesses() as $process) {
            $commandLine = $process->getCommandLine();

            try {
                if (!$process->isRunning()) {
                    continue;
                }

                $exitCode = $process->stop();
                $this->logger->info(sprintf('Остановлен процесс (cmd %s), код выхода %d', $commandLine, $exitCode));
            } catch (RuntimeException $e) {
                $this->logger->critical(sprintf(
                    'Неудалось отсновить процес (cmd %s), exception: %s, trace: %s',
                    $commandLine,
                    $e->getMessage(),
                    $e->getTraceAsString()
                ));
            }
        }
    }

    /**
     * @param Process $process
     */
    public function runProcess(Process $process)
    {
        $commandLine = $process->getCommandLine();
        $cwd = $process->getWorkingDirectory();
        $input = $process->getInput();

        try {
            // @sea https://github.com/symfony/symfony/issues/5759
            if (false === strpos($commandLine, 'exec ')) {
                $commandLine = 'exec ' . $commandLine;
            }

            $process->setCommandLine($commandLine);

            $process->start();

            $this->runningProcess[spl_object_hash($process)] = $process;

            $this->logger->info(sprintf(
                'Запустили процес (cmd %s, cwd %s, input %s)',
                $commandLine,
                $cwd,
                $input
            ));

        } catch (RuntimeException $e) {
            $this->logger->critical(sprintf(
                'Неудалось запустить процес (cmd %s, cwd %s, input %s) exception: %s, trace: %s',
                $commandLine,
                $cwd,
                $input,
                $e->getMessage(),
                $e->getTraceAsString()
            ));
        }
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return array|Process[]
     */
    public function getRunningProcesses()
    {
        return $this->runningProcess;
    }

    private function fork()
    {
        // Форкаем процесс
        $pid = pcntl_fork();

        if ($pid == -1) {
            die('Error with manager');
        } elseif ($pid) {
            // Убиваем родительский процес
            exit;
        }
    }

    /**
     * Start manager loop
     */
    private function startLoop()
    {
        $this->client = $this->stompFactory->createClient();

        $this->client
            ->connect()
            ->then(function (Client $client) {
                $this->loop->addPeriodicTimer(self::PROVISION_TIME, function () {
                    $this->provision();
                });

                $client->subscribe($this->channel, function (Frame $frame) use ($client) {
                    try {
                        $request = $this->messageTransformer->decodeRequest($frame->body);

                        $closure = $request->getClosure();

                        if (!is_callable($closure)) {
                            throw new ManagerException('Запрос не содерджит callable');
                        }
                        $result = $closure($this);
                        $response = new Response($result);
                    } catch (\Exception $e) {
                        $this->logger->error('Exception при обработке запроса ' . $e->getMessage());
                        $response = new Response($e->getMessage(), Response::STATUS_ERROR);
                    }

                    if ($replayTo = $frame->getHeader('reply-to')) {
                        $body = $this->messageTransformer->encodeResponse($response);
                        $client->send($replayTo, $body);
                    }
                });
            }, function (\Exception $e) {
                $this->logger->critical($e->getMessage());
            });

        $this->loop->run();
    }

    /**
     * Stop loop
     */
    private function stopLoop()
    {
        $this->client->disconnect();
        $this->loop->stop();
    }

    /**
     * Run provision
     */
    public function provision()
    {
        foreach ($this->nextProvision as $provision) {
            $provision->provision($this);
        }

        $this->nextProvision = [];

        foreach ($this->manifests as $manifest) {
            try {
                $manifest->getProvision()->provision($this);
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
