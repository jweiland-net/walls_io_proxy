<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Service;

use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Downloads images of walls.io posts into the public typo3temp folder, so no visitor needs to contact
 * the external servers (and does not receive any cookies from them).
 *
 * The content comes from an external source. That's why the target file name never depends on the
 * remote URL. It is a hash of the URL plus an extension derived from the detected image type.
 * Everything which is not a small, valid image of a whitelisted type will be rejected.
 */
final readonly class ExternalResourceCache
{
    public const MAX_FILE_SIZE = 5242880;

    private const TARGET_DIRECTORY = 'typo3temp/assets/walls_io_proxy';

    private const MAX_URL_LENGTH = 2048;

    /**
     * Whitelist of detected MIME types and the file extension they will be stored with
     *
     * @var array<string, string>
     */
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const REQUEST_OPTIONS = [
        'timeout' => 10,
        'connect_timeout' => 5,
        'http_errors' => false,
    ];

    public function __construct(
        private RequestFactory $requestFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * Returns the web path of the locally cached image or null, if the resource was rejected or could not be loaded.
     */
    public function cache(string $url, int $contentRecordUid): ?string
    {
        if (!$this->isAllowedUrl($url)) {
            $this->logger->warning(
                'Rejected external resource: Only http(s) URLs of public hosts are allowed.',
                ['url' => $this->getLoggableUrl($url), 'contentRecordUid' => $contentRecordUid],
            );

            return null;
        }

        $targetDirectory = $this->getTargetDirectory($contentRecordUid);
        $fileName = hash('sha256', $url);
        foreach (self::ALLOWED_IMAGE_TYPES as $extension) {
            $existingFile = sprintf('%s/%s.%s', $targetDirectory, $fileName, $extension);
            if (is_file($existingFile) && filesize($existingFile) > 0) {
                return PathUtility::getAbsoluteWebPath($existingFile);
            }
        }

        $content = $this->download($url, $contentRecordUid);
        if ($content === null) {
            return null;
        }

        $extension = $this->getExtensionOfImage($content);
        if ($extension === null) {
            $this->logger->warning(
                'Rejected external resource: The downloaded content is not an image of an allowed type.',
                ['url' => $this->getLoggableUrl($url), 'contentRecordUid' => $contentRecordUid],
            );

            return null;
        }

        $filePath = sprintf('%s/%s.%s', $targetDirectory, $fileName, $extension);
        GeneralUtility::mkdir_deep($targetDirectory);
        if (!GeneralUtility::writeFile($filePath, $content)) {
            $this->logger->error(
                'External resource could not be written to the file system.',
                ['url' => $this->getLoggableUrl($url), 'contentRecordUid' => $contentRecordUid],
            );

            return null;
        }

        return PathUtility::getAbsoluteWebPath($filePath);
    }

    /**
     * Remove all cached files of a wall plugin (tt_content record UID).
     */
    public function clear(int $contentRecordUid): bool
    {
        $targetDirectory = $this->getTargetDirectory($contentRecordUid);
        if (!is_dir($targetDirectory)) {
            return true;
        }

        if (GeneralUtility::rmdir($targetDirectory, true)) {
            return true;
        }

        $this->logger->error(
            'The cache directory of a walls.io plugin could not be removed.',
            ['contentRecordUid' => $contentRecordUid],
        );

        return false;
    }

    public function getTargetDirectory(int $contentRecordUid): string
    {
        return sprintf(
            '%s/%s/%d',
            Environment::getPublicPath(),
            self::TARGET_DIRECTORY,
            $contentRecordUid,
        );
    }

    private function download(string $url, int $contentRecordUid): ?string
    {
        $context = ['url' => $this->getLoggableUrl($url), 'contentRecordUid' => $contentRecordUid];

        try {
            $response = $this->requestFactory->request($url, 'GET', self::REQUEST_OPTIONS);
        } catch (TransferException $exception) {
            $this->logger->warning(
                'External resource could not be downloaded.',
                $context + ['exception' => $exception::class, 'exceptionCode' => $exception->getCode()],
            );

            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->warning(
                'Rejected external resource: Server responded with a status code different from 200.',
                $context + ['statusCode' => $response->getStatusCode()],
            );

            return null;
        }

        $contentLength = $response->getHeaderLine('Content-Length');
        if (is_numeric($contentLength) && (int)$contentLength > self::MAX_FILE_SIZE) {
            $this->logger->warning(
                'Rejected external resource: The file is too large.',
                $context + ['size' => (int)$contentLength, 'maxSize' => self::MAX_FILE_SIZE],
            );

            return null;
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        // Read one byte more than allowed to detect oversized files without loading them completely
        $content = $body->read(self::MAX_FILE_SIZE + 1);
        if (strlen($content) > self::MAX_FILE_SIZE) {
            $this->logger->warning(
                'Rejected external resource: The file is too large.',
                $context + ['maxSize' => self::MAX_FILE_SIZE],
            );

            return null;
        }

        if ($content === '') {
            $this->logger->warning('Rejected external resource: The file is empty.', $context);

            return null;
        }

        return $content;
    }

    private function getExtensionOfImage(string $content): ?string
    {
        $imageInformation = @getimagesizefromstring($content);
        if ($imageInformation === false) {
            return null;
        }

        return self::ALLOWED_IMAGE_TYPES[$imageInformation['mime']] ?? null;
    }

    private function isAllowedUrl(string $url): bool
    {
        if (strlen($url) > self::MAX_URL_LENGTH || !GeneralUtility::isValidUrl($url)) {
            return false;
        }

        $urlParts = parse_url($url);
        if (!is_array($urlParts)) {
            return false;
        }

        if (!in_array(strtolower($urlParts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        if (isset($urlParts['user']) || isset($urlParts['pass'])) {
            return false;
        }

        $host = strtolower(trim($urlParts['host'] ?? '', '[]'));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        // Do not allow IP addresses of private or reserved networks (e.g. 127.0.0.1 or 169.254.169.254)
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }

    /**
     * URLs of CDNs may contain signatures in their query string. These are not needed for log entries.
     */
    private function getLoggableUrl(string $url): string
    {
        $urlParts = parse_url($url);
        if (!is_array($urlParts)) {
            return '[invalid URL]';
        }

        return sprintf(
            '%s://%s%s',
            $urlParts['scheme'] ?? '',
            $urlParts['host'] ?? '',
            $urlParts['path'] ?? '',
        );
    }
}
