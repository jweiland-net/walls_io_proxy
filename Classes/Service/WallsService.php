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
use JWeiland\WallsIoProxy\Request\Posts\ChangedRequest;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use JWeiland\WallsIoProxy\Request\RequestInterface;
use JWeiland\WallsIoProxy\Utility\StringUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Service to retrieve result from WallsIO, decode the result and store entries into Cache
 */
class WallsService
{
    protected string $targetDirectory = 'typo3temp/assets/walls_io_proxy';

    /**
     * Fields to get from the API
     * @var array<string> $fields
     */
    protected array $fields = [
        'id',
        'comment',
        'type',
        'is_crosspost',
        'status',
        'created_timestamp',
        'external_name',
        'external_fullname',
        'external_user_id',
        'external_image',
        'post_image',
        'post_link',
    ];

    protected Registry $registry;

    protected WallsIoClient $client;

    protected ServerRequestInterface $request;

    public function __construct(Registry $registry, WallsIoClient $client, ServerRequestInterface $request)
    {
        $this->registry = $registry;
        $this->client = $client;
        $this->request = $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function getWallPosts(PluginConfiguration $pluginConfiguration): array
    {
        if (!$this->isValidPluginConfiguration($pluginConfiguration)) {
            return [];
        }

        $requestedWallPosts = $this->getUncachedRequestFromWallsIO($this->getWallsIoRequest($pluginConfiguration));

        if ($requestedWallPosts === []) {
            $wallPosts = $this->getStoredWallPostsFromRegistry($pluginConfiguration);
        } else {
            $wallPosts = [];
            foreach ($requestedWallPosts as $requestedWallPost) {
                if (is_array($requestedWallPost)) {
                    $sanitizedWallPost = $this->getSanitizedPost($requestedWallPost, $pluginConfiguration);
                    $wallPosts[$sanitizedWallPost['id']] = $sanitizedWallPost;
                }
            }

            // Do not store wall posts on BE yoast request
            if ($this->getTypo3Request()->getHeaders() && !array_key_exists('x-yoast-page-request', $this->getTypo3Request()->getHeaders())) {
                $this->setWallPostsToRegistry($wallPosts, $pluginConfiguration);
            }
        }

        return $wallPosts;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUncachedRequestFromWallsIO(RequestInterface $wallsIoRequest): array
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
    protected function getStoredWallPostsFromRegistry(PluginConfiguration $pluginConfiguration): array
    {
        return $this->registry->get(
            'WallsIoProxy',
            'ContentRecordUid_' . $pluginConfiguration->getRecordUid(),
            []
        );
    }

    /**
     * @param array<string, mixed> $wallPosts
     */
    protected function setWallPostsToRegistry(array $wallPosts, PluginConfiguration $pluginConfiguration): void
    {
        $this->registry->set(
            'WallsIoProxy',
            'ContentRecordUid_' . $pluginConfiguration->getRecordUid(),
            $wallPosts
        );
        $this->registry->set(
            'WallsIoProxy',
            'PageCacheExpireTime_' . $pluginConfiguration->getRecordUid(),
            $GLOBALS['EXEC_TIME'] + $this->getTypoScriptFrontendController()->get_cache_timeout()
        );
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    protected function getWallsIoRequest(PluginConfiguration $pluginConfiguration): RequestInterface
    {
        /** @var class-string<RequestInterface> $requestType */
        $requestType = $pluginConfiguration->getRequestType();

        /** @phpstan-var RequestInterface $wallsIoRequest */
        $wallsIoRequest = GeneralUtility::makeInstance($requestType);
        $wallsIoRequest->setFields($this->fields);
        $wallsIoRequest->setAccessToken($pluginConfiguration->getAccessToken());
        $wallsIoRequest->setLimit($pluginConfiguration->getEntriesToLoad());

        if ($wallsIoRequest instanceof PostsRequest) {
            $wallsIoRequest->setBefore('');
        }

        if ($wallsIoRequest instanceof ChangedRequest) {
            $wallsIoRequest->setSince(time() - (60 * 60 * 24 * $pluginConfiguration->getShowWallsSince()));
        }

        return $wallsIoRequest;
    }

    protected function isValidPluginConfiguration(PluginConfiguration $pluginConfiguration): bool
    {
        if ($pluginConfiguration->getRecordUid() === 0) {
            return false;
        }

        if ($pluginConfiguration->getAccessToken() === '') {
            return false;
        }

        if ($pluginConfiguration->getEntriesToLoad() === 0) {
            return false;
        }

        if ($pluginConfiguration->getEntriesToShow() === 0) {
            return false;
        }

        if ($pluginConfiguration->getRequestType() === '') {
            return false;
        }

        if (!class_exists($pluginConfiguration->getRequestType())) {
            return false;
        }

        return true;
    }

    /**
     * Clear cache for a specific wall plugin (tt_content record UID).
     * Will be called by Clear Cache post hook.
     */
    public function clearCache(int $contentRecordUid): int
    {
        if ($contentRecordUid !== 0) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->remove('WallsIoProxy', 'ContentRecordUid_' . $contentRecordUid);
            $registry->remove('WallsIoProxy', 'PageCacheExpireTime_' . $contentRecordUid);

            GeneralUtility::rmdir($this->getTargetDirectory($contentRecordUid));

            return 1;
        }

        return 0;
    }

    /**
     * Get cache directory for related files within the content of the wall posts comments.
     * Will be called by the AddWallsProcessor (DataProcessor)
     */
    public function getTargetDirectory(int $contentRecordUid): string
    {
        $targetDirectory = sprintf(
            '%s/%s/%s/',
            Environment::getPublicPath(),
            $this->targetDirectory,
            $contentRecordUid
        );

        if (!is_dir($targetDirectory)) {
            GeneralUtility::mkdir_deep($targetDirectory);
        }

        return $targetDirectory;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    protected function getSanitizedPost(array $post, PluginConfiguration $pluginConfiguration): array
    {
        if (array_key_exists('created_timestamp', $post)) {
            $post['created_timestamp_as_text'] = $this->getCreationText((int)$post['created_timestamp']);
        }

        if (
            array_key_exists('external_image', $post)
            && StringUtility::beginsWith((string)$post['external_image'], 'http')
        ) {
            $post['external_image'] = $this->cacheExternalResources(
                $post['external_image'],
                $pluginConfiguration->getRecordUid()
            );
        }

        if (
            array_key_exists('post_image', $post)
            && StringUtility::beginsWith((string)$post['post_image'], 'http')
        ) {
            $post['post_image'] = $this->cacheExternalResources(
                $post['post_image'],
                $pluginConfiguration->getRecordUid()
            );
        }

        if (
            array_key_exists('comment', $post)
            && !empty($post['comment'])
        ) {
            $matches = [];
            if (
                preg_match_all('/<img.*?src=["|\'](?<src>.*?)["|\'].*?>/', $post['comment'], $matches)
                && array_key_exists('src', $matches)
                && is_array($matches['src'])
            ) {
                foreach ($matches['src'] as $uri) {
                    if (StringUtility::beginsWith($uri, 'http')) {
                        $post['comment'] = str_replace(
                            $matches['src'],
                            $this->cacheExternalResources($uri, $pluginConfiguration->getRecordUid()),
                            $post['comment']
                        );
                    }
                }
            }
            $post['html_comment'] = nl2br($post['comment']);
        }

        return $post;
    }

    protected function cacheExternalResources(string $resource, int $contentRecordUid): string
    {
        $pathParts = GeneralUtility::split_fileref(parse_url($resource, PHP_URL_PATH));
        $filePath = sprintf(
            '%s%s.%s',
            $this->getTargetDirectory($contentRecordUid),
            $pathParts['filebody'],
            $pathParts['fileext']
        );

        if (!file_exists($filePath)) {
            GeneralUtility::writeFile($filePath, GeneralUtility::getUrl($resource));
        }

        return PathUtility::getAbsoluteWebPath($filePath);
    }

    protected function getCreationText(int $creationTime): string
    {
        $currentTimestamp = (int)date('U');
        $diffInSeconds = $currentTimestamp - $creationTime;

        $creationDate = new \DateTime(date('c', $creationTime));
        $currentDate = new \DateTime(date('c', $currentTimestamp));
        $dateInterval = $currentDate->diff($creationDate);

        if ($diffInSeconds <= 60) {
            return LocalizationUtility::translate(
                'creationTime.seconds',
                'walls_io_proxy'
            );
        }

        if ($diffInSeconds <= 3600) {
            return LocalizationUtility::translate(
                'creationTime.minutes',
                'walls_io_proxy',
                [$dateInterval->format('%i')]
            );
        }

        if ($diffInSeconds <= 86400) {
            return LocalizationUtility::translate(
                'creationTime.hours',
                'walls_io_proxy',
                [$dateInterval->format('%h')]
            );
        }

        return LocalizationUtility::translate(
            'creationTime.date',
            'walls_io_proxy',
            [$creationDate->format('d.m.Y H:i')]
        );
    }

    protected function getTypo3Request(): ServerRequestInterface
    {
        return $this->request;
    }
}
