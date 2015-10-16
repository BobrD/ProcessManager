<?php

namespace Simples\ProcessManager\Tests\Manger\Manifest;

use Simples\ProcessManager\Manager\Manifest\CallbackProvision;
use Simples\ProcessManager\Manager\Manifest\DefaultManifest;
use Simples\ProcessManager\Manager\Manifest\NullProcess;
use Simples\ProcessManager\Manager\Manifest\NullProvision;
use Symfony\Component\Process\Process;

class ManifestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Manifest name should be string
     */
    public function testCreteWithoutValidName()
    {
        new DefaultManifest(1);
    }

    public function testManifestDefaultCreateNullObject()
    {
        $manifest = new DefaultManifest('test');

        $this->assertInstanceOf(NullProcess::class, $manifest->getProcess());
        $this->assertInstanceOf(NullProvision::class, $manifest->getProvision());
    }

    public function testGet()
    {
        $provision = new CallbackProvision(function (){});
        $process = new Process('');
        $manifest = new DefaultManifest('test', $provision, $process);

        $this->assertEquals('test', $manifest->getName());
        $this->assertEquals($provision, $manifest->getProvision());
        $this->assertEquals($process, $manifest->getProcess());
    }
}

