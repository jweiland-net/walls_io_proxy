<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Hook;

use JWeiland\WallsIoProxy\Hook\DataHandlerHook;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test DataHandler
 */
class DataHandlerHookTest extends UnitTestCase
{
    protected DataHandlerHook $subject;

    protected WallsService|MockObject $wallsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wallsServiceMock = $this->createMock(WallsService::class);

        $this->subject = new DataHandlerHook(
            $this->wallsServiceMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->wallsServiceMock,
            $GLOBALS['TYPO3_REQUEST'],
        );

        parent::tearDown();
    }

    #[Test]
    public function clearCachePostProcWithEmptyParamsDoesNothing(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        // Set the expectation that clearCache should not be called
        $this->wallsServiceMock
            ->expects(self::never())
            ->method('clearCache');

        $this->subject->clearCachePostProc([]);
    }

    #[Test]
    public function clearCachePostProcWithInvalidCacheCmdDoesNothing(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        // Set the expectation that clearCache should not be called
        $this->wallsServiceMock
            ->expects(self::never())
            ->method('clearCache');

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wrong',
        ]);
    }

    #[Test]
    public function clearCachePostProcWithEmptyContentUidDoesNothing(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams([
                'contentRecordUid' => 0,
            ]);

        // Set the expectation that clearCache should not be called
        $this->wallsServiceMock
            ->expects(self::never())
            ->method('clearCache');

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wallioproxy',
        ]);
    }

    #[Test]
    public function clearCachePostProcWithValidCacheCmdAndContentRecordUidWillClearCache(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams([
                'contentRecordUid' => 12,
            ]);

        $this->wallsServiceMock
            ->expects(self::once())
            ->method('clearCache')
            ->with(self::equalTo(12));

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wallioproxy',
        ]);
    }
}
