<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Service;

use JWeiland\WallsIoProxy\Client\Request\PostsRequest;
use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Service\WallsService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Registry;

/**
 * Walls Service Test
 */
class WallsServiceTest extends FunctionalTestCase
{
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

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/walls_io_proxy'
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->registry = new Registry();
        $this->wallsIoClientProphecy = $this->prophesize(WallsIoClient::class);

        $this->subject = new WallsService(
            12345,
            $this->registry,
            $this->wallsIoClientProphecy->reveal()
        );
    }

    protected function tearDown()
    {
        unset(
            $this->subject,
            $this->registry,
            $this->wallsIoClientProphecy
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getWallPostsWithEmptyMaxPostsWillReturnEmptyArray()
    {
        self::assertSame(
            [],
            $this->subject->getWallPosts(0)
        );

        self::assertNull(
            $this->registry->get(
                'WallsIoProxy',
                'WallId_12345'
            )
        );
    }

    /**
     * @test
     */
    public function getWallPostsWithMaxPostsWillReturnCachedArrayOnEmptyResponse()
    {
        $this->registry->set(
            'WallsIoProxy',
            'WallId_12345',
            [
                'foo' => 'far'
            ]
        );

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => []
                ]
            );

        self::assertSame(
            [
                'foo' => 'far'
            ],
            $this->subject->getWallPosts(4)
        );
    }

    /**
     * @test
     */
    public function getWallPostsWithMaxPostsWillReturnCachedArrayOnInvalidResponse()
    {
        $this->registry->set(
            'WallsIoProxy',
            'WallId_12345',
            [
                'foo' => 'far'
            ]
        );

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'error'
                ]
            );

        self::assertSame(
            [
                'foo' => 'far'
            ],
            $this->subject->getWallPosts(4)
        );
    }

    /**
     * @test
     */
    public function getWallPostsWillReturnFreshWallPosts()
    {
        $expected = [
            '324125' => [
                'id' => '324125'
            ],
            '534213' => [
                'id' => '534213'
            ],
            '132452' => [
                'id' => '132452'
            ]
        ];

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $expected
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(3)
        );
    }

    /**
     * @test
     */
    public function getWallPostsWillNotAddCrossPosts()
    {
        $data = $expected = [
            '324125' => [
                'id' => '324125',
                'is_crosspost' => false
            ],
            '534213' => [
                'id' => '534213',
                'is_crosspost' => false
            ],
            '243512' => [
                'id' => '243512',
                'is_crosspost' => true
            ],
            '132452' => [
                'id' => '132452',
                'is_crosspost' => false
            ]
        ];

        unset($expected['243512']);

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(3)
        );
    }

    /**
     * @test
     */
    public function getWallPostsWillNotAddHiddenWallPosts()
    {
        $data = $expected = [
            '324125' => [
                'id' => '324125',
                'status' => false
            ],
            '534213' => [
                'id' => '534213',
                'status' => false
            ],
            '243512' => [
                'id' => '243512',
                'status' => true
            ],
            '132452' => [
                'id' => '132452',
                'status' => false
            ]
        ];

        unset($expected['243512']);

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(3)
        );
    }

    /**
     * @test
     */
    public function getWallPostsWillUsePostIdAsArrayKeys()
    {
        $data = [
            0 => [
                'id' => '324125'
            ],
            1 => [
                'id' => '534213'
            ],
        ];

        $expected = [
            '324125' => [
                'id' => '324125'
            ],
            '534213' => [
                'id' => '534213'
            ],
        ];

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(2)
        );
    }

    /**
     * @test
     */
    public function getWallPostsWillConvertTimestampHumanReadable()
    {
        $date = new \DateTime('now');
        $date->modify('-2 hours');

        $data = [
            0 => [
                'id' => '324125',
                'created_timestamp' => $date->format('U')
            ],
        ];

        $expected = [
            '324125' => [
                'id' => '324125',
                'created_timestamp' => $date->format('U'),
                'created_timestamp_as_text' => '2 hours ago'
            ],
        ];

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(1)
        );
    }

    /**
     * @test
     */
    public function getWallPostsWillConvertNewLinesToBR()
    {
        $data = [
            0 => [
                'id' => '324125',
                'comment' => 'Line1' . chr(10) . 'Line 2'
            ],
        ];

        $expected = [
            '324125' => [
                'id' => '324125',
                'comment' => 'Line1' . chr(10) . 'Line 2',
                'html_comment' => 'Line1<br />' . chr(10) . 'Line 2'
            ],
        ];

        $this->wallsIoClientProphecy
            ->processRequest(Argument::type(PostsRequest::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'status' => 'success',
                    'data' => $data
                ]
            );

        self::assertSame(
            $expected,
            $this->subject->getWallPosts(1)
        );
    }
}
