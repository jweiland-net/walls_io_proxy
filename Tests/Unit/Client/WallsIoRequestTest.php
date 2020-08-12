<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Client;

use JWeiland\WallsIoProxy\Client\WallsIoRequest;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Walls IO Request Test
 */
class WallsIoRequestTest extends UnitTestCase
{
    /**
     * @var WallsIoRequest
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new WallsIoRequest();
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
    public function getWallIdInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getWallId()
        );
    }

    /**
     * @test
     */
    public function setWallIdSetsWallId()
    {
        $this->subject->setWallId(123456);

        self::assertSame(
            123456,
            $this->subject->getWallId()
        );
    }

    /**
     * @test
     */
    public function setWallIdWithStringResultsInInteger()
    {
        $this->subject->setWallId('123Test');

        self::assertSame(
            123,
            $this->subject->getWallId()
        );
    }

    /**
     * @test
     */
    public function setWallIdWithBooleanResultsInInteger()
    {
        $this->subject->setWallId(true);

        self::assertSame(
            1,
            $this->subject->getWallId()
        );
    }

    /**
     * @test
     */
    public function getSessionIdInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getSessionId()
        );
    }

    /**
     * @test
     */
    public function setSessionIdSetsSessionId()
    {
        $this->subject->setSessionId('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getSessionId()
        );
    }

    /**
     * @test
     */
    public function setSessionIdWithIntegerResultsInString()
    {
        $this->subject->setSessionId(123);
        self::assertSame('123', $this->subject->getSessionId());
    }

    /**
     * @test
     */
    public function setSessionIdWithBooleanResultsInString()
    {
        $this->subject->setSessionId(true);
        self::assertSame('1', $this->subject->getSessionId());
    }

    /**
     * @test
     */
    public function getEntriesToLoadInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getEntriesToLoad()
        );
    }

    /**
     * @test
     */
    public function setEntriesToLoadSetsEntriesToLoad()
    {
        $this->subject->setEntriesToLoad(123456);

        self::assertSame(
            123456,
            $this->subject->getEntriesToLoad()
        );
    }

    /**
     * @test
     */
    public function setEntriesToLoadWithStringResultsInInteger()
    {
        $this->subject->setEntriesToLoad('123Test');

        self::assertSame(
            123,
            $this->subject->getEntriesToLoad()
        );
    }

    /**
     * @test
     */
    public function setEntriesToLoadWithBooleanResultsInInteger()
    {
        $this->subject->setEntriesToLoad(true);

        self::assertSame(
            1,
            $this->subject->getEntriesToLoad()
        );
    }

    /**
     * @test
     */
    public function getIncludeHeaderInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getIncludeHeader()
        );
    }

    /**
     * @test
     */
    public function setIncludeHeaderSetsIncludeHeader()
    {
        $this->subject->setIncludeHeader(123456);

        self::assertSame(
            123456,
            $this->subject->getIncludeHeader()
        );
    }

    /**
     * @test
     */
    public function setIncludeHeaderWithStringResultsInInteger()
    {
        $this->subject->setIncludeHeader('123Test');

        self::assertSame(
            123,
            $this->subject->getIncludeHeader()
        );
    }

    /**
     * @test
     */
    public function setIncludeHeaderWithBooleanResultsInInteger()
    {
        $this->subject->setIncludeHeader(true);

        self::assertSame(
            1,
            $this->subject->getIncludeHeader()
        );
    }

    /**
     * @test
     */
    public function getUseBinarySupportInitiallyReturnsFalse()
    {
        self::assertFalse(
            $this->subject->getUseBinarySupport()
        );
    }

    /**
     * @test
     */
    public function setUseBinarySupportSetsUseBinarySupport()
    {
        $this->subject->setUseBinarySupport(true);
        self::assertTrue(
            $this->subject->getUseBinarySupport()
        );
    }

    /**
     * @test
     */
    public function setUseBinarySupportWithStringReturnsTrue()
    {
        $this->subject->setUseBinarySupport('foo bar');
        self::assertTrue($this->subject->getUseBinarySupport());
    }

    /**
     * @test
     */
    public function setUseBinarySupportWithZeroReturnsFalse()
    {
        $this->subject->setUseBinarySupport(0);
        self::assertFalse($this->subject->getUseBinarySupport());
    }

    /**
     * @test
     */
    public function getUseCookiesInitiallyReturnsTrue()
    {
        self::assertTrue(
            $this->subject->getUseCookies()
        );
    }

    /**
     * @test
     */
    public function setUseCookiesSetsUseCookies()
    {
        $this->subject->setUseCookies(true);
        self::assertTrue(
            $this->subject->getUseCookies()
        );
    }

    /**
     * @test
     */
    public function setUseCookiesWithStringReturnsTrue()
    {
        $this->subject->setUseCookies('foo bar');
        self::assertTrue($this->subject->getUseCookies());
    }

    /**
     * @test
     */
    public function setUseCookiesWithZeroReturnsFalse()
    {
        $this->subject->setUseCookies(0);
        self::assertFalse($this->subject->getUseCookies());
    }
}
