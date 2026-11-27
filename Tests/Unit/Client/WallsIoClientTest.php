<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Client;

use GuzzleHttp\Psr7\Response;
use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Walls IO Client Test
 */
class WallsIoClientTest extends UnitTestCase
{
    private const ACCESS_TOKEN = 'ABC+123/secret';

    private WallsIoClient $subject;

    private RequestFactory&MockObject $requestFactoryMock;

    private LoggerInterface&MockObject $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->subject = new WallsIoClient(
            $this->requestFactoryMock,
            $this->loggerMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->requestFactoryMock,
            $this->loggerMock,
        );
        parent::tearDown();
    }

    private function createRequest(string $accessToken = self::ACCESS_TOKEN): PostsRequest
    {
        $request = new PostsRequest();
        $request->setAccessToken($accessToken);

        return $request;
    }

    #[Test]
    public function processRequestWithInvalidRequestLogsErrorWithoutAccessToken(): void
    {
        $request = $this->createRequest('');

        $this->requestFactoryMock
            ->expects($this->never())
            ->method('request');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Request URI is empty or contains invalid chars.',
                ['uri' => 'https://api.walls.io/v1/posts?fields=id%2Ccomment%2Ctype&include_inactive=0&limit=24&access_token='],
            );

        self::assertSame([], $this->subject->processRequest($request));
    }

    #[Test]
    public function processRequestWithInvalidClientResponseLogsError(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(500));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Walls.io responses with a status code different from 200',
                ['statusCode' => 500],
            );

        self::assertSame([], $this->subject->processRequest($this->createRequest()));
    }

    #[Test]
    public function processRequestWithExceptionLogsErrorWithoutAccessToken(): void
    {
        $exception = new \ErrorException(
            'Server down. Uri: https://api.walls.io?fields=test&access_token='
                . rawurlencode(self::ACCESS_TOKEN) . '&since=123 (' . self::ACCESS_TOKEN . ')',
            564,
        );

        $this->requestFactoryMock
            ->method('request')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                'Server down. Uri: https://api.walls.io?fields=test&access_token=XXX&since=123 (XXX)',
                ['exceptionCode' => 564],
            );

        self::assertSame([], $this->subject->processRequest($this->createRequest()));
    }

    #[Test]
    public function processRequestWithInvalidResponseLogsError(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], ''));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('The response of walls.io was not a valid JSON response.');

        self::assertSame([], $this->subject->processRequest($this->createRequest()));
    }

    #[Test]
    public function processRequestWithInvalidStatusLogsError(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(
                new Response(
                    200,
                    [],
                    json_encode(['status' => 'error', 'info' => ['broken']], JSON_THROW_ON_ERROR),
                ),
            );

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('broken', ['status' => 'error']);

        self::assertSame([], $this->subject->processRequest($this->createRequest()));
    }

    #[Test]
    public function processRequestWithResponseWithoutStatusLogsError(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], '{"foo": "bar"}'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Unknown error', ['status' => 'unknown']);

        self::assertSame([], $this->subject->processRequest($this->createRequest()));
    }

    #[Test]
    public function processRequestRequestsUriOfRequest(): void
    {
        $request = $this->createRequest();

        $this->requestFactoryMock
            ->expects($this->once())
            ->method('request')
            ->with($request->buildUri())
            ->willReturn(new Response(200, [], '{"status": "success"}'));

        $this->loggerMock
            ->expects($this->never())
            ->method('error');

        $this->subject->processRequest($request);
    }

    #[Test]
    public function processRequestReturnsWalls(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], json_encode(['status' => 'success'], JSON_THROW_ON_ERROR)));

        self::assertSame(
            ['status' => 'success'],
            $this->subject->processRequest($this->createRequest()),
        );
    }
}
