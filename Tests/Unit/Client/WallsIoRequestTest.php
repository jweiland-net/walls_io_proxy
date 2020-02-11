<?php
namespace JWeiland\WallsIoProxy\Tests\Unit\Client;

/*
 * This file is part of the walls_io_proxy project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
            1,
            $this->subject->getWallId()
        );
    }

    /**
     * @test
     */
    public function getSessionIdInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getSessionId());
    }

    /**
     * @test
     */
    public function setSessionIdWithBooleanResultsInString()
    {
        $this->subject->setSessionId(true);
        $this->assertSame('1', $this->subject->getSessionId());
    }

    /**
     * @test
     */
    public function getEntriesToLoadInitiallyReturnsZero()
    {
        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
            1,
            $this->subject->getEntriesToLoad()
        );
    }

    /**
     * @test
     */
    public function getIncludeHeaderInitiallyReturnsZero()
    {
        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
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

        $this->assertSame(
            1,
            $this->subject->getIncludeHeader()
        );
    }

    /**
     * @test
     */
    public function getUseBinarySupportInitiallyReturnsFalse()
    {
        $this->assertFalse(
            $this->subject->getUseBinarySupport()
        );
    }

    /**
     * @test
     */
    public function setUseBinarySupportSetsUseBinarySupport()
    {
        $this->subject->setUseBinarySupport(true);
        $this->assertTrue(
            $this->subject->getUseBinarySupport()
        );
    }

    /**
     * @test
     */
    public function setUseBinarySupportWithStringReturnsTrue()
    {
        $this->subject->setUseBinarySupport('foo bar');
        $this->assertTrue($this->subject->getUseBinarySupport());
    }

    /**
     * @test
     */
    public function setUseBinarySupportWithZeroReturnsFalse()
    {
        $this->subject->setUseBinarySupport(0);
        $this->assertFalse($this->subject->getUseBinarySupport());
    }

    /**
     * @test
     */
    public function getUseCookiesInitiallyReturnsTrue()
    {
        $this->assertTrue(
            $this->subject->getUseCookies()
        );
    }

    /**
     * @test
     */
    public function setUseCookiesSetsUseCookies()
    {
        $this->subject->setUseCookies(true);
        $this->assertTrue(
            $this->subject->getUseCookies()
        );
    }

    /**
     * @test
     */
    public function setUseCookiesWithStringReturnsTrue()
    {
        $this->subject->setUseCookies('foo bar');
        $this->assertTrue($this->subject->getUseCookies());
    }

    /**
     * @test
     */
    public function setUseCookiesWithZeroReturnsFalse()
    {
        $this->subject->setUseCookies(0);
        $this->assertFalse($this->subject->getUseCookies());
    }
}
