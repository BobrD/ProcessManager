<?php

namespace Simples\ProcessManager\Message;

use Simples\ProcessManager\Exception\MessageTransformerException;
use SuperClosure\SerializerInterface;

class MessageTransformer
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param string $encoded
     * @return Request
     * @throws MessageTransformerException
     */
    public function decodeRequest($encoded)
    {
        $message = $this->decodeMessage($encoded);

        if (!$message instanceof Request) {
            throw new MessageTransformerException('Message should be instance of Request');
        }

        $string = $message->getClosure();

        $closure = $this->serializer->unserialize($string);

        return new Request($closure);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function encodeRequest(Request $request)
    {
        $closure = $request->getClosure();

        $serialized = $this->serializer->serialize($closure);

        $request = new Request($serialized);

        return $this->encodeMessage($request);
    }

    /**
     * @param string $encoded
     * @return Response
     * @throws MessageTransformerException
     */
    public function decodeResponse($encoded)
    {
        $encoded = $this->decodeMessage($encoded);

        if (!$encoded instanceof Response) {
            throw new MessageTransformerException('Message should be instance of Request');
        }

        return $encoded;
    }

    /**
     * @param Response $response
     * @return string
     */
    public function encodeResponse(Response $response)
    {
        return $this->encodeMessage($response);
    }

    private function encodeMessage($message)
    {
        return base64_encode(serialize($message));
    }

    /**
     * @param string $encoded
     * @return mixed
     * @throws MessageTransformerException
     */
    private function decodeMessage($encoded)
    {
        $encoded = base64_decode($encoded);

        if (false === $encoded) {
            throw new MessageTransformerException('Base64_decode error');
        }

        $encoded = @unserialize($encoded);

        if (false === $encoded) {
            throw new MessageTransformerException('Unserialize error');
        }

        return $encoded;
    }
}
