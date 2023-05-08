<?php

namespace PE\Component\Socket;

use PE\Component\Socket\Exception\RuntimeException;

final class Server implements ServerInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private SocketInterface $stream;
    private SelectInterface $select;

    public function __construct(SocketInterface $stream, SelectInterface $select)
    {
        $this->stream = $stream;
        $this->stream->setBlocking(false);
        $this->stream->setBufferRD(0);

        $this->select = $select;
        $this->select->attachStreamRD($stream->getResource(), function () {
            try {
                $client = new Client($this->stream->accept(), $this->select);
                call_user_func($this->onInput, $client);
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
            }
        });

        $this->onError = fn() => null;// Dummy callback
        $this->onInput = fn() => null;// Dummy callback
        $this->onClose = fn() => null;// Dummy callback
    }

    public function getAddress(): ?string
    {
        return $this->stream->getAddress(false);
    }

    public function setInputHandler(callable $handler): void
    {
        $this->onInput = \Closure::fromCallable($handler);
    }

    public function setErrorHandler(callable $handler): void
    {
        $this->onError = \Closure::fromCallable($handler);
    }

    public function setCloseHandler(callable $handler): void
    {
        $this->onClose = \Closure::fromCallable($handler);
    }

    public function close(string $message = null): void
    {
        call_user_func($this->onClose, $message);
        $this->stream->close();

        $this->onError = fn() => null;// Dummy callback
        $this->onInput = fn() => null;// Dummy callback
        $this->onClose = fn() => null;// Dummy callback
    }
}
