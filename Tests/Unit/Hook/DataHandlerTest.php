<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Hook;

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Hook\DataHandler;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test DataHandler
 */
class DataHandlerTest extends UnitTestCase
{
    protected DataHandler $subject;

    protected Registry|MockObject $registryMock;

    protected WallsService|MockObject $wallsServiceMock;

    protected function setUp(): void
    {
        $this->registryMock = $this->createMock(Registry::class);
        $this->clientMock = $this->createMock(WallsIoClient::class);
        $this->wallsServiceMock = $this->getMockBuilder(WallsService::class)
            ->setConstructorArgs([$this->registryMock, $this->clientMock])
            ->getMock();
        $this->subject = new DataHandler(
            $this->wallsServiceMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->wallsServiceMock,
            $_GET['contentRecordUid']
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function clearCachePostProcWithEmptyParamsDoesNothing(): void
    {
        // Set expectation that clearCache should not be called
        $this->wallsServiceMock
            ->expects($this->never())
            ->method('clearCache');

        $this->subject->clearCachePostProc([]);
    }

    /**
     * @test
     */
    public function clearCachePostProcWithInvalidCacheCmdDoesNothing(): void
    {
        // Set expectation that clearCache should not be called
        $this->wallsServiceMock
            ->expects($this->never())
            ->method('clearCache');

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wrong',
        ]);
    }

    /**
     * @test
     */
    public function clearCachePostProcWithEmptyContentUidDoesNothing(): void
    {
        $_GET['contentRecordUid'] = 0;

        // Set expectation that clearCache should not be called
        $this->wallsServiceMock
            ->expects($this->never())
            ->method('clearCache');

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wallioproxy',
        ]);
    }

    /**
     * @test
     */
    public function clearCachePostProcWithValidCacheCmdAndContentRecordUidWillClearCache(): void
    {
        $_GET['contentRecordUid'] = 12;

        $this->wallsServiceMock
            ->expects($this->once())
            ->method('clearCache')
            ->with($this->equalTo(12));

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wallioproxy',
        ]);
    }
}
