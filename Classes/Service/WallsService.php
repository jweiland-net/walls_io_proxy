<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\Service;

/*
 * This file is part of the walls_io_proxy project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use JWeiland\WallsIoProxy\Client\WallsIoClient;
use JWeiland\WallsIoProxy\Client\WallsIoRequest;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller
 */
class WallsService
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WallsIoClient
     */
    protected $client;

    public function __construct(Registry $registry = null, WallsIoClient $client = null)
    {
        $this->registry = $registry ?? GeneralUtility::makeInstance(Registry::class);
        $this->client = $client ?? GeneralUtility::makeInstance(WallsIoClient::class, $this);
    }

    public function getWalls(int $wallId, int $entriesToLoad): array
    {
        $storedWall = $this->registry->get('WallsIoProxy', 'WallId_' . $wallId);
        if ($storedWall !== null) {
            return $this->getDataFromResult($storedWall, 3);
        }

        return $this->getEntries($wallId, $entriesToLoad);
    }

    protected function getEntries(int $wallId, int $entriesToLoad): array
    {
        $sessionId = $this->getSessionId($wallId);
        if ($sessionId) {
            $wallsIoEntryRequest = GeneralUtility::makeInstance(WallsIoRequest::class);
            $wallsIoEntryRequest->setWallId($wallId);
            $wallsIoEntryRequest->setSessionId($sessionId);
            $wallsIoEntryRequest->setEntriesToLoad($entriesToLoad);
            $wallsIoEntryRequest->setIncludeHeader(0);
            $response = $this->client->processRequest($wallsIoEntryRequest);

            if ($response->getBody()) {
                $data = $this->getDataFromResult($response->getBody(), 3);
                if (!empty($data)) {
                    $this->registry->set(
                        'WallsIoProxy',
                        'WallId_' . $wallId,
                        $response->getBody()
                    );
                    return $data;
                }
            }
        }

        return [
            'sessionId' => $sessionId,
            'errors' => $this->client->getError()
        ];
    }

    protected function getSessionId(int $wallId): string
    {
        $wallsIoSessionRequest = GeneralUtility::makeInstance(WallsIoRequest::class);
        $wallsIoSessionRequest->setWallId($wallId);
        $wallsIoSessionRequest->setIncludeHeader(1);
        $response = $this->client->processRequest($wallsIoSessionRequest);

        if (!$this->client->hasError() && $response->getBody()) {
            $data = $this->getDataFromResult($response->getBody(), 2);
            if (array_key_exists('sid', $data)) {
                return $data['sid'];
            }
        }

        return '';
    }

    protected function getDataFromResult(string $result, int $explodeParts): array
    {
        // Remove BOM
        if (strpos(bin2hex($result), 'efbbbf') === 0) {
            $result = substr($result, 3);
        }

        // Remove unwanted control chars from JSON String
        for ($i = 0; $i <= 31; ++$i) {
            $result = str_replace(chr($i), "", $result);
        }

        $parts = GeneralUtility::trimExplode(':', $result, true, $explodeParts);
        $jsonString = preg_replace('/^\d+/', '', $parts[$explodeParts - 1]);
        $data = json_decode($jsonString, true);
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
}
