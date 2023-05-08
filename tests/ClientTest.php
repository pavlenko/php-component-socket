<?php

namespace PE\Component\Socket\Tests;

use PE\Component\Socket\Exception\RuntimeException;
use PE\Component\Socket\SelectInterface;
use PE\Component\Socket\Client;
use PE\Component\Socket\SocketInterface;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testGetClientAddress(): void
    {
        $select = $this->createMock(SelectInterface::class);
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())->method('getAddress')->with(false)->willReturn('ADDR');

        $client = new Client($socket, $select);
        self::assertSame('ADDR', $client->getClientAddress());
    }

    public function testGetRemoteAddress(): void
    {
        $select = $this->createMock(SelectInterface::class);
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())->method('getAddress')->with(true)->willReturn('ADDR');

        $client = new Client($socket, $select);
        self::assertSame('ADDR', $client->getRemoteAddress());
    }

    public function testOnInputFailure(): void
    {
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));
        $socket->expects(self::once())
            ->method('recv')
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

        $server = new Client($socket, $select);
        $server->setErrorHandler(function () use (&$errored) {
            $errored = true;
        });

        $dispatch();
        self::assertTrue($errored);
    }

    public function testOnInputNoData(): void
    {
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));
        $socket->expects(self::once())
            ->method('recv')
            ->willReturn('');
        $socket->expects(self::once())
            ->method('isEOF')
            ->willReturn(true);

        $dispatch = null;

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::any())
            ->method('attachStreamRD')
            ->with(self::isType('resource'), self::callback(function ($cb) use (&$dispatch) {
                $dispatch = $cb;
                return true;
            }));

        $closed = false;

        $server = new Client($socket, $select);
        $server->setCloseHandler(function () use (&$closed) {
            $closed = true;
        });

        $dispatch();
        self::assertTrue($closed);
    }

    public function testOnInput(): void
    {
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));
        $socket->expects(self::once())
            ->method('recv')
            ->willReturn('DATA');

        $dispatch = null;

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::any())
            ->method('attachStreamRD')
            ->with(self::isType('resource'), self::callback(function ($cb) use (&$dispatch) {
                $dispatch = $cb;
                return true;
            }));

        $data = null;

        $server = new Client($socket, $select);
        $server->setInputHandler(function () use (&$data) {
            $data = func_get_arg(0);
        });

        $dispatch();
        self::assertSame('DATA', $data);
    }

    public function testWriteNoResource(): void
    {
        $select = $this->createMock(SelectInterface::class);
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())->method('close');

        $closed = false;

        $client = new Client($socket, $select);
        $client->setCloseHandler(function () use (&$closed) {
            $closed = true;
        });
        $client->write('');

        self::assertTrue($closed);
    }

    public function testWriteNoData(): void
    {
        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::never())->method('attachStreamWR');

        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::any())->method('getResource')->willReturn(fopen('php://temp', 'w+'));

        $client = new Client($socket, $select);
        $client->write('');
    }

    public function testWriteFailure(): void
    {
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::any())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));
        $socket->expects(self::once())
            ->method('send')
            ->willThrowException(new RuntimeException());

        $dispatch = null;

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())
            ->method('attachStreamWR')
            ->with(self::isType('resource'), self::callback(function ($cb) use (&$dispatch) {
                $dispatch = $cb;
                return true;
            }));

        $errored = false;

        $client = new Client($socket, $select);
        $client->setErrorHandler(function () use (&$errored) {
            $errored = true;
        });
        $client->write('DATA');

        $dispatch();
        self::assertTrue($errored);
    }

    public function testWrite(): void
    {
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::any())
            ->method('getResource')
            ->willReturn(fopen('php://temp', 'w+'));
        $socket->expects(self::once())
            ->method('send')
            ->with('DATA')
            ->willReturn(4);

        $dispatch = null;

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())
            ->method('attachStreamWR')
            ->with(self::isType('resource'), self::callback(function ($cb) use (&$dispatch) {
                $dispatch = $cb;
                return true;
            }));
        $select->expects(self::once())->method('detachStreamWR');

        $client = new Client($socket, $select);
        $client->write('DATA');

        $dispatch();
    }

    public function testClose(): void
    {
        $select = $this->createMock(SelectInterface::class);
        $socket = $this->createMock(SocketInterface::class);
        $socket->expects(self::once())->method('close');

        $closed = false;

        $client = new Client($socket, $select);
        $client->setCloseHandler(function () use (&$closed) {
            $closed = true;
        });
        $client->close();

        self::assertTrue($closed);
    }
}
