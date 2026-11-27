<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Functional\Service;

use GuzzleHttp\Psr7\Response;
use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\Factory\WallsIoRequestFactory;
use JWeiland\WallsIoProxy\Html\CommentSanitizerBuilder;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use JWeiland\WallsIoProxy\Service\CommentSanitizer;
use JWeiland\WallsIoProxy\Service\ExternalResourceCache;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Walls Service Test
 */
class WallsServiceTest extends FunctionalTestCase
{
    private WallsService $subject;

    private Registry $registry;

    private RequestFactory&MockObject $requestFactoryMock;

    private LoggerInterface&MockObject $loggerMock;

    private ServerRequest $request;

    /**
     * Content of the responses by URL (or beginning of the URL). The walls.io API and all external resources
     * are requested with the same mocked request factory.
     *
     * @var array<string, ResponseInterface>
     */
    private array $responses = [];

    /**
     * @var list<string>
     */
    private array $requestedUris = [];

    /**
     * @var array<string, mixed>
     */
    private array $processedDataForPostsRequest = [
        'data' => [
            'uid' => '12345',
        ],
        'conf' => [
            'accessToken' => 'ABC123',
            'entriesToLoad' => 24,
            'entriesToShow' => 8,
            'requestType' => 'posts',
        ],
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/walls-io-proxy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/tt_content.csv');
        $this->setUpFrontendRootPage(1);
        // Cached images are stored in the public directory, which is shared by all tests of this class
        GeneralUtility::rmdir(Environment::getPublicPath() . '/typo3temp/assets/walls_io_proxy', true);

        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $this->registry = $this->get(Registry::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->request = new ServerRequest('https://www.example.com/');

        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->requestFactoryMock
            ->method('request')
            ->willReturnCallback(function (string $uri): ResponseInterface {
                $this->requestedUris[] = $uri;
                foreach ($this->responses as $uriBeginning => $response) {
                    if (str_starts_with($uri, $uriBeginning)) {
                        return $response;
                    }
                }

                return new Response(404);
            });

        $this->subject = new WallsService(
            $this->registry,
            new WallsIoClient($this->requestFactoryMock, $this->loggerMock),
            new WallsIoRequestFactory($this->get(Context::class)),
            new ExternalResourceCache($this->requestFactoryMock, $this->loggerMock),
            new CommentSanitizer(new CommentSanitizerBuilder()),
            $this->loggerMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->requestFactoryMock,
            $this->loggerMock,
            $this->registry,
            $this->responses,
            $this->requestedUris,
        );

        parent::tearDown();
    }

    /**
     * @param array<int|string, mixed> $posts
     */
    private function respondWithPosts(array $posts): void
    {
        $this->responses['https://api.walls.io/'] = new Response(
            200,
            [],
            json_encode(['status' => 'success', 'data' => $posts], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function getPostOfFirstWall(array $post): array
    {
        $this->respondWithPosts([$post]);

        $walls = $this->subject->getWallPosts(
            PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
            $this->request,
        );

        return $walls[(string)$post['id']];
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function dataProviderForInvalidPluginConfiguration(): array
    {
        return [
            'Missing record UID' => [[]],
            'Missing access token' => [['data' => ['uid' => 1]]],
            'Missing entries to load' => [
                [
                    'data' => ['uid' => 1],
                    'conf' => ['accessToken' => 'ABC123', 'requestType' => 'posts', 'entriesToLoad' => 0],
                ],
            ],
            'Missing entries to show' => [
                [
                    'data' => ['uid' => 1],
                    'conf' => ['accessToken' => 'ABC123', 'requestType' => 'posts', 'entriesToShow' => 0],
                ],
            ],
            'Missing request type' => [
                [
                    'data' => ['uid' => 1],
                    'conf' => ['accessToken' => 'ABC123', 'entriesToLoad' => 24, 'entriesToShow' => 8],
                ],
            ],
            'Invalid request type' => [
                [
                    'data' => ['uid' => 1],
                    'conf' => [
                        'accessToken' => 'ABC123',
                        'entriesToLoad' => 24,
                        'entriesToShow' => 8,
                        'requestType' => 'foo',
                    ],
                ],
            ],
            'Request type is a class name which must not be instantiated' => [
                [
                    'data' => ['uid' => 1],
                    'conf' => [
                        'accessToken' => 'ABC123',
                        'entriesToLoad' => 24,
                        'entriesToShow' => 8,
                        'requestType' => \stdClass::class,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $processedData
     */
    #[Test]
    #[DataProvider('dataProviderForInvalidPluginConfiguration')]
    public function getWallPostsWithInvalidPluginConfigurationWillReturnEmptyArrayAndLogsWarning(array $processedData): void
    {
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Invalid walls.io plugin configuration. No posts will be requested.',
                self::arrayHasKey('invalidFields'),
            );

        self::assertSame(
            [],
            $this->subject->getWallPosts(PluginConfiguration::fromProcessedData($processedData), $this->request),
        );
        self::assertSame([], $this->requestedUris);
    }

    #[Test]
    public function getWallPostsDoesNotLogAccessToken(): void
    {
        $this->loggerMock
            ->method('warning')
            ->with(
                self::anything(),
                self::callback(static fn(array $context): bool => !str_contains((string)json_encode($context), 'ABC123')),
            );

        $this->subject->getWallPosts(
            PluginConfiguration::fromProcessedData(
                ['data' => ['uid' => 1], 'conf' => ['accessToken' => 'ABC123', 'requestType' => 'foo']],
            ),
            $this->request,
        );
    }

    #[Test]
    public function getWallPostsWithEmptyClientResultWillReturnEmptyWalls(): void
    {
        $this->respondWithPosts([]);

        self::assertSame(
            [],
            $this->subject->getWallPosts(
                PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
                $this->request,
            ),
        );
    }

    #[Test]
    public function getWallPostsWithEmptyClientResultWillReturnCachedWallsAndLogsWarning(): void
    {
        $this->registry->set(
            'WallsIoProxy',
            'ContentRecordUid_12345',
            [
                'foo' => 'far',
            ],
        );

        $this->responses['https://api.walls.io/'] = new Response(
            200,
            [],
            json_encode(['status' => 'error', 'info' => ['Something went wrong']], JSON_THROW_ON_ERROR),
        );

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Walls.io did not return any posts. Using the posts stored in registry instead.',
                ['contentRecordUid' => 12345, 'storedPosts' => 1],
            );

        self::assertSame(
            [
                'foo' => 'far',
            ],
            $this->subject->getWallPosts(
                PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
                $this->request,
            ),
        );
    }

    #[Test]
    public function getWallPostsWithServerErrorWillReturnCachedWalls(): void
    {
        $this->registry->set('WallsIoProxy', 'ContentRecordUid_12345', ['foo' => 'far']);
        $this->responses['https://api.walls.io/'] = new Response(503);

        self::assertSame(
            ['foo' => 'far'],
            $this->subject->getWallPosts(
                PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
                $this->request,
            ),
        );
    }

    #[Test]
    public function getWallPostsWillReturnFreshWallPosts(): void
    {
        $expected = [
            '324125' => [
                'id' => '324125',
            ],
            '534213' => [
                'id' => '534213',
            ],
            '132452' => [
                'id' => '132452',
            ],
        ];

        $this->respondWithPosts(array_values($expected));

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(
                PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
                $this->request,
            ),
        );
    }

    #[Test]
    public function getWallPostsIgnoresPostsWithoutId(): void
    {
        $this->respondWithPosts([['comment' => 'No ID'], 'foo', ['id' => '1']]);

        self::assertSame(
            [1 => ['id' => '1']],
            $this->subject->getWallPosts(
                PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
                $this->request,
            ),
        );
    }

    #[Test]
    public function getWallPostsWithStablePostsIdentifierRequestsPostsEndpoint(): void
    {
        $this->respondWithPosts([]);

        $this->subject->getWallPosts(
            PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
            $this->request,
        );

        self::assertCount(1, $this->requestedUris);
        self::assertStringStartsWith('https://api.walls.io/v1/posts?', $this->requestedUris[0]);
    }

    #[Test]
    public function getWallPostsWithChangedIdentifierRequestsChangedEndpoint(): void
    {
        $this->respondWithPosts([]);
        $this->processedDataForPostsRequest['conf']['requestType'] = 'changed';

        $this->subject->getWallPosts(
            PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
            $this->request,
        );

        self::assertCount(1, $this->requestedUris);
        self::assertStringStartsWith('https://api.walls.io/v1/posts/changed?', $this->requestedUris[0]);
        self::assertStringContainsString('since=', $this->requestedUris[0]);
    }

    #[Test]
    public function getWallPostsAcceptsLegacyClassNameOfRequestType(): void
    {
        $this->respondWithPosts([['id' => '1']]);
        $this->processedDataForPostsRequest['conf']['requestType'] = PostsRequest::class;

        self::assertSame(
            [1 => ['id' => '1']],
            $this->subject->getWallPosts(
                PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest),
                $this->request,
            ),
        );
        self::assertStringStartsWith('https://api.walls.io/v1/posts?', $this->requestedUris[0]);
    }

    #[Test]
    public function getWallPostsWillConvertTimestampHumanReadable(): void
    {
        $date = new \DateTime('now');
        $date->modify('-2 hours');

        self::assertSame(
            '2 hours ago',
            $this->getPostOfFirstWall(['id' => '324125', 'created_timestamp' => $date->format('U')])['created_timestamp_as_text'],
        );
    }

    #[Test]
    public function getWallPostsWillConvertNewLinesToBR(): void
    {
        self::assertSame(
            'Line1<br />' . chr(10) . 'Line 2',
            $this->getPostOfFirstWall(['id' => '324125', 'comment' => 'Line1' . chr(10) . 'Line 2'])['html_comment'],
        );
    }

    #[Test]
    public function getWallPostsKeepsOriginalCommentAndSanitizesHtmlComment(): void
    {
        $comment = 'Hello <script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">Link</a>';
        $post = $this->getPostOfFirstWall(['id' => '1', 'comment' => $comment]);

        self::assertSame($comment, $post['comment']);
        self::assertStringNotContainsString('<script', $post['html_comment']);
        self::assertStringNotContainsString('onerror', $post['html_comment']);
        self::assertStringNotContainsString('javascript:', $post['html_comment']);
        self::assertStringContainsString('Hello', $post['html_comment']);
    }

    #[Test]
    public function getWallPostsReplacesExternalImagesInCommentByCachedCopy(): void
    {
        $this->responses['https://cdn.example.com/'] = new Response(
            200,
            [],
            (string)file_get_contents(__DIR__ . '/../Fixtures/Images/pixel.png'),
        );

        $post = $this->getPostOfFirstWall(
            ['id' => '1', 'comment' => 'Look <img src="https://cdn.example.com/a.jpg" onerror="alert(1)" width="1"> here'],
        );

        self::assertMatchesRegularExpression(
            '#^Look <img src="[^"]*typo3temp/assets/walls_io_proxy/12345/[0-9a-f]{64}\.png" alt(="")?> here$#',
            $post['html_comment'],
        );
        self::assertStringNotContainsString('cdn.example.com', $post['html_comment']);
    }

    #[Test]
    public function getWallPostsRemovesImagesInCommentWhichCanNotBeCached(): void
    {
        $this->responses['https://cdn.example.com/'] = new Response(200, [], '<?php echo 1;');

        $post = $this->getPostOfFirstWall(
            [
                'id' => '1',
                'comment' => 'A<img src="https://cdn.example.com/a.jpg">B<img src="javascript:alert(1)">C<img onerror="alert(1)">D',
            ],
        );

        self::assertSame('ABCD', $post['html_comment']);
    }

    #[Test]
    public function getWallPostsCachesImagesOfPost(): void
    {
        $this->responses['https://cdn.example.com/'] = new Response(
            200,
            [],
            (string)file_get_contents(__DIR__ . '/../Fixtures/Images/pixel.jpg'),
        );

        $post = $this->getPostOfFirstWall(
            [
                'id' => '1',
                'post_image' => 'https://cdn.example.com/post.php',
                'external_image' => 'https://cdn.example.com/user',
            ],
        );

        self::assertMatchesRegularExpression('#typo3temp/assets/walls_io_proxy/12345/[0-9a-f]{64}\.jpg$#', $post['post_image']);
        self::assertMatchesRegularExpression('#typo3temp/assets/walls_io_proxy/12345/[0-9a-f]{64}\.jpg$#', $post['external_image']);
    }

    #[Test]
    public function getWallPostsNeverFallsBackToExternalImageUrls(): void
    {
        $this->responses['https://cdn.example.com/'] = new Response(200, [], 'not an image');

        $post = $this->getPostOfFirstWall(
            [
                'id' => '1',
                'post_image' => 'https://cdn.example.com/post.jpg',
                'external_image' => 'javascript:alert(1)',
            ],
        );

        self::assertSame('', $post['post_image']);
        self::assertSame('', $post['external_image']);
    }

    #[Test]
    public function getWallPostsRemovesLinksWhichAreNotHttpOrHttps(): void
    {
        $post = $this->getPostOfFirstWall(
            [
                'id' => '1',
                'post_link' => 'javascript:alert(1)',
                'permalink' => 'https://www.example.com/post/1',
                'userlink' => 'data:text/html;base64,PHNjcmlwdD4=',
                'post_video' => 'HTTP://www.example.com/video.mp4',
            ],
        );

        self::assertSame('', $post['post_link']);
        self::assertSame('https://www.example.com/post/1', $post['permalink']);
        self::assertSame('', $post['userlink']);
        self::assertSame('HTTP://www.example.com/video.mp4', $post['post_video']);
    }

    #[Test]
    public function getWallPostsWillStoreWallPostsAndPageCacheExpireTimeInRegistry(): void
    {
        $wallPosts = ['324125' => ['id' => '324125']];
        $this->respondWithPosts(array_values($wallPosts));

        $cacheDataCollector = new CacheDataCollector();
        $cacheDataCollector->restrictMaximumLifetime(3600);
        $request = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('frontend.cache.collector', $cacheDataCollector);

        $this->subject->getWallPosts(PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest), $request);

        self::assertSame($wallPosts, $this->registry->get('WallsIoProxy', 'ContentRecordUid_12345'));
        self::assertSame(
            $GLOBALS['EXEC_TIME'] + 3600,
            $this->registry->get('WallsIoProxy', 'PageCacheExpireTime_12345'),
        );
    }

    #[Test]
    public function getWallPostsWithYoastRequestWillNotStoreWallPostsInRegistry(): void
    {
        $this->respondWithPosts([['id' => '324125']]);

        $request = (new ServerRequest('https://www.example.com/'))
            ->withHeader('X-Yoast-Page-Request', '1')
            ->withAttribute('frontend.cache.collector', new CacheDataCollector());

        $this->subject->getWallPosts(PluginConfiguration::fromProcessedData($this->processedDataForPostsRequest), $request);

        self::assertNull($this->registry->get('WallsIoProxy', 'ContentRecordUid_12345'));
    }

    #[Test]
    public function clearCacheRemovesRegistryEntriesAndCachedFiles(): void
    {
        $this->registry->set('WallsIoProxy', 'ContentRecordUid_12345', ['foo' => 'far']);
        $this->registry->set('WallsIoProxy', 'PageCacheExpireTime_12345', 1234567890);
        $this->registry->set('WallsIoProxy', 'ContentRecordUid_999', ['keep' => 'me']);
        $this->responses['https://cdn.example.com/'] = new Response(
            200,
            [],
            (string)file_get_contents(__DIR__ . '/../Fixtures/Images/pixel.png'),
        );
        $this->getPostOfFirstWall(['id' => '1', 'post_image' => 'https://cdn.example.com/post.png']);
        $cacheDirectory = Environment::getPublicPath() . '/typo3temp/assets/walls_io_proxy/12345';
        self::assertDirectoryExists($cacheDirectory);

        self::assertTrue($this->subject->clearCache(12345));

        self::assertNull($this->registry->get('WallsIoProxy', 'PageCacheExpireTime_12345'));
        self::assertNull($this->registry->get('WallsIoProxy', 'ContentRecordUid_12345'));
        self::assertSame(['keep' => 'me'], $this->registry->get('WallsIoProxy', 'ContentRecordUid_999'));
        self::assertDirectoryDoesNotExist($cacheDirectory);
    }

    #[Test]
    public function clearCacheWithInvalidUidReturnsFalse(): void
    {
        self::assertFalse($this->subject->clearCache(0));
        self::assertFalse($this->subject->clearCache(-1));
    }
}
