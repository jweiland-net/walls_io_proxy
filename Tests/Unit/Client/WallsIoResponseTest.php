<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Client;

use JWeiland\WallsIoProxy\Client\WallsIoResponse;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Walls IO Response Test
 */
class WallsIoResponseTest extends UnitTestCase
{
    /**
     * @var WallsIoResponse
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new WallsIoResponse();
    }

    protected function tearDown()
    {
        unset(
            $this->subject
        );
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getHeaderInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getHeader()
        );
    }

    /**
     * @test
     */
    public function setHeaderSetsHeader()
    {
        $this->subject->setHeader('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getHeader()
        );
    }

    /**
     * @test
     */
    public function setHeaderWithIntegerResultsInString()
    {
        $this->subject->setHeader(123);
        self::assertSame('123', $this->subject->getHeader());
    }

    /**
     * @test
     */
    public function setHeaderWithBooleanResultsInString()
    {
        $this->subject->setHeader(true);
        self::assertSame('1', $this->subject->getHeader());
    }

    /**
     * @test
     */
    public function getBodyInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getBody()
        );
    }

    /**
     * @test
     */
    public function setBodySetsBody()
    {
        $this->subject->setBody('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getBody()
        );
    }

    /**
     * @test
     */
    public function setBodyWithIntegerResultsInString()
    {
        $this->subject->setBody(123);
        self::assertSame('123', $this->subject->getBody());
    }

    /**
     * @test
     */
    public function setBodyWithBooleanResultsInString()
    {
        $this->subject->setBody(true);
        self::assertSame('1', $this->subject->getBody());
    }
}
