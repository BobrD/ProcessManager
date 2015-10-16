<?php

namespace Simples\ProcessManager;

class Configuration
{
    public static function loadConfig($path = '/etc/ppm/config.json')
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException('Файл конфигурации не найден');
        }

        $configJson = file_get_contents($path);

        $config = json_decode($configJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Не удалось обработать config');
        }

        return $config;
    }
}
