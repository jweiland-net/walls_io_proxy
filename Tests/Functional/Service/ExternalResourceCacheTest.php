<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Functional\Service;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JWeiland\WallsIoProxy\Service\ExternalResourceCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test the validation of downloaded external resources
 */
class ExternalResourceCacheTest extends FunctionalTestCase
{
    private const URL = 'https://cdn.example.com/images/photo.jpg?signature=secret';

    private const CONTENT_RECORD_UID = 12;

    private ExternalResourceCache $subject;

    private RequestFactory&MockObject $requestFactoryMock;

    private LoggerInterface&MockObject $loggerMock;

    protected array $testExtensionsToLoad = [
        'jweiland/walls-io-proxy',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->subject = new ExternalResourceCache($this->requestFactoryMock, $this->loggerMock);
        // The public directory is shared by all tests of this class
        $this->subject->clear(self::CONTENT_RECORD_UID);
    }

    protected function tearDown(): void
    {
        $this->subject->clear(self::CONTENT_RECORD_UID);
        unset(
            $this->subject,
            $this->requestFactoryMock,
            $this->loggerMock,
        );

        parent::tearDown();
    }

    private function getFixture(string $fileName): string
    {
        return (string)file_get_contents(__DIR__ . '/../Fixtures/Images/' . $fileName);
    }

    /**
     * @return list<string> Names of all files below the cache directory of the test content record
     */
    private function getCachedFiles(): array
    {
        $targetDirectory = $this->subject->getTargetDirectory(self::CONTENT_RECORD_UID);
        if (!is_dir($targetDirectory)) {
            return [];
        }

        return array_values(array_diff(scandir($targetDirectory) ?: [], ['.', '..']));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function allowedImagesDataProvider(): array
    {
        return [
            'JPEG' => ['pixel.jpg', 'jpg'],
            'PNG' => ['pixel.png', 'png'],
            'GIF' => ['pixel.gif', 'gif'],
            'WebP' => ['pixel.webp', 'webp'],
        ];
    }

    #[Test]
    #[DataProvider('allowedImagesDataProvider')]
    public function cacheStoresImageWithHashAsNameAndExtensionOfDetectedType(string $fixture, string $extension): void
    {
        $content = $this->getFixture($fixture);
        $this->requestFactoryMock
            ->expects($this->once())
            ->method('request')
            ->with(self::URL)
            ->willReturn(new Response(200, [], $content));

        $this->loggerMock
            ->expects($this->never())
            ->method('warning');

        $webPath = $this->subject->cache(self::URL, self::CONTENT_RECORD_UID);

        $expectedFileName = hash('sha256', self::URL) . '.' . $extension;
        self::assertSame([$expectedFileName], $this->getCachedFiles());
        self::assertStringEndsWith(
            'typo3temp/assets/walls_io_proxy/' . self::CONTENT_RECORD_UID . '/' . $expectedFileName,
            (string)$webPath,
        );
        self::assertSame(
            $content,
            file_get_contents($this->subject->getTargetDirectory(self::CONTENT_RECORD_UID) . '/' . $expectedFileName),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function maliciousUrlsDataProvider(): array
    {
        return [
            'PHP file' => ['https://cdn.example.com/shell.php'],
            'PHP file with image extension chain' => ['https://cdn.example.com/shell.php.jpg'],
            'Path traversal' => ['https://cdn.example.com/../../../../../typo3conf/evil.php'],
            'Encoded path traversal' => ['https://cdn.example.com/%2e%2e/%2e%2e/evil.php'],
            'Query string with extension' => ['https://cdn.example.com/image?file=evil.php'],
        ];
    }

    #[Test]
    #[DataProvider('maliciousUrlsDataProvider')]
    public function cacheNeverUsesPartsOfTheRemoteUrlAsFileName(string $url): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], $this->getFixture('pixel.png')));

        $webPath = $this->subject->cache($url, self::CONTENT_RECORD_UID);

        self::assertSame([hash('sha256', $url) . '.png'], $this->getCachedFiles());
        self::assertMatchesRegularExpression(
            '#^(.*/)?typo3temp/assets/walls_io_proxy/' . self::CONTENT_RECORD_UID . '/[0-9a-f]{64}\.png$#',
            (string)$webPath,
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function noImageContentDataProvider(): array
    {
        return [
            'PHP code' => ['<?php system($_GET["cmd"]);'],
            'HTML' => ['<html><script>alert(1)</script></html>'],
            'SVG' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'],
            'Plain text' => ['Hello World'],
            'Image header only' => ["\x89PNG\r\n\x1a\n"],
            'GIF header with garbage' => ['GIF89a'],
        ];
    }

    #[Test]
    #[DataProvider('noImageContentDataProvider')]
    public function cacheRejectsContentWhichIsNotAnImageAndLogsWarning(string $content): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, ['Content-Type' => 'image/jpeg'], $content));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Rejected external resource: The downloaded content is not an image of an allowed type.',
                [
                    'url' => 'https://cdn.example.com/images/photo.jpg',
                    'contentRecordUid' => self::CONTENT_RECORD_UID,
                ],
            );

        self::assertNull($this->subject->cache(self::URL, self::CONTENT_RECORD_UID));
        self::assertSame([], $this->getCachedFiles());
    }

    #[Test]
    public function cacheRejectsEmptyContent(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], ''));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with('Rejected external resource: The file is empty.');

        self::assertNull($this->subject->cache(self::URL, self::CONTENT_RECORD_UID));
        self::assertSame([], $this->getCachedFiles());
    }

    #[Test]
    public function cacheRejectsFilesWhichAreTooLargeByContentLengthHeader(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(
                new Response(200, ['Content-Length' => (string)(ExternalResourceCache::MAX_FILE_SIZE + 1)], $this->getFixture('pixel.png')),
            );

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with('Rejected external resource: The file is too large.');

        self::assertNull($this->subject->cache(self::URL, self::CONTENT_RECORD_UID));
        self::assertSame([], $this->getCachedFiles());
    }

    #[Test]
    public function cacheRejectsFilesWhichAreTooLargeEvenIfServerLiesAboutTheSize(): void
    {
        // A valid image followed by garbage to exceed the limit
        $content = $this->getFixture('pixel.png') . str_repeat('0', ExternalResourceCache::MAX_FILE_SIZE);
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], $content));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with('Rejected external resource: The file is too large.');

        self::assertNull($this->subject->cache(self::URL, self::CONTENT_RECORD_UID));
        self::assertSame([], $this->getCachedFiles());
    }

    #[Test]
    public function cacheRejectsResponsesWithStatusCodeDifferentFrom200(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(404, [], $this->getFixture('pixel.png')));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Rejected external resource: Server responded with a status code different from 200.',
                self::containsEqual(404),
            );

        self::assertNull($this->subject->cache(self::URL, self::CONTENT_RECORD_UID));
        self::assertSame([], $this->getCachedFiles());
    }

    #[Test]
    public function cacheLogsTransferExceptionsWithoutWritingAnyFile(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willThrowException(new ConnectException('Connection refused', new Request('GET', self::URL)));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with(
                'External resource could not be downloaded.',
                self::callback(
                    static fn(array $context): bool => $context['exception'] === ConnectException::class
                        && !str_contains((string)json_encode($context), 'signature=secret'),
                ),
            );

        self::assertNull($this->subject->cache(self::URL, self::CONTENT_RECORD_UID));
        self::assertSame([], $this->getCachedFiles());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAllowedUrlsDataProvider(): array
    {
        return [
            'Empty URL' => [''],
            'Relative URL' => ['/typo3temp/image.png'],
            'Protocol relative URL' => ['//cdn.example.com/image.png'],
            'File protocol' => ['file:///etc/passwd'],
            'FTP protocol' => ['ftp://cdn.example.com/image.png'],
            'Gopher protocol' => ['gopher://cdn.example.com/image.png'],
            'Data URI' => ['data:image/png;base64,iVBORw0KGgo='],
            'JavaScript URI' => ['javascript:alert(1)'],
            'URL with credentials' => ['https://user:password@cdn.example.com/image.png'],
            'Localhost' => ['http://localhost/image.png'],
            'Loopback address' => ['http://127.0.0.1/image.png'],
            'Cloud metadata address' => ['http://169.254.169.254/latest/meta-data/'],
            'Private network address' => ['http://192.168.0.1/image.png'],
            'IPv6 loopback address' => ['http://[::1]/image.png'],
            'Too long URL' => ['https://cdn.example.com/' . str_repeat('a', 2100)],
        ];
    }

    #[Test]
    #[DataProvider('notAllowedUrlsDataProvider')]
    public function cacheRejectsNotAllowedUrlsWithoutRequestingThem(string $url): void
    {
        $this->requestFactoryMock
            ->expects($this->never())
            ->method('request');

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with('Rejected external resource: Only http(s) URLs of public hosts are allowed.');

        self::assertNull($this->subject->cache($url, self::CONTENT_RECORD_UID));
        self::assertSame([], $this->getCachedFiles());
    }

    #[Test]
    public function cacheDoesNotLogQueryStringOfUrl(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], 'no image'));

        $this->loggerMock
            ->method('warning')
            ->with(
                self::anything(),
                self::callback(static fn(array $context): bool => !str_contains((string)json_encode($context), 'secret')),
            );

        $this->subject->cache(self::URL, self::CONTENT_RECORD_UID);
    }

    #[Test]
    public function cacheUsesAlreadyCachedFileWithoutDownloadingItAgain(): void
    {
        $this->requestFactoryMock
            ->expects($this->once())
            ->method('request')
            ->willReturn(new Response(200, [], $this->getFixture('pixel.gif')));

        $firstResult = $this->subject->cache(self::URL, self::CONTENT_RECORD_UID);
        $secondResult = $this->subject->cache(self::URL, self::CONTENT_RECORD_UID);

        self::assertNotNull($firstResult);
        self::assertSame($firstResult, $secondResult);
    }

    #[Test]
    public function cacheDownloadsAgainIfCachedFileIsEmpty(): void
    {
        $targetDirectory = $this->subject->getTargetDirectory(self::CONTENT_RECORD_UID);
        mkdir($targetDirectory, 0777, true);
        file_put_contents($targetDirectory . '/' . hash('sha256', self::URL) . '.png', '');

        $this->requestFactoryMock
            ->expects($this->once())
            ->method('request')
            ->willReturn(new Response(200, [], $this->getFixture('pixel.png')));

        self::assertNotNull($this->subject->cache(self::URL, self::CONTENT_RECORD_UID));

        // The size of the empty file was read before, so PHP's stat cache has to be cleared
        $cachedFile = $targetDirectory . '/' . hash('sha256', self::URL) . '.png';
        clearstatcache(true, $cachedFile);
        self::assertGreaterThan(0, filesize($cachedFile));
    }

    #[Test]
    public function clearRemovesAllCachedFilesOfContentRecord(): void
    {
        $this->requestFactoryMock
            ->method('request')
            ->willReturn(new Response(200, [], $this->getFixture('pixel.png')));

        $this->subject->cache(self::URL, self::CONTENT_RECORD_UID);
        $this->subject->cache('https://cdn.example.com/other.png', self::CONTENT_RECORD_UID);
        $this->subject->cache(self::URL, 99);
        self::assertCount(2, $this->getCachedFiles());

        self::assertTrue($this->subject->clear(self::CONTENT_RECORD_UID));

        self::assertDirectoryDoesNotExist($this->subject->getTargetDirectory(self::CONTENT_RECORD_UID));
        self::assertDirectoryExists($this->subject->getTargetDirectory(99));
    }

    #[Test]
    public function clearWithoutCacheDirectoryReturnsTrue(): void
    {
        self::assertTrue($this->subject->clear(self::CONTENT_RECORD_UID));
    }
}
