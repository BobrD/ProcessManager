<?php

namespace Simples\ProcessManager\Application;

use Simples\ProcessManager\Configuration;
use Simples\ProcessManager\Manager\ManagerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('start')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Файл с конфигураций')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userConfigFile = $input->getOption('config');

        try {
            $config =  $userConfigFile ? Configuration::loadConfig($userConfigFile): Configuration::loadConfig();
        } catch (\Exception $e) {
            die('Не удласо загрузить конфигурацию');
        }

        $manager = ManagerFactory::create($config);

        $manifestFile = $config['manifestBuilderPath'];

        if (empty($manifestFile) && !file_exists($manifestFile)) {
            $manager->getLogger()->critical('Файл с манифестами не найден');
        }

        $manifests = include $manifestFile;

        if (!is_array($manifests)) {
            $manager->getLogger()->critical('Файл манифестов должен содержать массив манифестов');
        }

        foreach ($manifests as $manifest) {
            $manager->addManifest($manifest);
        }

        $manager->run();
    }
}
