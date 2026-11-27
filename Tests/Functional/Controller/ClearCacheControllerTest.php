<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Functional\Controller;

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Controller\ClearCacheController;
use JWeiland\WallsIoProxy\Factory\WallsIoRequestFactory;
use JWeiland\WallsIoProxy\Html\CommentSanitizerBuilder;
use JWeiland\WallsIoProxy\Service\CommentSanitizer;
use JWeiland\WallsIoProxy\Service\ExternalResourceCache;
use JWeiland\WallsIoProxy\Service\WallsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test the AJAX controller to clear the cache of a walls.io plugin
 */
class ClearCacheControllerTest extends FunctionalTestCase
{
    private ClearCacheController $subject;

    private Registry $registry;

    private LoggerInterface&MockObject $loggerMock;

    protected array $testExtensionsToLoad = [
        'jweiland/walls-io-proxy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/tt_content.csv');

        $this->registry = $this->get(Registry::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $requestFactory = $this->createMock(RequestFactory::class);
        $this->subject = new ClearCacheController(
            new WallsService(
                $this->registry,
                new WallsIoClient($requestFactory, $this->loggerMock),
                new WallsIoRequestFactory($this->get(Context::class)),
                new ExternalResourceCache($requestFactory, $this->loggerMock),
                new CommentSanitizer(new CommentSanitizerBuilder()),
                $this->loggerMock,
            ),
            $this->get(ConnectionPool::class),
            $this->get(LanguageServiceFactory::class),
            $this->loggerMock,
        );

        $this->registry->set('WallsIoProxy', 'ContentRecordUid_2', ['foo' => 'far']);
        $this->registry->set('WallsIoProxy', 'PageCacheExpireTime_2', 1234567890);
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->registry,
            $this->loggerMock,
        );

        parent::tearDown();
    }

    /**
     * @param array<string, mixed>|null $parsedBody
     */
    private function createRequest(int $beUserUid, ?array $parsedBody, string $method = 'POST'): ServerRequest
    {
        $request = (new ServerRequest('https://www.example.com/typo3/ajax/walls-io-proxy/clear-cache', $method))
            ->withParsedBody($parsedBody);

        return $beUserUid > 0
            ? $request->withAttribute('backend.user', $this->setUpBackendUser($beUserUid))
            : $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function assertCacheOfContentRecordWasNotCleared(): void
    {
        self::assertSame(['foo' => 'far'], $this->registry->get('WallsIoProxy', 'ContentRecordUid_2'));
        self::assertSame(1234567890, $this->registry->get('WallsIoProxy', 'PageCacheExpireTime_2'));
    }

    #[Test]
    public function clearActionWithAdminAndValidContentRecordClearsCache(): void
    {
        $cacheDirectory = Environment::getPublicPath() . '/typo3temp/assets/walls_io_proxy/2';
        mkdir($cacheDirectory, 0777, true);
        file_put_contents($cacheDirectory . '/test.png', 'test');

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with(
                'The cache of a walls.io plugin was cleared.',
                ['contentRecordUid' => 2, 'beUserUid' => 1],
            );

        $response = $this->subject->clearAction($this->createRequest(1, ['contentRecordUid' => '2']));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $result = $this->decode($response);
        self::assertTrue($result['success']);
        self::assertSame('Cache cleared', $result['title']);
        self::assertStringContainsString('content record 2', $result['message']);
        self::assertNull($this->registry->get('WallsIoProxy', 'ContentRecordUid_2'));
        self::assertNull($this->registry->get('WallsIoProxy', 'PageCacheExpireTime_2'));
        self::assertDirectoryDoesNotExist($cacheDirectory);
    }

    #[Test]
    public function clearActionWithHiddenContentRecordClearsCache(): void
    {
        $this->registry->set('WallsIoProxy', 'ContentRecordUid_3', ['foo' => 'far']);

        $response = $this->subject->clearAction($this->createRequest(1, ['contentRecordUid' => 3]));

        self::assertSame(200, $response->getStatusCode());
        self::assertNull($this->registry->get('WallsIoProxy', 'ContentRecordUid_3'));
    }

    #[Test]
    public function clearActionWithEditorIsForbidden(): void
    {
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'A non-administrator tried to clear the cache of a walls.io plugin.',
                ['beUserUid' => 2],
            );

        $response = $this->subject->clearAction($this->createRequest(2, ['contentRecordUid' => 2]));

        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
        $this->assertCacheOfContentRecordWasNotCleared();
    }

    #[Test]
    public function clearActionWithoutBackendUserIsForbidden(): void
    {
        $response = $this->subject->clearAction($this->createRequest(0, ['contentRecordUid' => 2]));

        self::assertSame(403, $response->getStatusCode());
        $this->assertCacheOfContentRecordWasNotCleared();
    }

    #[Test]
    public function clearActionWithGetRequestIsNotAllowed(): void
    {
        $response = $this->subject->clearAction($this->createRequest(1, ['contentRecordUid' => 2], 'GET'));

        self::assertSame(405, $response->getStatusCode());
        $this->assertCacheOfContentRecordWasNotCleared();
    }

    /**
     * @return array<string, array{array<string, mixed>|null}>
     */
    public static function invalidContentRecordDataProvider(): array
    {
        return [
            'No request body' => [null],
            'Missing UID' => [[]],
            'Empty UID' => [['contentRecordUid' => '']],
            'UID is zero' => [['contentRecordUid' => 0]],
            'Negative UID' => [['contentRecordUid' => -2]],
            'UID is no number' => [['contentRecordUid' => 'abc']],
            'UID is an array' => [['contentRecordUid' => [2]]],
            'Not existing record' => [['contentRecordUid' => 999]],
            'Record of other content type' => [['contentRecordUid' => 1]],
            'Deleted record' => [['contentRecordUid' => 4]],
        ];
    }

    /**
     * @param array<string, mixed>|null $parsedBody
     */
    #[Test]
    #[DataProvider('invalidContentRecordDataProvider')]
    public function clearActionWithInvalidContentRecordReturnsNotFound(?array $parsedBody): void
    {
        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Clearing the cache of a walls.io plugin was requested for an invalid content record.',
                self::arrayHasKey('beUserUid'),
            );

        $response = $this->subject->clearAction($this->createRequest(1, $parsedBody));

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($this->decode($response)['success']);
        $this->assertCacheOfContentRecordWasNotCleared();
    }

    #[Test]
    public function clearActionDoesNotUseQueryParametersAsContentRecordUid(): void
    {
        $request = $this->createRequest(1, null)->withQueryParams(['contentRecordUid' => 2]);

        $response = $this->subject->clearAction($request);

        self::assertSame(404, $response->getStatusCode());
        $this->assertCacheOfContentRecordWasNotCleared();
    }
}
