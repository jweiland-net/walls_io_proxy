<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Hook;

use JWeiland\WallsIoProxy\Hook\DataHandler;
use JWeiland\WallsIoProxy\Service\WallsService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test DataHandler
 */
class DataHandlerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var DataHandler
     */
    protected $subject;

    /**
     * @var WallsService|ObjectProphecy
     */
    protected $wallsServiceProphecy;

    protected function setUp(): void
    {
        $this->wallsServiceProphecy = $this->prophesize(WallsService::class);

        $this->subject = new DataHandler(
            $this->wallsServiceProphecy->reveal()
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->wallsServiceProphecy,
            $_GET['contentRecordUid']
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function clearCachePostProcWithEmptyParamsDoesNothing(): void
    {
        $this->wallsServiceProphecy
            ->clearCache(Argument::any())
            ->shouldNotBeCalled();

        $this->subject->clearCachePostProc([]);
    }

    /**
     * @test
     */
    public function clearCachePostProcWithInvalidCacheCmdDoesNothing(): void
    {
        $this->wallsServiceProphecy
            ->clearCache(Argument::any())
            ->shouldNotBeCalled();

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wrong'
        ]);
    }

    /**
     * @test
     */
    public function clearCachePostProcWithEmptyContentUidDoesNothing(): void
    {
        $_GET['contentRecordUid'] = 0;

        $this->wallsServiceProphecy
            ->clearCache(Argument::any())
            ->shouldNotBeCalled();

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wallioproxy'
        ]);
    }

    /**
     * @test
     */
    public function clearCachePostProcWithValidCacheCmdAndContentRecordUidWillClearCache(): void
    {
        $_GET['contentRecordUid'] = 12;

        $this->wallsServiceProphecy
            ->clearCache(Argument::exact(12))
            ->shouldBeCalled();

        $this->subject->clearCachePostProc([
            'cacheCmd' => 'wallioproxy'
        ]);
    }
}
