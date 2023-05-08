<?php

namespace PE\Component\Stream\Tests;

use PE\Component\Socket\Exception\InvalidArgumentException;
use PE\Component\Socket\Exception\RuntimeException;
use PE\Component\Socket\Factory;
use PE\Component\Socket\Select;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class FactoryTest extends TestCase
{
    use PHPMock;

    public function testCreateClientFailureWithInvalidAddress()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Factory(new Select()))->createClient('http://127.0.0.1:9999');
    }

    public function testCreateClientFailureWithInvalidIP()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Factory(new Select()))->createClient('::0');
    }

    public function testCreateClientFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_client');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Factory(new Select()))->createClient('127.0.0.1:9999');
    }

    public function testCreateClient()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_client');
        $f->expects(self::once())->willReturn(fopen('php://temp', 'w+'));

        (new Factory(new Select()))->createClient('127.0.0.1:9999');
    }

    public function testCreateClientSecure()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_client');
        $f->expects(self::once())->willReturn(fopen('php://temp', 'w+'));

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        (new Factory(new Select()))->createClient('tls://127.0.0.1:9999');
    }

    public function testCreateServerFailureWithInvalidAddress()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Factory(new Select()))->createServer('http://127.0.0.1:9999');
    }

    public function testCreateServerFailureWithInvalidIP()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Factory(new Select()))->createServer('::0');
    }

    public function testCreateServerFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_server');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Factory(new Select()))->createServer('127.0.0.1:9999');
    }

    public function testCreateServer()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_server');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        (new Factory(new Select()))->createServer('127.0.0.1:9999');
    }

    public function testCreateServerSecure()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_server');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        (new Factory(new Select()))->createServer('tls://127.0.0.1:9999');
    }
}
