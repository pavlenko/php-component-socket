<?php

namespace PE\Component\Socket\Tests;

use PE\Component\Socket\ClientInterface;
use PE\Component\Socket\Exception\RuntimeException;
use PE\Component\Socket\SelectInterface;
use PE\Component\Socket\Server;
use PE\Component\Socket\SocketInterface;
use PHPUnit\Framework\TestCase;

final class ServerTest extends TestCase
{
    public function testGetAddress(): void
    {
        $select = $this->createMock(SelectInterface::class);
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())->method('getAddress')->with(false)->willReturn('ADDR');

        $server = new Server($socket, $select);
        self::assertSame('ADDR', $server->getAddress());
    }

    public function testOnInputFailure(): void
    {
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));
        $socket->expects(self::once())
            ->method('accept')
            ->willThrowException(new RuntimeException());

        $dispatch = null;

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::any())
            ->method('attachStreamRD')
            ->with(self::isType('resource'), self::callback(function ($cb) use (&$dispatch) {
                $dispatch = $cb;
                return true;
            }));

        $errored = false;

        $server = new Server($socket, $select);
        $server->setErrorHandler(function () use (&$errored) {
            $errored = true;
        });

        $dispatch();
        self::assertTrue($errored);
    }

    public function testOnInput(): void
    {
        $client = $this->createMock(SocketInterface::class);
        $client->expects(self::once())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));

        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));
        $socket->expects(self::once())
            ->method('accept')
            ->willReturn($client);

        $dispatch = null;

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::any())
            ->method('attachStreamRD')
            ->with(self::isType('resource'), self::callback(function ($cb) use (&$dispatch) {
                $dispatch = $cb;
                return true;
            }));

        $connection = null;

        $server = new Server($socket, $select);
        $server->setInputHandler(function ($client) use (&$connection) {
            $connection = $client;
        });

        $dispatch();
        self::assertInstanceOf(ClientInterface::class, $connection);
    }

    public function testClose(): void
    {
        $select = $this->createMock(SelectInterface::class);
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())->method('close');

        $closed = false;

        $server = new Server($socket, $select);
        $server->setCloseHandler(function () use (&$closed) {
            $closed = true;
        });
        $server->close();

        self::assertTrue($closed);
    }
}
