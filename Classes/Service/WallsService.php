<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Service;

use JWeiland\WallsIoProxy\Client\Request\PostsRequest;
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
    protected $wallId = 0;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WallsIoClient
     */
    protected $client;

    public function __construct(int $wallsId, Registry $registry = null, WallsIoClient $client = null)
    {
        $this->wallId = $wallsId;
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
     *
     * @param int $maxPosts
     * @return array
     */
    public function getWallPosts(int $maxPosts): array
    {
        $wallPosts = [];
        $requestedWallPosts = [];
        $lastPostId = '';
        $hasError = false;

        while (count($wallPosts) < $maxPosts) {
            if ($requestedWallPosts === []) {
                $requestedWallPosts = $this->getUncachedPostsFromWallsIO(8, $lastPostId);

                // if request has errors, try to get old data from last response stored in sys_registry
                // Return old wall entries and break current loop on failure
                if (
                    array_key_exists('hasErrors', $requestedWallPosts)
                    && $requestedWallPosts['hasErrors'] === true
                ) {
                    $hasError = true;
                    $storedWallPosts = $this->registry->get(
                        'WallsIoProxy',
                        'WallId_' . $this->wallId
                    );
                    $wallPosts = $storedWallPosts ?? [];
                    break;
                }
            }

            $requestedWall = array_shift($requestedWallPosts);

            // Prevent adding/duplicate wall posts, which are already posted on other social media services
            if ($requestedWall['is_crosspost'] === true) {
                continue;
            }

            if (is_array($requestedWall)) {
                $sanitizedWallPost = $this->getSanitizedPost($requestedWall);
                $lastPostId = $sanitizedWallPost['id'];
                $wallPosts[$sanitizedWallPost['id']] = $sanitizedWallPost;
            }
        }

        if ($hasError === false && !empty($wallPosts)) {
            $this->registry->set(
                'WallsIoProxy',
                'WallId_' . $this->wallId,
                $wallPosts
            );
        }

        return $wallPosts;
    }

    /**
     * @param int $entriesToLoad
     * @param string $beforePostId Load posts before this post ID. Could be used as offset for pagination. As it is a very huge int value we use string here to prevent problems on 32bit systems. String is also supported by API
     * @return array
     */
    protected function getUncachedPostsFromWallsIO(int $entriesToLoad = 8, string $beforePostId = ''): array
    {
        $wallsIoPostRequest = GeneralUtility::makeInstance(PostsRequest::class);
        $wallsIoPostRequest->setFields($this->fields);
        $wallsIoPostRequest->setLimit($entriesToLoad);
        $wallsIoPostRequest->setBefore($beforePostId);
        $response = $this->client->processRequest($wallsIoPostRequest);

        if (
            is_array($response)
            && array_key_exists('status', $response)
            && $response['status'] === 'success'
            && array_key_exists('data', $response)
            && is_array($response['data'])
            && !empty($response['data'])
        ) {
            return $response['data'];
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
        if ($this->wallId) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->remove('WallsIoProxy', 'WallId_' . $this->wallId);

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

        return $publicPath . '/' . $this->targetDirectory . '/' . $this->wallId . '/';
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
        if (!is_dir($this->targetDirectory)) {
            GeneralUtility::mkdir_deep($this->targetDirectory);
        }

        $pathParts = GeneralUtility::split_fileref(parse_url($resource, PHP_URL_PATH));
        $filePath = sprintf(
            '%s%s.%s',
            $this->targetDirectory,
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
