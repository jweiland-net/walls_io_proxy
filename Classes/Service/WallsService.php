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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller
 */
class WallsService
{
    /**
     * @var string
     */
    protected $wallsUri = 'https://walls.io/socket.io/';

    /**
     * @var int
     */
    protected $wallId = 0;

    /**
     * @var int
     */
    protected $entriesToLoad = 0;

    /**
     * It's really hard to interpret walls.io binary support.
     * Keep this value disabled to switch over to base64.
     * We don't support binary requests!
     *
     * @var bool
     */
    protected $useBinarySupport = false;

    /**
     * Keep this value enabled, as walls.io needs the Cookie information of first request
     * in second/further request, too.
     *
     * @var bool
     */
    protected $useCookies = true;

    /**
     * Header of first request.
     * Needed to extract cookie
     *
     * @var array
     */
    protected $header = [];

    public function getWalls(int $wallId, int $entriesToLoad): array
    {
        $this->wallId = $wallId;
        $this->entriesToLoad = $entriesToLoad;
        return $this->getEntries(
            $this->getSessionId()
        );
    }

    protected function getSessionId(): string
    {
        $errors = [];
        $result = GeneralUtility::getUrl(
            $this->buildWallsUri(),
            1,
            $this->getHeaders(),
            $errors
        );

        // Explode 2 \r\n to separate header from body
        list($header, $body) = GeneralUtility::trimExplode(chr(13) . chr(10) . chr(13) . chr(10), $result);
        foreach (GeneralUtility::trimExplode(chr(10), $header) as $headerData) {
            $headerParts = GeneralUtility::trimExplode(':', $headerData, true, 2);
            $this->header[$headerParts[0]] = $headerParts[1];
        }

        if (is_string($body)) {
            $data = $this->getDataFromResult($body, 2);
            if (array_key_exists('sid', $data)) {
                return $data['sid'];
            }
        }
        return '';
    }

    protected function getEntries(string $sessionId): array
    {
        $errors = [];
        $result = GeneralUtility::getUrl(
            $this->buildWallsUri($sessionId),
            0,
            $this->getHeaders(),
            $errors
        );

        if (is_string($result)) {
            $data = $this->getDataFromResult($result, 3);
            if (!empty($data)) {
                return $data;
            }
        }

        return [
            'sessionId' => $sessionId,
            'errors' => $errors
        ];
    }

    protected function getHeaders()
    {
        $headers = [
            'accept' => '*/*',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' =>'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7,so;q=0.6',
            'cache-control' => 'no-cache',
            'dnt' => '1',
            'pragma' => 'no-cache',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36'
        ];

        $cookie = $this->getCookie();
        if (!empty($cookie)) {
            $headers['cookie'] = $cookie;
        }

        return $headers;
    }

    protected function buildWallsUri(string $sessionId = ''): string
    {
        return rtrim($this->wallsUri, '?/') . '/?' . http_build_query($this->getWallsUriParameters($sessionId));
    }

    protected function getWallsUriParameters(string $sessionId): array
    {
        $wallsUriParameters = [
            'wallId' => (string)$this->wallId,
            'client' => 'wallsio-frontend',
            'network' => '',
            'EIO' => '3',
            'transport' => 'polling'
        ];

        if ($this->entriesToLoad) {
            $wallsUriParameters['initialCheckins'] = (string)$this->entriesToLoad;
        }

        if ($this->useBinarySupport === false) {
            $wallsUriParameters['b64'] = '1';
        }

        if ($sessionId) {
            $wallsUriParameters['sid'] = $sessionId;
        }

        return $wallsUriParameters;
    }

    protected function getCookie()
    {
        $cookies = [];
        if (array_key_exists('Set-Cookie', $this->header)) {
            foreach (GeneralUtility::trimExplode(',', $this->header['Set-Cookie']) as $cookie) {
                $cookieParts = GeneralUtility::trimExplode(';', $cookie);
                $cookies[] = current($cookieParts);
            }
        }
        return implode(';', $cookies);
    }

    protected function getDataFromResult(string $result, int $explodeParts): array
    {
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
     * @return string
     */
    protected function getFormattedTimestamp(): string
    {
        $chars = range(0, 9);
        array_push($chars, ...range('A', 'Z'));
        array_push($chars, ...range('a', 'z'));
        array_push($chars, ...['-', '_']);
        $amountOfChars = count($chars);
        $timestamp = date('U') * 1000; // we need Microseconds
        $formattedTimestamp = '';
        do {
            $formattedTimestamp = $chars[$timestamp % $amountOfChars] . $formattedTimestamp;
            $timestamp = floor($timestamp / $amountOfChars);
        } while ($timestamp > 0);
        return $formattedTimestamp;
    }
}
