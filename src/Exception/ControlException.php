<?php

namespace Simples\ProcessManager\Exception;

class ControlException extends AbstractProcessManagerException
{
    public static function manifestFileNotResolved($file)
    {
        return new self(sprintf('Manifest file %s not resolved', $file));
    }

    public static function invalidManifestClass()
    {
        return new self('Manifest class should implement \Simples\ProcessManager\Manager\Manifest\ManifestInterface');
    }
}
