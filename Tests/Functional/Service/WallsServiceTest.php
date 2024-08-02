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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Walls Service Test
 */
class WallsServiceTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var WallsService
     */
    protected $subject;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WallsIoClient|ObjectProphecy
     */
    protected $wallsIoClientProphecy;

    protected $processedDataForPostsRequest = [
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
    protected $testExtensionsToLoad = [
        'typo3conf/ext/walls_io_proxy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet('ntf://Database/tt_content.xml');
        $this->setUpFrontendRootPage(1);

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $this->registry = new Registry();
        $this->wallsIoClientProphecy = $this->prophesize(WallsIoClient::class);

        $this->subject = new WallsService(
            $this->registry,
            $this->wallsIoClientProphecy->reveal()
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->registry,
            $this->wallsIoClientProphecy
        );

        parent::tearDown();
    }

    public function dataProviderForInvalidPluginConfiguration(): array
    {
        return [
            'Missing record UID' => [[]],
            'Missing access token' => [['data' => ['uid' => 1]]],
            'Missing entries to load' => [['data' => ['uid' => 1], 'conf' => ['accessToken' => 'ABC123']]],
            'Missing entries to show' => [['data' => ['uid' => 1], 'conf' => ['accessToken' => 'ABC123', 'entriesToLoad' => 24]]],
            'Missing request type' => [['data' => ['uid' => 1], 'conf' => ['accessToken' => 'ABC123', 'entriesToLoad' => 24, 'entriesToShow' => 8]]],
            'Invalid request type' => [['data' => ['uid' => 1], 'conf' => ['accessToken' => 'ABC123', 'entriesToLoad' => 24, 'entriesToShow' => 8, 'requestType' => 'foo']]],
        ];
    }

    /**
     * @test
     *
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
        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => [],
                ]
            );

        self::assertSame(
            [],
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest)
            )
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

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(['status' => 'error']);

        self::assertSame(
            [
                'foo' => 'far',
            ],
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest)
            )
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

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $expected,
                ]
            );
        $this->wallsIoClientProphecy
            ->hasErrors()
            ->shouldBeCalled()
            ->willReturn(false);

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest)
            )
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

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data,
                ]
            );
        $this->wallsIoClientProphecy
            ->hasErrors()
            ->shouldBeCalled()
            ->willReturn(false);

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest)
            )
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

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data,
                ]
            );
        $this->wallsIoClientProphecy
            ->hasErrors()
            ->shouldBeCalled()
            ->willReturn(false);

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(
                new PluginConfiguration($this->processedDataForPostsRequest)
            )
        );
    }
}
