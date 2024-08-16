<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Service;

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Walls Service Test
 */
class WallsServiceTest extends FunctionalTestCase
{
    protected WallsService $subject;

    protected Registry $registry;

    /**
     * @var WallsIoClient|MockObject|(WallsIoClient&MockObject)
     */
    protected $wallsIoClientMock;

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

    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
        'jweiland/walls-io-proxy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/tt_content.csv');
        $this->setUpFrontendRootPage(1);

        $GLOBALS['LANG'] = $this->getMockBuilder(LanguageService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = new Registry();
        $this->wallsIoClientMock = $this->getMockBuilder(WallsIoClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new WallsService(
            $this->registry,
            $this->wallsIoClientMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->registry,
            $this->wallsIoClientMock
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

    /**
     * @test
     * @dataProvider dataProviderForInvalidPluginConfiguration
     */
    public function getWallPostsWithInvalidPluginConfigurationWillReturnEmptyArray(array $processedData): void
    {
        self::assertSame(
            [],
            $this->subject->getWallPosts(
                new PluginConfiguration($processedData)
            )
        );
    }

    /**
     * @test
     */
    public function getWallPostsWithEmptyClientResultWillReturnEmptyWalls(): void
    {
        $this->wallsIoClientMock
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => [],
                ]
            );

        $this->subject = new WallsService($this->registry, $this->wallsIoClientMock);
        $result = $this->subject->getWallPosts(
            new PluginConfiguration($this->processedDataForPostsRequest)
        );

        self::assertSame(
            [],
            $result
        );
    }

    /**
     * @test
     */
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
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(['status' => 'error']);
        $this->subject = new WallsService($this->registry, $this->wallsIoClientMock);
        $result = $this->subject->getWallPosts(
            new PluginConfiguration($this->processedDataForPostsRequest)
        );

        self::assertSame(
            [
                'foo' => 'far',
            ],
            $result
        );
    }

    /**
     * @test
     */
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
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $expected,
                ]
            );

        $this->wallsIoClientMock
            ->method('hasErrors')
            ->willReturn(false);
        $this->subject = new WallsService($this->registry, $this->wallsIoClientMock);
        $result = $this->subject->getWallPosts(
            new PluginConfiguration($this->processedDataForPostsRequest)
        );

        self::assertSame(
            $expected,
            $result
        );
    }

    /**
     * @test
     */
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
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data,
                ]
            );

        $this->wallsIoClientMock
            ->method('hasErrors')
            ->willReturn(false);
        $result = $this->subject->getWallPosts(
            new PluginConfiguration($this->processedDataForPostsRequest)
        );
        self::assertSame(
            $expected,
            $result
        );
    }

    /**
     * @test
     */
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
            ->method('processRequest')
            ->with(self::isInstanceOf(PostsRequest::class))
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data,
                ]
            );

        $this->wallsIoClientMock
            ->method('hasErrors')
            ->willReturn(false);

        $this->subject = new WallsService($this->registry, $this->wallsIoClientMock);
        $result = $this->subject->getWallPosts(
            new PluginConfiguration($this->processedDataForPostsRequest)
        );
        self::assertSame(
            $expected,
            $result
        );
    }
}
