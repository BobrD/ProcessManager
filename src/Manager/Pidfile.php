<?php

namespace Simples\ProcessManager\Manager;

class Pidfile
{
    /**
     * @var string
     */
    protected $applicationName;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var resource
     */
    protected $fileResource;

    /**
     * @var string
     */
    protected $lockDirectory;

    /**
     * @var int
     */
    protected $processId;

    /**
     * @param string  $applicationName The application name, used as pidfile basename
     * @param string  $lockDirectory   Directory were pidfile is stored
     */
    public function __construct($applicationName = 'arara', $lockDirectory = '/var/run')
    {
        $this->assertApplicationName($applicationName);
        $this->assertLockDirectory($lockDirectory);
    }

    /**
     * Returns the application's name.
     *
     * @return string
     */
    public function getApplicationName()
    {
        return $this->applicationName;
    }


    /**
     * Returns the Pidfile filename.
     *
     * @return string
     */
    protected function getFileName()
    {
        if (null === $this->fileName) {
            $this->fileName = $this->lockDirectory.'/'.$this->applicationName.'.pid';
        }

        return $this->fileName;
    }

    /**
     * Returns the Pidfile file resource.
     *
     * @return resource
     */
    protected function getFileResource()
    {
        if (null === $this->fileResource) {
            $fileResource = @fopen($this->getFileName(), 'a+');
            if (! $fileResource) {
                throw new \RuntimeException('Could not open pidfile');
            }
            $this->fileResource = $fileResource;
        }

        return $this->fileResource;
    }

    /**
     * Returns TRUE when pidfile is active or false when is not.
     *
     * @return bool
     */
    public function isActive()
    {
        $pid = $this->getProcessId();
        if (null === $pid) {
            return false;
        }

        return posix_kill($pid, 0);
    }

    /**
     * Returns Pidfile content with the PID or null when there is no stored PID.
     *
     * @return int|null
     */
    public function getProcessId()
    {
        if (null === $this->processId) {
            $content = fgets($this->getFileResource());
            $pieces = explode(PHP_EOL, trim($content));
            $this->processId = reset($pieces) ?: 0;
        }

        return $this->processId ?: null;
    }

    /**
     * Initializes pidfile.
     *
     * Create an empty file, store the PID into the file and lock it.
     */
    public function initialize()
    {
        if ($this->isActive()) {
            throw new \RuntimeException('Process is already active');
        }

        $handle = $this->getFileResource();

        if (!@flock($handle, (LOCK_EX | LOCK_NB))) {
            throw new \RuntimeException('Could not lock pidfile');
        }

        if (-1 === @fseek($handle, 0)) {
            throw new \RuntimeException('Could not seek pidfile cursor');
        }

        if (! @ftruncate($handle, 0)) {
            throw new \RuntimeException('Could not truncate pidfile');
        }

        if (! @fwrite($handle, $this->getCurrentPid() . PHP_EOL)) {
            throw new \RuntimeException('Could not write on pidfile');
        }
    }

    /**
     * Finalizes pidfile.
     *
     * Unlock pidfile and removes it.
     */
    public function finalize()
    {
        @flock($this->getFileResource(), LOCK_UN);
        @fclose($this->getFileResource());
        @unlink($this->getFileName());
    }

    /**
     * @param string $applicationName The application name, used as pidfile basename.
     *
     * @throws \InvalidArgumentException When application name is not valid.
     */
    protected function assertApplicationName($applicationName)
    {
        if ($applicationName != strtolower($applicationName)) {
            throw new \InvalidArgumentException('Application name should be lowercase');
        }
        if (preg_match('/[^-_a-z0-9]/', $applicationName)) {
            throw new \InvalidArgumentException(sprintf('Application name should contains only alphanumeric chars, %s given', $applicationName));
        }

        if (strlen($applicationName) > 16) {
            $message = 'Application name should be no longer than 16 characters';
            throw new \InvalidArgumentException($message);
        }

        $this->applicationName = $applicationName;
    }

    /**
     * @param string $lockDirectory Directory were pidfile should be stored.
     *
     * @throws \InvalidArgumentException When lock directory is not valid.
     */
    protected function assertLockDirectory($lockDirectory)
    {
        if (! is_dir($lockDirectory)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid directory', $lockDirectory));
        }

        if (! is_writable($lockDirectory)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a writable directory', $lockDirectory));
        }

        $this->lockDirectory = $lockDirectory;
    }

    protected function getCurrentPid()
    {
        return posix_getpid();
    }
}