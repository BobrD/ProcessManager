<?php

namespace Simples\ProcessManager\Rpc;

use React\Stomp\Client;

class StompClient extends Client
{
    public function sendToTemp($destination, $body, array $headers = array(), callable $callback)
    {
        $subscriptionId = '/temp-queue/' . $destination;

        $headers['reply-to'] = $subscriptionId;

        $this->send($destination, $body, $headers);

        $reflection = new \ReflectionClass(parent::class);

        $subscriptionsProperty = $reflection->getProperty('subscriptions');
        $subscriptionsProperty->setAccessible(true);
        $value = $subscriptionsProperty->getValue($this);
        $value[$subscriptionId] = $callback;
        $subscriptionsProperty->setValue($this, $value);

        $acknowledgementsProperty = $reflection->getProperty('acknowledgements');
        $acknowledgementsProperty->setAccessible(true);
        $value = $acknowledgementsProperty->getValue($this);
        $value[$subscriptionId] = 'auto';
        $acknowledgementsProperty->setValue($this, $value);
    }
}