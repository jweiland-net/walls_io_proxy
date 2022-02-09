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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Walls.io Data Processing Test
 */
class AddWallsProcessorTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var AddWallsProcessor
     */
    protected $subject;

    /**
     * @var WallsService|ObjectProphecy
     */
    protected $wallsServiceProphecy;

    /**
     * @var FlexFormService
     */
    protected $flexFormService;

    /**
     * @var ContentObjectRenderer|ObjectProphecy
     */
    protected $contentObjectRendererProphecy;

    /**
     * Because of using EXT: we have to load our extension before testing
     *
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/walls_io_proxy'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->wallsServiceProphecy = $this->prophesize(WallsService::class);
        $this->flexFormService = new FlexFormService();
        $this->contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);

        $this->subject = new AddWallsProcessor(
            $this->wallsServiceProphecy->reveal(),
            $this->flexFormService
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->wallsServiceProphecy,
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
                'equals' => '1'
            ]
        ];

        $this->contentObjectRendererProphecy
            ->checkIf($processorConfiguration['if.'])
            ->shouldBeCalled()
            ->willReturn(false);

        $this->wallsServiceProphecy
            ->getWallPosts(Argument::any())
            ->shouldNotBeCalled();

        self::assertSame(
            $processedData,
            $this->subject->process(
                $this->contentObjectRendererProphecy->reveal(),
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

        $this->wallsServiceProphecy
            ->getWallPosts(Argument::any())
            ->shouldBeCalled()
            ->willReturn([]);

        self::assertSame(
            [
                'conf' => [],
                'walls' => []
            ],
            $this->subject->process(
                $this->contentObjectRendererProphecy->reveal(),
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
                ))
            ]
        ];

        $expectedProcessedData = $processedData;
        $expectedProcessedData['conf'] = [
            'accessToken' => 'ABC123',
            'entriesToLoad' => '24',
            'entriesToShow' => '8',
            'showWallsSince' => '365',
        ];
        $expectedProcessedData['walls'] = [];

        $this->wallsServiceProphecy
            ->getWallPosts(Argument::any())
            ->shouldBeCalled()
            ->willReturn([]);

        self::assertSame(
            $expectedProcessedData,
            $this->subject->process(
                $this->contentObjectRendererProphecy->reveal(),
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
                'uid' => 1
            ]
        ];

        $this->wallsServiceProphecy
            ->getWallPosts(Argument::that(static function(PluginConfiguration $pluginConfiguration) {
                return $pluginConfiguration->getRecordUid() === 1;
            }))
            ->shouldBeCalled()
            ->willReturn([]);

        self::assertSame(
            [
                'data' => [
                    'uid' => 1
                ],
                'conf' => [],
                'walls' => []
            ],
            $this->subject->process(
                $this->contentObjectRendererProphecy->reveal(),
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
                'pi_flexform' => ''
            ]
        ];

        $walls = [
            0 => 'Test',
            1 => [
                'key' => 'value'
            ]
        ];

        $this->wallsServiceProphecy
            ->getWallPosts(Argument::any())
            ->shouldBeCalled()
            ->willReturn($walls);

        self::assertSame(
            [
                'data' => [
                    'uid' => 1,
                    'pi_flexform' => '',
                ],
                'conf' => [],
                'walls' => $walls
            ],
            $this->subject->process(
                $this->contentObjectRendererProphecy->reveal(),
                [],
                [],
                $processedData
            )
        );
    }
}
