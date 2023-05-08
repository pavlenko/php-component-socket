<?php

namespace PE\Component\Socket\Tests;

use PE\Component\Socket\Exception\RuntimeException;
use PE\Component\Socket\Socket;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class SocketTest extends TestCase
{
    use PHPMock;

    /**
     * @return resource
     */
    private function getResource()
    {
        return fopen('php://temp', 'w+');
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'get_resource_type');
        $f->expects(self::once())->willReturn('foo');

        new Socket($this->getResource());
    }

    public function testResource(): void
    {
        $resource = $this->getResource();
        $stream   = new Socket($resource);

        self::assertSame($resource, $stream->getResource());
        self::assertFalse($stream->isEOF());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressOnClosedResource(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_get_name');
        $f->expects(self::never());//->with(self::isType('resource'), true)->willReturn('address');

        $stream = new Socket($resource = fopen('php://temp', 'w+'));
        fclose($resource);
        self::assertNull($stream->getAddress(true));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressRemote(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_get_name');
        $f->expects(self::once())->with(self::isType('resource'), true)->willReturn('address');

        $stream = new Socket($this->getResource());
        self::assertSame('tcp://address', $stream->getAddress(true));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressLocal(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_get_name');
        $f->expects(self::once())->with(self::isType('resource'), false)->willReturn('address');

        $stream = new Socket($this->getResource());
        self::assertSame('tcp://address', $stream->getAddress(false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressIPv6(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_get_name');
        $f->expects(self::once())->willReturn(':::0');

        $stream = new Socket($this->getResource());
        self::assertSame('tcp://[::]:0', $stream->getAddress(true));
    }

    public function testSetCryptoFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Socket($this->getResource()))->setCrypto(true);
    }

    public function testSetCrypto()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        (new Socket($this->getResource()))->setCrypto(true);
    }

    public function testSetTimeoutFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_timeout');
        $f->expects(self::once())->willReturn(false);

        (new Socket($this->getResource()))->setTimeout(1);
    }

    public function testSetTimeoutSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_timeout');
        $f->expects(self::once())->willReturn(true);

        (new Socket($this->getResource()))->setTimeout(1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetBlockingFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_blocking');
        $f->expects(self::once())->willReturn(false);

        (new Socket($this->getResource()))->setBlocking(true);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetBlockingSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_blocking');
        $f->expects(self::once())->willReturn(true);

        (new Socket($this->getResource()))->setBlocking(true);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetBufferRFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Socket($this->getResource()))->setBufferRD(1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetBufferRSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Socket($this->getResource()))->setBufferRD(1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetBufferWFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Socket($this->getResource()))->setBufferWR(1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetBufferWSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Socket($this->getResource()))->setBufferWR(1);
    }

    public function testAcceptFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_accept');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Socket($this->getResource()))->accept();
    }

    public function testAccept()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_accept');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $client = (new Socket($this->getResource()))->accept();
        self::assertSame($r, $client->getResource());
    }

    public function testAcceptEncrypted()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_accept');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_socket_enable_crypto');
        $f->expects(self::exactly(2))->willReturn(true);

        $master = new Socket($this->getResource());
        $master->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_SERVER);

        $client = $master->accept();
        self::assertSame($r, $client->getResource());
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadDataFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_get_contents');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Socket($this->getResource()))->recv();
    }

    public function testReadDataSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_get_contents');
        $f->expects(self::once())->willReturn('');

        (new Socket($this->getResource()))->recv();
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendDataFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'fwrite');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Socket($this->getResource()))->send('D');
    }

    public function testSendDataSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'fwrite');
        $f->expects(self::once())->willReturn(1);

        (new Socket($this->getResource()))->send('D');
    }

    public function testCloseSkipped(): void
    {
        $f1 = $this->getFunctionMock('PE\Component\Socket', 'is_resource');
        $f1->expects(self::once())->willReturn(false);

        $f2 = $this->getFunctionMock('PE\Component\Socket', 'fclose');
        $f2->expects(self::never());

        (new Socket($this->getResource()))->close();
    }

    public function testCloseSuccess(): void
    {
        $f1 = $this->getFunctionMock('PE\Component\Socket', 'is_resource');
        $f1->expects(self::once())->willReturn(true);

        $f2 = $this->getFunctionMock('PE\Component\Socket', 'fclose');
        $f2->expects(self::once());

        (new Socket($this->getResource()))->close();
    }
}
