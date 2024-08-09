<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\DataProcessing;

use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\DataProcessing\AddWallsProcessor;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Walls.io Data Processing Test
 */
class AddWallsProcessorTest extends FunctionalTestCase
{
    protected AddWallsProcessor $subject;

    protected WallsService|MockObject $wallsServiceMock;

    protected FlexFormService $flexFormService;

    protected ContentObjectRenderer|MockObject $contentObjectRendererMock;

    protected array $testExtensionsToLoad = [
        'jweiland/walls_io_proxy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->wallsServiceMock = $this->createMock(WallsService::class);
        $this->flexFormService = new FlexFormService();
        $this->contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);

        $this->subject = new AddWallsProcessor(
            $this->wallsServiceMock,
            $this->flexFormService
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->wallsServiceMock,
            $this->flexFormService
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function processWithValidTypoScriptConditionWillNotModifyProcessedData(): void
    {
        $processedData = [];
        $processorConfiguration = [
            'if.' => [
                'value' => '1',
                'equals' => '1',
            ],
        ];

        $this->contentObjectRendererMock
            ->method('checkIf')
            ->with($processorConfiguration['if.'])
            ->willReturn(false);

        $this->wallsServiceMock
            ->method('getWallPosts')
            ->with($this->any());

        self::assertSame(
            $processedData,
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                $processorConfiguration,
                $processedData
            )
        );
    }

    /**
     * @test
     */
    public function processWithNonPiFlexformWillAddEmptyConfVariable(): void
    {
        $processedData = [];

        $this->wallsServiceMock
            ->method('getWallPosts')
            ->with($this->any())
            ->willReturn([]);

        self::assertSame(
            [
                'conf' => [],
                'walls' => [],
            ],
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                [],
                $processedData
            )
        );
    }

    /**
     * @test
     */
    public function processWithValidPiFlexformWillAddConfVariable(): void
    {
        $processedData = [
            'data' => [
                'pi_flexform' => file_get_contents(GeneralUtility::getFileAbsFileName(
                    'EXT:walls_io_proxy/Tests/Functional/Fixtures/FlexForm.xml'
                )),
            ],
        ];

        $expectedProcessedData = $processedData;
        $expectedProcessedData['conf'] = [
            'accessToken' => 'ABC123',
            'entriesToLoad' => '24',
            'entriesToShow' => '8',
            'showWallsSince' => '365',
        ];
        $expectedProcessedData['walls'] = [];

        $this->wallsServiceMock
            ->method('getWallPosts')
            ->with($this->any())
            ->willReturn([]);

        self::assertSame(
            $expectedProcessedData,
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                [],
                $processedData
            )
        );
    }

    /**
     * @test
     */
    public function processWillCallGetWallPostsWithPluginConfiguration(): void
    {
        $processedData = [
            'data' => [
                'uid' => 1,
            ],
        ];

        $this->wallsServiceMock
            ->method('getWallPosts')
            ->with(
                $this->callback(function (PluginConfiguration $pluginConfiguration) {
                    return $pluginConfiguration->getRecordUid() === 1;
                })
            )
            ->willReturn([]);

        self::assertSame(
            [
                'data' => [
                    'uid' => 1,
                ],
                'conf' => [],
                'walls' => [],
            ],
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                [],
                $processedData
            )
        );
    }

    /**
     * @test
     */
    public function processAddsWallsToProcessedData(): void
    {
        $processedData = [
            'data' => [
                'uid' => 1,
                'pi_flexform' => '',
            ],
        ];

        $walls = [
            0 => 'Test',
            1 => [
                'key' => 'value',
            ],
        ];

        $this->wallsServiceMock
            ->method('getWallPosts')
            ->with($this->any())
            ->willReturn($walls);

        self::assertSame(
            [
                'data' => [
                    'uid' => 1,
                    'pi_flexform' => '',
                ],
                'conf' => [],
                'walls' => $walls,
            ],
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                [],
                $processedData
            )
        );
    }
}
