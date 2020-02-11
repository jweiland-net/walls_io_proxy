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
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getHeader());
    }

    /**
     * @test
     */
    public function setHeaderWithBooleanResultsInString()
    {
        $this->subject->setHeader(true);
        $this->assertSame('1', $this->subject->getHeader());
    }

    /**
     * @test
     */
    public function getBodyInitiallyReturnsEmptyString()
    {
        $this->assertSame(
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

        $this->assertSame(
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
        $this->assertSame('123', $this->subject->getBody());
    }

    /**
     * @test
     */
    public function setBodyWithBooleanResultsInString()
    {
        $this->subject->setBody(true);
        $this->assertSame('1', $this->subject->getBody());
    }
}
