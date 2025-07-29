<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Functional\Service;

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Walls Service Test
 */
class WallsServiceTest extends FunctionalTestCase
{
    protected WallsService $subject;

    protected Registry $registry;

    protected WallsIoClient|MockObject $wallsIoClientMock;

    protected RequestFactory|MockObject $requestFactoryMock;

    protected ServerRequest|MockObject $requestMock;

    protected array $processedDataForPostsRequest = [
        'data' => [
            'uid' => '12345',
        ],
        'conf' => [
            'accessToken' => 'ABC123',
            'entriesToLoad' => 24,
            'entriesToShow' => 8,
            'requestType' => PostsRequest::class,
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

        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $this->registry = new Registry();
        $this->wallsIoClientMock = $this->createMock(WallsIoClient::class);
        $this->requestFactoryMock = $this->createMock(RequestFactory::class);

        $this->requestMock = $this->createMock(ServerRequest::class);

        $this->subject = new WallsService(
            $this->registry,
            $this->wallsIoClientMock,
            $this->requestFactoryMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->requestFactoryMock,
            $this->wallsIoClientMock,
            $this->registry,
        );

        parent::tearDown();
    }

    public static function dataProviderForInvalidPluginConfiguration(): array
    {
        return [
            'Missing record UID' => [[]],
            'Missing access token' => [['data' => ['uid' => 1]]],
            'Missing entries to load' => [['data' => ['uid' => 1], 'conf' => ['accessToken' => 'ABC123']]],
            'Missing entries to show' => [
                [
                    'data' => ['uid' => 1],
                    'conf' => ['accessToken' => 'ABC123', 'entriesToLoad' => 24],
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
        ];
    }

    #[Test]
    #[DataProvider('dataProviderForInvalidPluginConfiguration')]
    public function getWallPostsWithInvalidPluginConfigurationWillReturnEmptyArray(array $processedData): void
    {
        self::assertSame(
            [],
            $this->subject->getWallPosts(
                new PluginConfiguration($processedData),
                $this->requestMock
            )
        );
    }

    #[Test]
    public function getWallPostsWithEmptyClientResultWillReturnEmptyWalls(): void
    {
        $this->wallsIoClientMock
            ->expects(self::once())
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => [],
                ]
            );

        self::assertSame(
            [],
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest),
                $this->requestMock
            )
        );
    }

    #[Test]
    public function getWallPostsWithEmptyClientResultWillReturnCachedWalls(): void
    {
        $this->registry->set(
            'WallsIoProxy',
            'ContentRecordUid_12345',
            [
                'foo' => 'far',
            ]
        );

        $this->wallsIoClientMock
            ->expects(self::once())
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(['status' => 'error']);

        self::assertSame(
            [
                'foo' => 'far',
            ],
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest),
                $this->requestMock
            )
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

        $this->wallsIoClientMock
            ->expects(self::once())
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $expected,
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest),
                $this->requestMock
            )
        );
    }

    #[Test]
    public function getWallPostsWillConvertTimestampHumanReadable(): void
    {
        $date = new \DateTime('now');
        $date->modify('-2 hours');

        $data = [
            0 => [
                'id' => '324125',
                'created_timestamp' => $date->format('U'),
            ],
        ];

        $expected = [
            '324125' => [
                'id' => '324125',
                'created_timestamp' => $date->format('U'),
                'created_timestamp_as_text' => '2 hours ago',
            ],
        ];

        $this->wallsIoClientMock
            ->expects(self::once())
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data,
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest),
                $this->requestMock
            )
        );
    }

    #[Test]
    public function getWallPostsWillConvertNewLinesToBR(): void
    {
        $data = [
            0 => [
                'id' => '324125',
                'comment' => 'Line1' . chr(10) . 'Line 2',
            ],
        ];

        $expected = [
            '324125' => [
                'id' => '324125',
                'comment' => 'Line1' . chr(10) . 'Line 2',
                'html_comment' => 'Line1<br />' . chr(10) . 'Line 2',
            ],
        ];

        $this->wallsIoClientMock
            ->expects(self::once())
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data,
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest),
                $this->requestMock
            )
        );
    }
}
