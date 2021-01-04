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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to retrieve result from WallsIO, decode the result and store entries into Cache
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
        // First: Try to get fresh data
        $walls = $this->getEntries($wallId, $entriesToLoad);

        // Second: If no data or request has errors, try to get old data from last response stored in sys_registry
        if (array_key_exists('error', $walls)) {
            $storedWall = $this->registry->get('WallsIoProxy', 'WallId_' . $wallId);
            if ($storedWall !== null) {
                $walls = $this->getDataFromResult($storedWall);
            }
        }

        return $walls;
    }

    protected function getEntries(int $wallId, int $entriesToLoad): array
    {
        $sessionId = $this->getSessionId($wallId, $entriesToLoad);
        if ($sessionId) {
            $wallsIoEntryRequest = GeneralUtility::makeInstance(WallsIoRequest::class);
            $wallsIoEntryRequest->setWallId($wallId);
            $wallsIoEntryRequest->setSessionId($sessionId);
            $wallsIoEntryRequest->setEntriesToLoad($entriesToLoad);
            $wallsIoEntryRequest->setIncludeHeader(0);
            $response = $this->client->processRequest($wallsIoEntryRequest);

            if ($response->getBody()) {
                $data = $this->getDataFromResult($response->getBody());
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
            'error' => $this->client->getError()
        ];
    }

    protected function getSessionId(int $wallId, int $entriesToLoad): string
    {
        $wallsIoSessionRequest = GeneralUtility::makeInstance(WallsIoRequest::class);
        $wallsIoSessionRequest->setWallId($wallId);
        $wallsIoSessionRequest->setEntriesToLoad($entriesToLoad);
        $wallsIoSessionRequest->setIncludeHeader(1);
        $response = $this->client->processRequest($wallsIoSessionRequest);

        if (!$this->client->hasError() && $response->getBody()) {
            $data = $this->getDataFromResult($response->getBody());
            if (array_key_exists('sid', $data)) {
                return $data['sid'];
            }
        }

        return '';
    }

    protected function getDataFromResult(string $result): array
    {
        // Remove BOM
        if (strpos(bin2hex($result), 'efbbbf') === 0) {
            $result = substr($result, 3);
        }

        // Remove unwanted control chars from JSON String
        for ($i = 0; $i <= 31; ++$i) {
            $result = str_replace(chr($i), '', $result);
        }

        $jsonMatches = $this->getJsonMatchesFromResult($result);
        if (empty($jsonMatches)) {
            return [];
        }

        $data = json_decode(trim($jsonMatches[0], '0123456789:'), true);
        return is_array($data) ? $data : [];
    }

    protected function getJsonMatchesFromResult(string $result)
    {
        $matches = [];
        $jsonMatches = [];
        if (preg_match_all('/\d+:\d+(?:\{|\[)/', $result, $matches, PREG_OFFSET_CAPTURE)) {
            $jsonLength = mb_strlen($result);
            $matches = $matches[0];
            $positionIndex = 0;
            $match = 0;
            while ($positionIndex < $jsonLength) {
                $offsetOfNextMatch = $matches[$match + 1][1] ?? $jsonLength + 1;
                $jsonMatches[] = mb_substr($result, $positionIndex, $offsetOfNextMatch - $positionIndex);
                $positionIndex = $offsetOfNextMatch;
                $match++;
            }
        }
        return $jsonMatches;
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
