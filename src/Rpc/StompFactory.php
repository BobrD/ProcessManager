<?php

namespace Simples\ProcessManager\Rpc;

use React\Stomp\Factory;
use React\EventLoop\LoopInterface;
use React\Stomp\Io\InputStream;
use React\Stomp\Io\OutputStream;
use React\Stomp\Protocol\Parser;

class StompFactory extends Factory
{
    private $defaultOptions = array(
        'host'      => '127.0.0.1',
        'port'      => 61613,
        'vhost'     => '/',
        'login'     => 'guest',
        'passcode'  => 'guest',
    );

    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;

        parent::__construct($loop);
    }

    public function createClient(array $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);

        $conn = $this->createConnection($options);

        $parser = new Parser();
        $input = new InputStream($parser);
        $conn->pipe($input);

        $output = new OutputStream($this->loop);
        $output->pipe($conn);

        $conn->on('error', function ($e) use ($input) {
            $input->emit('error', array($e));
        });

        return new StompClient($this->loop, $input, $output, $options);
    }
}