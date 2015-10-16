<?php

namespace Simples\ProcessManager\Tests\Message;

use Simples\ProcessManager\Message\MessageTransformer;
use Simples\ProcessManager\Message\Request;
use Simples\ProcessManager\Message\Response;
use SuperClosure\Serializer;

class MessageTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testDecodeEncodeRequest()
    {
        $serializer = new Serializer();
        $transformer = new MessageTransformer($serializer);

        $request = new Request(function () use ($transformer) {
            $this->assertInstanceOf(MessageTransformer::class, $transformer);
        });

        $encoded = $transformer->encodeRequest($request);

        $decoded = $transformer->decodeRequest($encoded);

        $closure = $decoded->getClosure();

        $closure();
    }

    public function testDecodeEncodeResponse()
    {
        $response = new Response();

        $serializer = new Serializer();
        $transformer = new MessageTransformer($serializer);

        $encoded = $transformer->encodeResponse($response);

        $decoded = $transformer->decodeResponse($encoded);

        $this->assertEquals($response->data(), $decoded->data());
        $this->assertEquals($response->status(), $decoded->status());
    }

    /**
     * @expectedException \Simples\ProcessManager\Exception\MessageTransformerException
     */
    public function testTrowExceptionIfInvalidEncodedString()
    {
        $serializer = new Serializer();
        $transformer = new MessageTransformer($serializer);

        $invalidEncodedString = 'str';

        $transformer->decodeRequest($invalidEncodedString);
    }

    /**
     * @expectedException \Simples\ProcessManager\Exception\MessageTransformerException
     */
    public function testTrowExceptionIfCallInvalidMethodForResponse()
    {
        $response = new Response();

        $serializer = new Serializer();
        $transformer = new MessageTransformer($serializer);

        $encoded = $transformer->encodeResponse($response);

        $transformer->decodeRequest($encoded);
    }

    /**
     * @expectedException \Simples\ProcessManager\Exception\MessageTransformerException
     */
    public function testTrowExceptionIfCallInvalidMethodForRequest()
    {
        $request = new Request(function () {});

        $serializer = new Serializer();
        $transformer = new MessageTransformer($serializer);

        $encoded = $transformer->encodeRequest($request);

        $transformer->decodeResponse($encoded);
    }
}
