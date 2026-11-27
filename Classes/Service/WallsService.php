<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Service;

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\Factory\WallsIoRequestFactory;
use JWeiland\WallsIoProxy\Request\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Service to retrieve the result from WallsIO, decode the result and store entries into Cache
 */
final readonly class WallsService
{
    /**
     * Fields containing the URL of an image. The images will be downloaded into our local file cache.
     */
    private const IMAGE_FIELDS = ['external_image', 'post_image'];

    /**
     * Fields containing external links. Only http(s) links are allowed to be rendered in href/src attributes.
     */
    private const LINK_FIELDS = ['post_link', 'permalink', 'userlink', 'post_video'];

    public function __construct(
        private Registry $registry,
        private WallsIoClient $client,
        private WallsIoRequestFactory $requestFactory,
        private ExternalResourceCache $resourceCache,
        private CommentSanitizer $commentSanitizer,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getWallPosts(PluginConfiguration $pluginConfiguration, ServerRequestInterface $request): array
    {
        $requestType = $pluginConfiguration->resolveRequestType();
        if (
            $requestType === null
            || $pluginConfiguration->getRecordUid() === 0
            || $pluginConfiguration->getInvalidFields() !== []
        ) {
            $this->logInvalidConfiguration($pluginConfiguration);

            return [];
        }

        $requestedWallPosts = $this->getUncachedRequestFromWallsIO(
            $this->requestFactory->create($requestType, $pluginConfiguration),
        );

        if ($requestedWallPosts === []) {
            $storedWallPosts = $this->getStoredWallPostsFromRegistry($pluginConfiguration);
            $this->logger->warning(
                'Walls.io did not return any posts. Using the posts stored in registry instead.',
                [
                    'contentRecordUid' => $pluginConfiguration->getRecordUid(),
                    'storedPosts' => count($storedWallPosts),
                ],
            );

            return $storedWallPosts;
        }

        $wallPosts = [];
        foreach ($requestedWallPosts as $requestedWallPost) {
            if (is_array($requestedWallPost) && isset($requestedWallPost['id']) && is_scalar($requestedWallPost['id'])) {
                $wallPosts[$requestedWallPost['id']] = $this->getSanitizedPost($requestedWallPost, $pluginConfiguration);
            }
        }

        // Do not store wall posts on BE yoast request
        if (!$request->hasHeader('x-yoast-page-request')) {
            $this->setWallPostsToRegistry($request, $wallPosts, $pluginConfiguration);
        }

        return $wallPosts;
    }

    /**
     * Clear cache for a specific wall plugin (tt_content record UID).
     * Will be called by the AJAX controller of the backend.
     */
    public function clearCache(int $contentRecordUid): bool
    {
        if ($contentRecordUid <= 0) {
            return false;
        }

        $this->registry->remove('WallsIoProxy', 'ContentRecordUid_' . $contentRecordUid);
        $this->registry->remove('WallsIoProxy', 'PageCacheExpireTime_' . $contentRecordUid);

        return $this->resourceCache->clear($contentRecordUid);
    }

    /**
     * @return array<string, mixed>
     */
    private function getUncachedRequestFromWallsIO(RequestInterface $wallsIoRequest): array
    {
        $response = $this->client->processRequest($wallsIoRequest);

        if (
            array_key_exists('status', $response)
            && $response['status'] === 'success'
            && array_key_exists('data', $response)
            && is_array($response['data'])
        ) {
            return $response['data'];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getStoredWallPostsFromRegistry(PluginConfiguration $pluginConfiguration): array
    {
        $storedWallPosts = $this->registry->get(
            'WallsIoProxy',
            'ContentRecordUid_' . $pluginConfiguration->getRecordUid(),
            [],
        );

        return is_array($storedWallPosts) ? $storedWallPosts : [];
    }

    /**
     * @param array<string, mixed> $wallPosts
     */
    private function setWallPostsToRegistry(
        ServerRequestInterface $request,
        array $wallPosts,
        PluginConfiguration $pluginConfiguration,
    ): void {
        $this->registry->set(
            'WallsIoProxy',
            'ContentRecordUid_' . $pluginConfiguration->getRecordUid(),
            $wallPosts,
        );

        $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
        if (!$cacheDataCollector instanceof CacheDataCollector) {
            return;
        }

        $cacheLifetime = min($GLOBALS['EXEC_TIME'] + $cacheDataCollector->resolveLifetime(), PHP_INT_MAX);

        $this->registry->set(
            'WallsIoProxy',
            'PageCacheExpireTime_' . $pluginConfiguration->getRecordUid(),
            $cacheLifetime,
        );
    }

    private function logInvalidConfiguration(PluginConfiguration $pluginConfiguration): void
    {
        $invalidFields = $pluginConfiguration->getInvalidFields();
        if ($pluginConfiguration->getRecordUid() === 0) {
            $invalidFields[] = 'recordUid';
        }

        $this->logger->warning(
            'Invalid walls.io plugin configuration. No posts will be requested.',
            [
                'contentRecordUid' => $pluginConfiguration->getRecordUid(),
                'invalidFields' => $invalidFields,
            ],
        );
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function getSanitizedPost(array $post, PluginConfiguration $pluginConfiguration): array
    {
        $contentRecordUid = $pluginConfiguration->getRecordUid();

        if (array_key_exists('created_timestamp', $post)) {
            $post['created_timestamp_as_text'] = $this->getCreationText((int)$post['created_timestamp']);
        }

        // Never fall back to the external URL. Visitors should not contact the external servers.
        foreach (self::IMAGE_FIELDS as $field) {
            if (array_key_exists($field, $post)) {
                $post[$field] = is_string($post[$field])
                    ? ($this->resourceCache->cache($post[$field], $contentRecordUid) ?? '')
                    : '';
            }
        }

        foreach (self::LINK_FIELDS as $field) {
            if (array_key_exists($field, $post)) {
                $post[$field] = $this->getHttpUrl($post[$field]);
            }
        }

        if (isset($post['comment']) && is_string($post['comment']) && $post['comment'] !== '') {
            $post['html_comment'] = $this->commentSanitizer->sanitize(
                $this->replaceExternalImages($post['comment'], $contentRecordUid),
            );
        }

        return $post;
    }

    /**
     * Replaces the external images of a comment by their locally cached copy.
     * Images which can not be cached will be removed. All other attributes of the img tag get lost.
     */
    private function replaceExternalImages(string $comment, int $contentRecordUid): string
    {
        return (string)preg_replace_callback(
            '/<img\b[^>]*>/i',
            function (array $matches) use ($contentRecordUid): string {
                if (preg_match('/\bsrc\s*=\s*(["\'])(?<src>.*?)\1/is', $matches[0], $source) !== 1) {
                    return '';
                }

                $cachedImage = $this->resourceCache->cache(html_entity_decode($source['src']), $contentRecordUid);

                return $cachedImage === null ? '' : '<img src="' . htmlspecialchars($cachedImage) . '" alt="">';
            },
            $comment,
        );
    }

    /**
     * Only http(s) links are allowed. Everything else (javascript:, data:, ...) will be removed.
     */
    private function getHttpUrl(mixed $url): string
    {
        if (!is_string($url)) {
            return '';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true) ? $url : '';
    }

    private function getCreationText(int $creationTime): string
    {
        $currentTimestamp = (int)date('U');
        $diffInSeconds = $currentTimestamp - $creationTime;

        $creationDate = new \DateTime(date('c', $creationTime));
        $currentDate = new \DateTime(date('c', $currentTimestamp));
        $dateInterval = $currentDate->diff($creationDate);

        if ($diffInSeconds <= 60) {
            return (string)LocalizationUtility::translate(
                'creationTime.seconds',
                'walls_io_proxy',
            );
        }

        if ($diffInSeconds <= 3600) {
            return (string)LocalizationUtility::translate(
                'creationTime.minutes',
                'walls_io_proxy',
                [$dateInterval->format('%i')],
            );
        }

        if ($diffInSeconds <= 86400) {
            return (string)LocalizationUtility::translate(
                'creationTime.hours',
                'walls_io_proxy',
                [$dateInterval->format('%h')],
            );
        }

        return (string)LocalizationUtility::translate(
            'creationTime.date',
            'walls_io_proxy',
            [$creationDate->format('d.m.Y H:i')],
        );
    }
}
