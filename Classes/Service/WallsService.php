<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Service;

use JWeiland\WallsIoProxy\Client\Request\Posts\ChangedRequest;
use JWeiland\WallsIoProxy\Client\WallsIoClient;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Service to retrieve result from WallsIO, decode the result and store entries into Cache
 */
class WallsService
{
    /**
     * @var string
     */
    protected $targetDirectory = 'typo3temp/assets/walls_io_proxy';

    /**
     * Fields to get from the API
     *
     * @var array
     */
    protected $fields = [
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
        'post_image'
    ];

    /**
     * @var int
     */
    protected $contentRecordUid = 0;

    /**
     * Can be empty in case of "Clear->cache"
     *
     * @var string
     */
    protected $accessToken = '';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WallsIoClient
     */
    protected $client;

    public function __construct(
        int $contentRecordUid,
        string $accessToken = '',
        Registry $registry = null,
        WallsIoClient $client = null
    ) {
        $this->contentRecordUid = $contentRecordUid;
        $this->accessToken = $accessToken;
        $this->registry = $registry ?? GeneralUtility::makeInstance(Registry::class);
        $this->client = $client ?? GeneralUtility::makeInstance(WallsIoClient::class);
    }

    /**
     * Get $maxPosts wall posts.
     *
     * As there is no filter to get only posts where is_crosspost is false, we have to partly fill the wall posts
     * array. We load 8 records from API again and again until $maxPosts is reached.
     *
     * If any request results in error, we will return cached wall posts immediately.
     */
    public function getWallPosts(int $maxPosts, int $since): array
    {
        $wallPosts = [];
        $since = time() - (60 * 60 * 24 * $since);
        $hasError = false;
        $wallsIoRequest = $this->getUncachedRequestFromWallsIO($maxPosts, $since);
        $requestedWallPosts = $wallsIoRequest['data'];

        if (
            $requestedWallPosts === []
            || (
                array_key_exists('hasErrors', $requestedWallPosts)
                && $requestedWallPosts['hasErrors'] === true
            )
        ) {
            $hasError = true;
            $storedWallPosts = $this->registry->get(
                'WallsIoProxy',
                'ContentRecordUid_' . $this->contentRecordUid
            );
            $wallPosts = $storedWallPosts ?? [];
        } else {
            foreach ($requestedWallPosts as $requestedWallPost) {
                if (is_array($requestedWallPost)) {
                    $sanitizedWallPost = $this->getSanitizedPost($requestedWallPost);
                    $wallPosts[$sanitizedWallPost['id']] = $sanitizedWallPost;
                }
            }
        }

        if ($hasError === false && !empty($wallPosts)) {
            $this->registry->set(
                'WallsIoProxy',
                'ContentRecordUid_' . $this->contentRecordUid,
                $wallPosts
            );
        }

        return $wallPosts;
    }

    /**
     * @param int $entriesToLoad
     * @param int $since // Initially time(). Use it for pagination. Take current_time from last request as new time for $since
     * @return array
     */
    protected function getUncachedRequestFromWallsIO(int $entriesToLoad = 8, int $since = 0): array
    {
        $wallsIoPostRequest = GeneralUtility::makeInstance(ChangedRequest::class);
        $wallsIoPostRequest->setAccessToken($this->accessToken);
        $wallsIoPostRequest->setSince($since ?: time());
        $wallsIoPostRequest->setFields($this->fields);
        $wallsIoPostRequest->setLimit($entriesToLoad);
        $response = $this->client->processRequest($wallsIoPostRequest);

        if (
            is_array($response)
            && array_key_exists('status', $response)
            && $response['status'] === 'success'
            && array_key_exists('data', $response)
            && is_array($response['data'])
            && array_key_exists('current_time', $response)
        ) {
            return $response;
        }

        return [
            'hasErrors' => true
        ];
    }

    /**
     * Clear cache for a specific wall ID.
     * Will be called by Clear Cache post hook.
     *
     * @return int
     */
    public function clearCache(): int
    {
        if ($this->contentRecordUid) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->remove('WallsIoProxy', 'ContentRecordUid_' . $this->contentRecordUid);

            GeneralUtility::flushDirectory($this->getTargetDirectory());

            return 1;
        }
        return 0;
    }

    /**
     * Get cache directory for related files within the content of the wall posts comments.
     * Will be called by the AddWallsProcessor (DataProcessor)
     *
     * @return string
     */
    public function getTargetDirectory(): string
    {
        if (version_compare(TYPO3_branch, '9.2', '>=')) {
            $publicPath = \TYPO3\CMS\Core\Core\Environment::getPublicPath();
        } else {
            $publicPath = rtrim(PATH_site, '/');
        }

        $targetDirectory = $publicPath . '/' . $this->targetDirectory . '/' . $this->contentRecordUid . '/';

        if (!is_dir($targetDirectory)) {
            GeneralUtility::mkdir_deep($targetDirectory);
        }

        return $targetDirectory;
    }

    protected function getSanitizedPost(array $post): array
    {
        if (array_key_exists('created_timestamp', $post)) {
            $post['created_timestamp_as_text'] = $this->getCreationText((int)$post['created_timestamp']);
        }

        if (
            array_key_exists('external_image', $post)
            && StringUtility::beginsWith((string)$post['external_image'], 'http')
        ) {
            $post['external_image'] = $this->cacheExternalResources($post['external_image']);
        }

        if (
            array_key_exists('post_image', $post)
            && StringUtility::beginsWith((string)$post['post_image'], 'http')
        ) {
            $post['post_image'] = $this->cacheExternalResources($post['post_image']);
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
                            $this->cacheExternalResources($uri),
                            $post['comment']
                        );
                    }
                }
            }
            $post['html_comment'] = nl2br($post['comment']);
        }

        return $post;
    }

    protected function cacheExternalResources(string $resource): string
    {
        $pathParts = GeneralUtility::split_fileref(parse_url($resource, PHP_URL_PATH));
        $filePath = sprintf(
            '%s%s.%s',
            $this->getTargetDirectory(),
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
        if ($diffInSeconds > 60 && $diffInSeconds <= 3600) {
            return LocalizationUtility::translate(
                'creationTime.minutes',
                'walls_io_proxy',
                [$dateInterval->format('%i')]
            );
        }
        if ($diffInSeconds > 3600 && $diffInSeconds <= 86400) {
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
}
