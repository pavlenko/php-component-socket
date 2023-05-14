<?php

namespace PE\Component\Socket;

use PE\Component\Socket\Exception\RuntimeException;

final class Server implements ServerInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private SocketInterface $socket;
    private SelectInterface $select;

    public function __construct(SocketInterface $socket, SelectInterface $select)
    {
        $this->socket = $socket;
        $this->socket->setBlocking(false);
        $this->socket->setBufferRD(0);

        $this->select = $select;
        $this->select->attachStreamRD($socket->getResource(), function () {
            try {
                $client = new Client($this->socket->accept(), $this->select);
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
        return $this->socket->getAddress(false);
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
        $this->socket->close();

        $this->onError = fn() => null;// Dummy callback
        $this->onInput = fn() => null;// Dummy callback
        $this->onClose = fn() => null;// Dummy callback
    }
}
