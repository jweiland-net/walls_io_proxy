<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Functional\Service;

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Client\WallsIoRequest;
use JWeiland\WallsIoProxy\Client\WallsIoResponse;
use JWeiland\WallsIoProxy\Service\WallsService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Walls IO Service Test
 */
class WallsServiceTest extends FunctionalTestCase
{
    /**
     * @var WallsService
     */
    protected $subject;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WallsIoClient|ObjectProphecy
     */
    protected $client;

    protected function setUp()
    {
        parent::setUp();

        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->registry->removeAllByNamespace('WallsIoProxy');

        $this->client = $this->prophesize(WallsIoClient::class);

        $this->subject = new WallsService(
            $this->registry,
            $this->client->reveal()
        );
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
    public function getWallsWithBrokenRequestWillGetWallsFromRegistry()
    {
        $this->registry->set(
            'WallsIoProxy',
            'WallId_12345',
            '1:2{"walls":2}'
        );

        $wallsIoRequestForSession = new WallsIoRequest();
        $wallsIoRequestForSession->setWallId(12345);
        $wallsIoRequestForSession->setEntriesToLoad(8);
        $wallsIoRequestForSession->setIncludeHeader(1);

        $wallsIoResponseForSession = new WallsIoResponse();
        $wallsIoResponseForSession->setBody('1:0{"sid":""}');

        $this->client
            ->processRequest($wallsIoRequestForSession)
            ->shouldBeCalled()
            ->willReturn($wallsIoResponseForSession);
        $this->client
            ->hasError()
            ->shouldBeCalled()
            ->willReturn(true);
        $this->client
            ->getError()
            ->shouldBeCalled()
            ->willReturn([
                'message' => 'Session ID empty'
            ]);

        self::assertSame(
            [
                'walls' => 2
            ],
            $this->subject->getWalls(12345, 8)
        );
    }

    /**
     * @test
     */
    public function getWallsWillGetFreshWalls()
    {
        $wallsIoRequestForSession = new WallsIoRequest();
        $wallsIoRequestForSession->setWallId(12345);
        $wallsIoRequestForSession->setEntriesToLoad(8);
        $wallsIoRequestForSession->setIncludeHeader(1);

        $wallsIoResponseForSession = new WallsIoResponse();
        $wallsIoResponseForSession->setBody('1:0{"sid":"myOwnSession"}');

        $this->client
            ->processRequest($wallsIoRequestForSession)
            ->shouldBeCalled()
            ->willReturn($wallsIoResponseForSession);
        $this->client
            ->hasError()
            ->shouldBeCalled()
            ->willReturn(false);

        $wallsIoRequestForEntries = new WallsIoRequest();
        $wallsIoRequestForEntries->setWallId(12345);
        $wallsIoRequestForEntries->setSessionId('myOwnSession');
        $wallsIoRequestForEntries->setEntriesToLoad(8);
        $wallsIoRequestForEntries->setIncludeHeader(0);

        $wallsIoResponseForEntries = new WallsIoResponse();
        $wallsIoResponseForEntries->setBody('1:2{"walls":25}');

        $this->client
            ->processRequest($wallsIoRequestForEntries)
            ->shouldBeCalled()
            ->willReturn($wallsIoResponseForEntries);

        self::assertSame(
            [
                'walls' => 25
            ],
            $this->subject->getWalls(12345, 8)
        );

        self::assertSame(
            '1:2{"walls":25}',
            $this->registry->get('WallsIoProxy', 'WallId_12345')
        );
    }
}
