<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Client;

use GuzzleHttp\Psr7\Stream;
use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Walls IO Client Test
 */
class WallsIoClientTest extends UnitTestCase
{
    protected WallsIoClient $subject;

    /**
     * @var RequestFactory|MockObject|(RequestFactory&MockObject)
     */
    protected $requestFactoryMock;

    /**
     * @var LoggerInterface|MockObject|(LoggerInterface&MockObject)
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactoryMock = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->subject = new WallsIoClient(
            $this->requestFactoryMock,
            $this->loggerMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->requestFactoryMock,
            $this->loggerMock
        );
        parent::tearDown();
    }

    #[Test]
    public function processRequestWithInvalidRequestLogsError(): void
    {
        /** @var PostsRequest|MockObject $postsRequest */
        $postsRequest = $this->createMock(PostsRequest::class);
        $postsRequest
            ->method('buildUri')
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->expects(self::once())
            ->method('isValidRequest')
            ->willReturn(false);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Request URI is empty or contains invalid chars.',
                ['uri' => 'https://www.jweiland.net']
            );

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest)
        );
    }

    #[Test]
    public function processRequestWithInvalidClientResponseLogsError(): void
    {
        /** @var PostsRequest|MockObject $postsRequest */
        $postsRequest = $this->getMockBuilder(PostsRequest::class)->getMock();
        $postsRequest
            ->method('buildUri')
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->method('isValidRequest')
            ->willReturn(true);

        /** @var Response|MockObject $clientResponse */
        $clientResponse = $this->createMock(Response::class);
        $clientResponse
            ->method('getStatusCode')
            ->willReturn(500);

        $this->requestFactoryMock
            ->method('request')
            ->with('https://www.jweiland.net')
            ->willReturn($clientResponse);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Walls.io responses with a status code different from 200',
                ['Status Code' => 500]
            );

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest)
        );
    }

    #[Test]
    public function processRequestWithExceptionLogsError(): void
    {
        $postsRequest = $this->getMockBuilder(PostsRequest::class)
            ->getMock();
        $postsRequest
            ->method('buildUri')
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->method('getParameter')
            ->with('access_token')
            ->willReturn('ABC');
        $postsRequest
            ->method('isValidRequest')
            ->willReturn(true);

        $exception = new \ErrorException(
            'Server down. Uri: https://api.walls.io?fields=test&access_token=ABC&since=123',
            564,
            1,
            __FILE__,
            123
        );

        $this->requestFactoryMock
            ->method('request')
            ->with('https://www.jweiland.net')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Server down. Uri: https://api.walls.io?fields=test&access_token=XXX&since=123',
                ['Exception Code' => 564]
            );

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest)
        );
    }

    #[Test]
    public function processRequestWithInvalidResponseLogsError(): void
    {
        /** @var PostsRequest|MockObject $postsRequest */
        $postsRequest = $this->getMockBuilder(PostsRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $postsRequest
            ->method('buildUri')
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->method('isValidRequest')
            ->willReturn(true);

        /** @var Response|MockObject $clientResponse */
        $clientResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $clientResponse
            ->method('getStatusCode')
            ->willReturn(200);

        // Create a mock for the StreamInterface
        $streamMock = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $streamMock->method('__toString')
            ->willReturn('');

        $clientResponse
            ->method('getBody')
            ->willReturn($streamMock);

        $this->requestFactoryMock
            ->method('request')
            ->with('https://www.jweiland.net')
            ->willReturn($clientResponse);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('The response of walls.io was not a valid JSON response.');

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest)
        );
    }

    #[Test]
    public function processRequestWithInvalidStatusLogsError(): void
    {
        /** @var PostsRequest|MockObject $postsRequest */
        $postsRequest = $this->createMock(PostsRequest::class);
        $postsRequest
            ->method('buildUri')
            ->willReturn('https://www.jweiland.net');

        $postsRequest
            ->method('isValidRequest')
            ->willReturn(true);

        $clientResponse = $this->createMock(Response::class);
        $clientResponse
            ->method('getStatusCode')
            ->willReturn(200);

        $streamMock = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Configure the stream mock to return the JSON string when __toString() is called
        $streamMock->expects(self::once())
            ->method('__toString')
            ->willReturn(
                json_encode([
                    'status' => 'error',
                    'info' => [
                        0 => 'broken',
                    ],
                ], JSON_THROW_ON_ERROR)
            );

        $clientResponse
            ->method('getBody')
            ->willReturn($streamMock);

        $this->requestFactoryMock
            ->method('request')
            ->with('https://www.jweiland.net')
            ->willReturn($clientResponse);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('broken', ['status' => 'error']);

        self::assertSame(
            [],
            $this->subject->processRequest($postsRequest)
        );
    }

    #[Test]
    public function processRequestReturnsWalls(): void
    {
        /** @var PostsRequest|MockObject $postsRequest */
        $postsRequest = $this->getMockBuilder(PostsRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $postsRequest
            ->method('buildUri')
            ->willReturn('https://www.jweiland.net');
        $postsRequest
            ->method('isValidRequest')
            ->willReturn(true);

        $streamMock = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Configure the stream mock to return the JSON string when __toString() is called
        $streamMock->expects(self::once())
            ->method('__toString')
            ->willReturn(
                json_encode([
                    'status' => 'success',
                ], JSON_THROW_ON_ERROR)
            );

        /** @var Response|MockObject $clientResponse */
        $clientResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $clientResponse
            ->method('getStatusCode')
            ->willReturn(200);
        $clientResponse
            ->method('getBody')
            ->willReturn($streamMock);

        $this->requestFactoryMock
            ->method('request')
            ->with('https://www.jweiland.net')
            ->willReturn($clientResponse);

        self::assertSame(
            [
                'status' => 'success',
            ],
            $this->subject->processRequest($postsRequest)
        );
    }
}
