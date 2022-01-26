<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Client;

use JWeiland\WallsIoProxy\Client\Request\PostsRequest;
use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Helper\MessageHelper;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Walls IO Client Test
 */
class WallsIoClientTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var WallsIoClient
     */
    protected $subject;

    /**
     * @var RequestFactory|ObjectProphecy
     */
    protected $requestFactoryProphecy;

    /**
     * @var MessageHelper|ObjectProphecy
     */
    protected $messageHelperProphecy;

    public function setUp(): void
    {
        $this->requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $this->messageHelperProphecy = $this->prophesize(MessageHelper::class);

        $this->subject = new WallsIoClient(
            $this->requestFactoryProphecy->reveal(),
            $this->messageHelperProphecy->reveal()
        );
    }

    public function tearDown(): void
    {
        unset(
            $this->subject,
            $this->requestFactoryProphecy,
            $this->messageHelperProphecy
        );
        parent::tearDown();
    }

    /**
     * @test
     */
    public function processRequestWithInvalidRequestAddsFlashMessage(): void
    {
        /** @var PostsRequest|ObjectProphecy $postsRequest */
        $postsRequest = $this->prophesize(PostsRequest::class);
        $postsRequest
            ->buildUri()
            ->shouldBeCalled()
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->isValidRequest()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->messageHelperProphecy
            ->addFlashMessage(
                'URI is empty or contains invalid chars. URI: https://www.jweiland.net',
                'Invalid request URI',
                FlashMessage::ERROR
            )
            ->shouldBeCalled();

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest->reveal())
        );
    }

    /**
     * @test
     */
    public function processRequestWithInvalidClientResponseAddsFlashMessage(): void
    {
        /** @var PostsRequest|ObjectProphecy $postsRequest */
        $postsRequest = $this->prophesize(PostsRequest::class);
        $postsRequest
            ->buildUri()
            ->shouldBeCalled()
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->isValidRequest()
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var Response|ObjectProphecy $clientResponse */
        $clientResponse = $this->prophesize(Response::class);
        $clientResponse
            ->getStatusCode()
            ->shouldBeCalled()
            ->willReturn(500);

        $this->requestFactoryProphecy
            ->request('https://www.jweiland.net')
            ->shouldBeCalled()
            ->willReturn($clientResponse->reveal());

        $this->messageHelperProphecy
            ->addFlashMessage(
                'Walls.io responses with a status code different from 200',
                'Status Code: 500',
                FlashMessage::ERROR
            )
            ->shouldBeCalled();
        $this->messageHelperProphecy
            ->hasErrorMessages()
            ->shouldBeCalled()
            ->willReturn(true);

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest->reveal())
        );
    }

    /**
     * @test
     */
    public function processRequestWithInvalidRequestResultsInExceptionWithChangedAccessToken(): void
    {
        /** @var PostsRequest|ObjectProphecy $postsRequest */
        $postsRequest = $this->prophesize(PostsRequest::class);
        $postsRequest
            ->buildUri()
            ->shouldBeCalled()
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->getParameter('access_token')
            ->shouldBeCalled()
            ->willReturn('ABC');
        $postsRequest
            ->isValidRequest()
            ->shouldBeCalled()
            ->willReturn(true);

        $exception = new \ErrorException(
            'Server down. Uri: https://api.walls.io?fields=test&access_token=ABC&since=123',
            564,
            1,
            __FILE__,
            123
        );

        $this->requestFactoryProphecy
            ->request('https://www.jweiland.net')
            ->shouldBeCalled()
            ->willThrow($exception);

        $this->messageHelperProphecy
            ->addFlashMessage(
                'Server down. Uri: https://api.walls.io?fields=test&access_token=XXX&since=123',
                'Error Code: 564',
                FlashMessage::ERROR
            )
            ->shouldBeCalled();

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest->reveal())
        );
    }

    /**
     * @test
     */
    public function processRequestWithInvalidResponseAddsFlashMessage(): void
    {
        /** @var PostsRequest|ObjectProphecy $postsRequest */
        $postsRequest = $this->prophesize(PostsRequest::class);
        $postsRequest
            ->buildUri()
            ->shouldBeCalled()
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->isValidRequest()
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var Response|ObjectProphecy $clientResponse */
        $clientResponse = $this->prophesize(Response::class);
        $clientResponse
            ->getStatusCode()
            ->shouldBeCalled()
            ->willReturn(200);
        $clientResponse
            ->getBody()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->requestFactoryProphecy
            ->request('https://www.jweiland.net')
            ->shouldBeCalled()
            ->willReturn($clientResponse->reveal());

        $this->messageHelperProphecy
            ->addFlashMessage(
                'The response of walls.io was not a valid JSON response.',
                'Invalid JSON response',
                FlashMessage::ERROR
            )
            ->shouldBeCalled();
        $this->messageHelperProphecy
            ->hasErrorMessages()
            ->shouldBeCalled()
            ->willReturn(false);

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest->reveal())
        );
    }

    /**
     * @test
     */
    public function processRequestWithInvalidStatusAddsFlashMessage(): void
    {
        /** @var PostsRequest|ObjectProphecy $postsRequest */
        $postsRequest = $this->prophesize(PostsRequest::class);
        $postsRequest
            ->buildUri()
            ->shouldBeCalled()
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->isValidRequest()
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var Response|ObjectProphecy $clientResponse */
        $clientResponse = $this->prophesize(Response::class);
        $clientResponse
            ->getStatusCode()
            ->shouldBeCalled()
            ->willReturn(200);
        $clientResponse
            ->getBody()
            ->shouldBeCalled()
            ->willReturn(json_encode([
                'status' => 'error',
                'info' => [
                    0 => 'broken'
                ]
            ]));

        $this->requestFactoryProphecy
            ->request('https://www.jweiland.net')
            ->shouldBeCalled()
            ->willReturn($clientResponse->reveal());

        $this->messageHelperProphecy
            ->addFlashMessage(
                'broken',
                'error',
                FlashMessage::ERROR
            )
            ->shouldBeCalled();
        $this->messageHelperProphecy
            ->hasErrorMessages()
            ->shouldBeCalled()
            ->willReturn(false);

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest->reveal())
        );
    }

    /**
     * @test
     */
    public function processRequestReturnsWalls(): void
    {
        /** @var PostsRequest|ObjectProphecy $postsRequest */
        $postsRequest = $this->prophesize(PostsRequest::class);
        $postsRequest
            ->buildUri()
            ->shouldBeCalled()
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->isValidRequest()
            ->shouldBeCalled()
            ->willReturn(true);

        /** @var Response|ObjectProphecy $clientResponse */
        $clientResponse = $this->prophesize(Response::class);
        $clientResponse
            ->getStatusCode()
            ->shouldBeCalled()
            ->willReturn(200);
        $clientResponse
            ->getBody()
            ->shouldBeCalled()
            ->willReturn(json_encode([
                'status' => 'success'
            ]));

        $this->requestFactoryProphecy
            ->request('https://www.jweiland.net')
            ->shouldBeCalled()
            ->willReturn($clientResponse->reveal());

        $this->messageHelperProphecy
            ->hasErrorMessages()
            ->shouldBeCalled()
            ->willReturn(false);

        self::assertSame(
            [
                'status' => 'success'
            ],
            $this->subject->processRequest($postsRequest->reveal())
        );
    }
}
