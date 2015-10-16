<?php

namespace Simples\ProcessManager\Tests\Manger\Manifest;

use Simples\ProcessManager\Manager\Manager;
use Simples\ProcessManager\Manager\Manifest\CallbackProvision;

class CallbackProvisionTest extends \PHPUnit_Framework_TestCase
{
    public function testCallCallback()
    {
        $callback = function ($manager) {
            $this->assertInstanceOf(Manager::class, $manager);
        };

        $provision = new CallbackProvision($callback);

        $manager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->getMock();

        $provision->provision($manager);
    }
}

