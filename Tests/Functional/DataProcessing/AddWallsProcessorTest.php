<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Functional\DataProcessing;

use GuzzleHttp\Psr7\Response;
use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\DataProcessing\AddWallsProcessor;
use JWeiland\WallsIoProxy\Factory\WallsIoRequestFactory;
use JWeiland\WallsIoProxy\Html\CommentSanitizerBuilder;
use JWeiland\WallsIoProxy\Service\CommentSanitizer;
use JWeiland\WallsIoProxy\Service\ExternalResourceCache;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Walls.io Data Processing Test
 */
class AddWallsProcessorTest extends FunctionalTestCase
{
    private AddWallsProcessor $subject;

    private RequestFactory&MockObject $requestFactoryMock;

    private ContentObjectRenderer&MockObject $contentObjectRendererMock;

    /**
     * @var list<string>
     */
    private array $requestedUris = [];

    /**
     * @var array<string, mixed>
     */
    private array $wallsIoResponse = ['status' => 'success', 'data' => []];

    protected array $testExtensionsToLoad = [
        'jweiland/walls-io-proxy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->requestFactoryMock
            ->method('request')
            ->willReturnCallback(function (string $uri): Response {
                $this->requestedUris[] = $uri;

                return new Response(200, [], json_encode($this->wallsIoResponse, JSON_THROW_ON_ERROR));
            });

        $this->contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);
        $this->contentObjectRendererMock
            ->method('getRequest')
            ->willReturn(new ServerRequest('https://www.example.com/'));

        $logger = new NullLogger();
        $this->subject = new AddWallsProcessor(
            new WallsService(
                $this->get(Registry::class),
                new WallsIoClient($this->requestFactoryMock, $logger),
                new WallsIoRequestFactory($this->get(Context::class)),
                new ExternalResourceCache($this->requestFactoryMock, $logger),
                new CommentSanitizer(new CommentSanitizerBuilder()),
                $logger,
            ),
            $this->get(FlexFormTools::class),
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->requestFactoryMock,
            $this->contentObjectRendererMock,
            $this->requestedUris,
        );

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function getProcessedDataWithValidFlexForm(): array
    {
        return [
            'data' => [
                'uid' => 1,
                'pi_flexform' => file_get_contents(GeneralUtility::getFileAbsFileName(
                    'EXT:walls_io_proxy/Tests/Functional/Fixtures/FlexForm.xml',
                )),
            ],
        ];
    }

    #[Test]
    public function processWithFailedTypoScriptConditionWillNotModifyProcessedData(): void
    {
        $processedData = [];
        $processorConfiguration = [
            'if.' => [
                'value' => '1',
                'equals' => '2',
            ],
        ];

        $this->contentObjectRendererMock
            ->expects($this->atLeastOnce())
            ->method('checkIf')
            ->with($processorConfiguration['if.']);

        self::assertSame(
            $processedData,
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                $processorConfiguration,
                $processedData,
            ),
        );
        self::assertSame([], $this->requestedUris);
    }

    #[Test]
    public function processWithNonPiFlexformWillAddEmptyConfVariableAndNoWalls(): void
    {
        self::assertSame(
            [
                'conf' => [],
                'walls' => [],
            ],
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                [],
                [],
            ),
        );
        self::assertSame([], $this->requestedUris);
    }

    #[Test]
    public function processWithValidPiFlexformWillAddConfVariable(): void
    {
        $processedData = $this->getProcessedDataWithValidFlexForm();

        $expectedProcessedData = $processedData;
        $expectedProcessedData['conf'] = [
            'accessToken' => 'ABC123',
            'requestType' => 'posts',
            'entriesToLoad' => '24',
            'entriesToShow' => '8',
            'showWallsSince' => '365',
        ];
        $expectedProcessedData['walls'] = [];

        self::assertSame(
            $expectedProcessedData,
            $this->subject->process(
                $this->contentObjectRendererMock,
                [],
                [],
                $processedData,
            ),
        );
    }

    #[Test]
    public function processRequestsWallsWithPluginConfigurationOfFlexForm(): void
    {
        $this->subject->process(
            $this->contentObjectRendererMock,
            [],
            [],
            $this->getProcessedDataWithValidFlexForm(),
        );

        self::assertCount(1, $this->requestedUris);
        self::assertStringStartsWith('https://api.walls.io/v1/posts?', $this->requestedUris[0]);
        self::assertStringContainsString('access_token=ABC123', $this->requestedUris[0]);
        self::assertStringContainsString('limit=24', $this->requestedUris[0]);
    }

    #[Test]
    public function processWithRecordWithoutConfigurationWillNotRequestWalls(): void
    {
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
                ['data' => ['uid' => 1]],
            ),
        );
        self::assertSame([], $this->requestedUris);
    }

    #[Test]
    public function processAddsWallsToProcessedData(): void
    {
        $this->wallsIoResponse['data'] = [
            ['id' => '1', 'type' => 'facebook'],
            ['id' => '2', 'type' => 'twitter'],
        ];

        $processedData = $this->subject->process(
            $this->contentObjectRendererMock,
            [],
            [],
            $this->getProcessedDataWithValidFlexForm(),
        );

        self::assertSame(
            [
                1 => ['id' => '1', 'type' => 'facebook'],
                2 => ['id' => '2', 'type' => 'twitter'],
            ],
            $processedData['walls'],
        );
    }
}
