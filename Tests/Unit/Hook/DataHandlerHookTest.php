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
use JWeiland\WallsIoProxy\Hook\DataHandlerHook;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test DataHandler
 */
class DataHandlerHookTest extends UnitTestCase
{
    protected DataHandlerHook $subject;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var WallsService|MockObject
     */
    protected $wallsServiceMock;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $requestMock;

    protected ServerRequestInterface $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new ServerRequest('https://www.example.com', 'GET');
        $this->request = $this->request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $this->registryMock = $this->createMock(Registry::class);
        $this->clientMock = $this->createMock(WallsIoClient::class);
        $this->requestMock = $this->createMock(ServerRequest::class);
        $typoScriptFrontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $typoScriptFrontendControllerMock
            ->expects(self::never())
            ->method('addCacheTags');

        $_SERVER['TYPO3_REQUEST'] = $this->request->withAttribute(
            'frontend.controller',
            $typoScriptFrontendControllerMock,
        );
        $_SERVER['REQUEST_URI'] = 'https://www.example.com';
        $this->wallsServiceMock = $this->getMockBuilder(WallsService::class)
            ->setConstructorArgs([$this->registryMock, $this->clientMock, $this->requestMock])
            ->getMock();
        $this->subject = new DataHandlerHook(
            $this->wallsServiceMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->wallsServiceMock,
            $_GET['contentRecordUid'],
            $this->request,
            $GLOBALS['TYPO3_REQUEST'],
        );

        parent::tearDown();
    }

    #[Test]
    public function clearCachePostProcWithEmptyParamsDoesNothing(): void
    {
        // Set expectation that clearCache should not be called
        $this->wallsServiceMock
            ->expects(self::never())
            ->method('clearCache');

        $this->subject->clearCachePostProc([]);
    }

    #[Test]
    public function clearCachePostProcWithInvalidCacheCmdDoesNothing(): void
    {
        // Set expectation that clearCache should not be called
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
        $_GET['contentRecordUid'] = 0;

        // Set expectation that clearCache should not be called
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
        $_GET['contentRecordUid'] = 12;

        $this->wallsServiceMock
            ->expects(self::once())
            ->method('clearCache')
            ->with(self::equalTo(12));

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wallioproxy',
        ]);
    }
}
