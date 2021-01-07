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
use JWeiland\WallsIoProxy\Client\WallsIoRequest;
use TYPO3\CMS\Core\Core\Environment;
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
        $this->client = $client ?? GeneralUtility::makeInstance(WallsIoClient::class, $this);
    }

    public function getWalls(int $entriesToLoad): array
    {
        // First: Try to get fresh data
        $walls = $this->getEntries($entriesToLoad);

        // Second: If no data or request has errors, try to get old data from last response stored in sys_registry
        if (array_key_exists('error', $walls)) {
            DebuggerUtility::var_dump($walls['error']);
            $storedWall = $this->registry->get('WallsIoProxy', 'WallId_' . $this->wallId);
            if ($storedWall !== null) {
                $walls = $this->getDataFromResult($storedWall);
            }
        }

        return $walls;
    }

    protected function getEntries(int $entriesToLoad): array
    {
        $wallsIoEntryRequest = GeneralUtility::makeInstance(WallsIoRequest::class);
        $wallsIoEntryRequest->setWallId($this->wallId);
        $wallsIoEntryRequest->setEntriesToLoad($entriesToLoad);
        $wallsIoEntryRequest->setIncludeHeader(0);
        $response = $this->client->processRequest($wallsIoEntryRequest);

        if ($response->getBody()) {
            $data = $this->getDataFromResult($response->getBody());
            if (!empty($data)) {
                $this->registry->set(
                    'WallsIoProxy',
                    'WallId_' . $this->wallId,
                    $response->getBody()
                );
                return $data;
            }
        }

        return [
            'error' => $this->client->getError()
        ];
    }

    protected function getDataFromResult(string $result): array
    {
        $data = json_decode($result, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Walls.io works with a special Timestamp format
     * I have adapted the JS part into PHP
     *
     * function r(t) {
     *   var e = "";
     *   do e = s[t % a] + e, t = Math.floor(t / a); while (t > 0);
     *   return e
     * }
     *
     * @param int $timestamp
     * @return string
     */
    public function getFormattedTimestamp(int $timestamp): string
    {
        $chars = range(0, 9);
        array_push($chars, ...range('A', 'Z'));
        array_push($chars, ...range('a', 'z'));
        array_push($chars, ...['-', '_']);
        $amountOfChars = count($chars);
        $timestamp = $timestamp * 1000; // we need Microseconds
        $formattedTimestamp = '';
        do {
            $formattedTimestamp = $chars[$timestamp % $amountOfChars] . $formattedTimestamp;
            $timestamp = floor($timestamp / $amountOfChars);
        } while ($timestamp > 0);
        return $formattedTimestamp;
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
        return Environment::getPublicPath() . '/' . $this->targetDirectory . '/' . $this->wallId . '/';
    }
}
