<?php

namespace PE\Component\Stream\Tests;

use PE\Component\Socket\Exception\RuntimeException;
use PE\Component\Socket\Select;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class SelectTest extends TestCase
{
    use PHPMock;

    public function testSelectFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_select');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        $select = new Select();
        $select->attachStreamRD(fopen('php://temp', 'w+'), fn() => null);
        $select->dispatch();
    }

    public function testSelect()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_select');
        $f->expects(self::once())->willReturn(2);

        $select = new Select();
        $select->attachStreamRD(fopen('php://temp', 'w+'), fn() => self::assertTrue(true));
        $select->attachStreamWR(fopen('php://temp', 'w+'), fn() => self::assertTrue(true));
        $select->dispatch();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCleanup()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_select');
        $f->expects(self::never());

        $select = new Select();
        $select->attachStreamRD($s1 = fopen('php://temp', 'w+'), fn() => self::assertTrue(true));
        $select->attachStreamWR($s2 = fopen('php://temp', 'w+'), fn() => self::assertTrue(true));
        @fclose($s1);
        @fclose($s2);
        $select->dispatch();
    }

    public function testDetach()
    {
        $f = $this->getFunctionMock('PE\Component\Socket', 'stream_select');
        $f->expects(self::never());

        $select = new Select();

        $select->attachStreamRD($rd = fopen('php://temp', 'w+'), fn() => self::assertTrue(true));
        $select->attachStreamWR($wr = fopen('php://temp', 'w+'), fn() => self::assertTrue(true));

        $select->detachStreamRD($rd);
        $select->detachStreamWR($wr);

        $select->dispatch();
    }
}
