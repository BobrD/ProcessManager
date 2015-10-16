<?php

namespace Simples\ProcessManager\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->isLinux()) {
            $output->writeln('<error>Установка прервана. Операционая система должны быить Linux</error>');
            return;
        }

        if (!$this->isRoot()) {
            $output->writeln('<error>Установка прервана. Установка должна производится с правами root.</error>');
            return;
        }

        $output->writeln('<info>Создаём файл конфигурации "/etc/ppm/config.json"</info>');
        $this->createConfigFile();

        $output->writeln('<info>Создаём директори для логов "/var/log/ppm"</info>');
        $this->createDir('/var/log/ppm');

        $output->writeln('<info>Копируем файлы</info>');
        if (!$this->selfCopy('/opt/ppm/')) {
            $output->writeln('<error>Установка прервана. Не удалось скопировать файлы.</error>');
            return;
        }

        $output->writeln('<info>Создаём init.d</info>');
        $this->createInidt();
    }

    private function createDir($dir)
    {
        if (!file_exists($dir)) {
            return mkdir($dir, 0644, true);
        }

        return true;
    }

    private function createConfigFile()
    {
        $defaultConfig = [
            'logDirPath' => '/var/log/ppm',
            'manifestBuilderPath' => null
        ];

        $this->createDir('/etc/ppm');

        $fh = fopen('/etc/ppm/config.json', 'w');

        fwrite($fh, json_encode($defaultConfig, JSON_PRETTY_PRINT));

        fclose($fh);
    }

    private function createInidt()
    {
        $file = '/etc/init.d/ppm';
        $fh = fopen($file, 'w');
        fwrite($fh, $this->initDPattern());
        fclose($fh);

        chmod($file, 0755);
    }

    private function initDPattern()
    {
        return <<<'INITD'
#! /bin/sh

NAME=ppm
DESC="Daemon for my magnificent PHP CLI script"
PIDFILE="/var/run/${NAME}.pid"
LOGFILE="/var/log/${NAME}.log"

DAEMON="/usr/bin/php"
DAEMON_OPTS="ppm.phar start"

START_OPTS="--start --background --make-pidfile --pidfile ${PIDFILE} --exec ${DAEMON} ${DAEMON_OPTS}"
STOP_OPTS="--stop --pidfile ${PIDFILE}"

test -x $DAEMON || exit 0

set -e

case "$1" in
    start)
        echo -n "Starting ${DESC}: "
        start-stop-daemon $START_OPTS >> $LOGFILE
        echo "$NAME."
    ;;

    stop)
        echo -n "Stopping $DESC: "
        start-stop-daemon $STOP_OPTS
        echo "$NAME."
        rm -f $PIDFILE
    ;;

    restart|force-reload)
        echo -n "Restarting $DESC: "
        start-stop-daemon $STOP_OPTS
        sleep 1
        start-stop-daemon $START_OPTS >> $LOGFILE
        echo "$NAME."
    ;;

    *)
    N=/etc/init.d/$NAME
    echo "Usage: $N {start|stop|restart|force-reload}" >&2
    exit 1
    ;;

esac

exit 0
INITD;
    }

    /**
     * @return bool
     */
    private function isLinux()
    {
        return PHP_OS === 'Linux';
    }

    /**
     * @return string
     */
    private function getMainScript()
    {
        $stack = debug_backtrace();
        $firstFrame = $stack[count($stack) - 1];
        $initialFile = $firstFrame['file'];

        return $initialFile;
    }


    /**
     * @param string $path
     * @return bool
     */
    private function selfCopy($path)
    {
        $this->createDir($path);

        $main = $this->getMainScript();
        return copy($main, $path . 'ppm.phar');
    }

    /**
     * @return bool
     */
    private function isRoot()
    {
        return 0 === posix_getuid();
    }
}
