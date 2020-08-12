<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Client;

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Client\WallsIoRequest;
use JWeiland\WallsIoProxy\Client\WallsIoResponse;
use JWeiland\WallsIoProxy\Service\WallsService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Walls IO Client Test
 */
class WallsIoClientTest extends UnitTestCase
{
    /**
     * @var WallsIoClient
     */
    protected $subject;

    /**
     * @var RequestFactory|ObjectProphecy
     */
    protected $requestFactoryProphecy;

    protected function setUp()
    {
        $this->requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $this->subject = new WallsIoClient(
            new WallsService()
        );
    }

    protected function tearDown()
    {
        unset(
            $this->subject,
            $this->requestFactoryProphecy
        );
        parent::tearDown();
    }

    /**
     * @test
     */
    public function processRequestWillReturnEmptyResponse()
    {
        $request = new WallsIoRequest();
        $expectedResponse = new WallsIoResponse();

        self::assertEquals(
            $expectedResponse,
            $this->subject->processRequest($request)
        );
        self::assertTrue(
            $this->subject->hasError()
        );
        self::assertContains(
            'Missing mandatory Wall ID',
            $this->subject->getError()['title']
        );
    }

    /**
     * @test
     */
    public function processRequestWillBuildCorrectWallsUri()
    {
        $request = new WallsIoRequest();
        $request->setWallId(12345);

        $guzzleResponse = new Response();
        $guzzleResponse->getBody()->write('Test');
        $guzzleResponse->getBody()->rewind();
        $this->requestFactoryProphecy
            ->request(
                Argument::allOf(
                    Argument::containingString('wallId=12345'),
                    Argument::containingString('client=wallsio-frontend'),
                    Argument::containingString('cookieSupport=1'),
                    Argument::containingString('network='),
                    Argument::containingString('EIO=3'),
                    Argument::containingString('transport=polling'),
                    Argument::containingString('t=')
                ),
                Argument::cetera()
            )
            ->shouldBeCalled()
            ->willReturn($guzzleResponse);
        GeneralUtility::addInstance(RequestFactory::class, $this->requestFactoryProphecy->reveal());

        $expectedResponse = new WallsIoResponse();
        $expectedResponse->setBody('Test');

        self::assertEquals(
            $expectedResponse,
            $this->subject->processRequest($request)
        );
    }

    /**
     * @test
     */
    public function processRequestWillBuildCorrectRequestHeader()
    {
        $request = new WallsIoRequest();
        $request->setWallId(12345);

        $guzzleResponse = new Response();
        $guzzleResponse->getBody()->write('Test');
        $guzzleResponse->getBody()->rewind();
        $this->requestFactoryProphecy
            ->request(
                Argument::any(),
                Argument::exact('GET'),
                Argument::allOf(
                    Argument::withKey('headers'),
                    Argument::containing([
                        'accept' => '*/*',
                        'accept-encoding' => 'gzip, deflate, br',
                        'accept-language' =>'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7,so;q=0.6',
                        'cache-control' => 'no-cache',
                        'dnt' => '1',
                        'pragma' => 'no-cache',
                        'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36'
                    ])
                )
            )
            ->shouldBeCalled()
            ->willReturn($guzzleResponse);
        GeneralUtility::addInstance(RequestFactory::class, $this->requestFactoryProphecy->reveal());

        $expectedResponse = new WallsIoResponse();
        $expectedResponse->setBody('Test');

        self::assertEquals(
            $expectedResponse,
            $this->subject->processRequest($request)
        );
    }

    /**
     * @test
     */
    public function processRequestWithoutHeaderWillReturnBodyOnly()
    {
        $request = new WallsIoRequest();
        $request->setWallId(12345);
        $request->setIncludeHeader(0);

        $guzzleResponse = new Response();
        $guzzleResponse->getBody()->write('Body');
        $guzzleResponse->getBody()->rewind();
        $this->requestFactoryProphecy
            ->request(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($guzzleResponse);
        GeneralUtility::addInstance(RequestFactory::class, $this->requestFactoryProphecy->reveal());

        $expectedResponse = new WallsIoResponse();
        $expectedResponse->setBody('Body');

        self::assertEquals(
            $expectedResponse,
            $this->subject->processRequest($request)
        );
    }

    /**
     * @test
     */
    public function processRequestWithHeaderWillReturnHeaderAndBody()
    {
        $request = new WallsIoRequest();
        $request->setWallId(12345);
        $request->setIncludeHeader(1);

        $guzzleResponse = new Response();
        $guzzleResponse->withHeader('cache-control', 'no-pragma');
        $guzzleResponse->getBody()->write('Body');
        $guzzleResponse->getBody()->rewind();
        $this->requestFactoryProphecy
            ->request(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($guzzleResponse);
        GeneralUtility::addInstance(RequestFactory::class, $this->requestFactoryProphecy->reveal());

        $expectedResponse = new WallsIoResponse();
        $expectedResponse->setHeader('cache-control: no-pragma');
        $expectedResponse->setBody('Body');

        $response = $this->subject->processRequest($request);
        self::assertEquals(
            'Body',
            $response->getBody()
        );
        self::assertContains(
            'Host: walls.io',
            $response->getHeader()
        );
        self::assertContains(
            'Connection: close',
            $response->getHeader()
        );
    }
}
