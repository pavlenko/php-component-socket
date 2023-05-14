<?php

namespace PE\Component\Socket;

use PE\Component\Socket\Exception\RuntimeException;

final class Client implements ClientInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private bool $writable = true;
    private string $buffer = '';

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
                $data = $this->socket->recv();
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
                return;
            }

            if ($data !== '') {
                call_user_func($this->onInput, $data);
            } elseif ($this->socket->isEOF()) {
                $this->close('Disconnected on RD');
            }
        });

        $this->onError = fn() => null;// Dummy callback
        $this->onInput = fn() => null;// Dummy callback
        $this->onClose = fn() => null;// Dummy callback
    }

    public function getClientAddress(): ?string
    {
        return $this->socket->getAddress(false);
    }

    public function getRemoteAddress(): ?string
    {
        return $this->socket->getAddress(true);
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

    public function write(string $data, bool $close = false): void
    {
        if (!is_resource($this->socket->getResource())) {
            $this->close('Disconnected on WR');
            return;
        }

        if (empty($data) || !$this->writable) {
            return;
        }

        if ($close) {
            $this->writable = false;
        }

        $this->buffer .= $data;
        $this->select->attachStreamWR($this->socket->getResource(), function () {
            try {
                $sent = $this->socket->send($this->buffer);
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
                return;
            }

            $this->buffer = substr($this->buffer, $sent);
            if (empty($this->buffer)) {
                $this->select->detachStreamWR($this->socket->getResource());
                if (!$this->writable) {
                    $this->close();
                }
            }
        });
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
