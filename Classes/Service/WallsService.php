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
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

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

    public function getWalls(int $entriesToLoad): array
    {
        // First: Try to get fresh data
        $walls = $this->getEntries($entriesToLoad);

        // Second: If no data or request has errors, try to get old data from last response stored in sys_registry
        if (array_key_exists('error', $walls) || empty($walls['data'])) {
            DebuggerUtility::var_dump($walls);
            $storedWall = $this->registry->get('WallsIoProxy', 'WallId_' . $this->wallId);
            if ($storedWall !== null) {
                $walls = $storedWall['data'];
            }
        }

        return $walls;
    }

    protected function getEntries(int $entriesToLoad): array
    {
        $wallsIoPostRequest = GeneralUtility::makeInstance(PostsRequest::class);
        $wallsIoPostRequest->setFields(['id', 'comment', 'type', 'created_timestamp', 'external_image', 'post_image']);
        $wallsIoPostRequest->setLimit($entriesToLoad);
        $response = $this->client->processRequest($wallsIoPostRequest);

        if (!empty($response['data'])) {
            $data = $response['data'];
            if (!empty($data)) {
                $this->registry->set(
                    'WallsIoProxy',
                    'WallId_' . $this->wallId,
                    $response
                );
                return $data;
            }
        }

        return [
            'error' => $this->client->getError()
        ];
    }

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

    public function getWallId(): int
    {
        return $this->wallId;
    }

    public function getTargetDirectory(): string
    {
        if (version_compare(TYPO3_branch, '9.2', '>=')) {
            $publicPath = \TYPO3\CMS\Core\Core\Environment::getPublicPath();
        } else {
            $publicPath = rtrim(PATH_site, '/');
        }

        return $publicPath . '/' . $this->targetDirectory . '/' . $this->wallId . '/';
    }
}
