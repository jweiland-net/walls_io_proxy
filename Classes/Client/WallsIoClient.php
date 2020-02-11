<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\Client;

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
 * Walls IO Client
 */
class WallsIoClient
{
    /**
     * @var string
     */
    protected $wallsUri = 'https://walls.io/socket.io/';

    /**
     * Needed to catch Cookie information
     *
     * @var array
     */
    protected $headersOfPreviousRequest = [];

    /**
     * @var array
     */
    protected $error = [];

    public function processRequest(WallsIoRequest $request)
    {
        // Remove previous request error
        $this->error = [];

        $result = GeneralUtility::getUrl(
            $this->buildWallsUri($request),
            $request->getIncludeHeader(),
            $this->getHeaders(),
            $this->error
        );

        $response = GeneralUtility::makeInstance(WallsIoResponse::class);

        if ($request->getIncludeHeader()) {
            // Explode 2 \r\n to separate header from body
            list($header, $body) = GeneralUtility::trimExplode(
                chr(13) . chr(10) . chr(13) . chr(10),
                $result
            );

            $this->storeResponseHeaderForNextRequest($header);

            $response->setHeader($header);
            $response->setBody($body);
        } else {
            $response->setBody($result);
        }

        return $response;
    }

    public function hasError()
    {
        return !empty($this->error['message']);
    }

    public function getError()
    {
        if (!empty($this->error['message'])) {
            return $this->error;
        }
        return [];
    }

    /**
     * Store all header data.
     * Helpful to extract Cookie information for next request
     *
     * @param string $header
     */
    protected function storeResponseHeaderForNextRequest(string $header)
    {
        foreach (GeneralUtility::trimExplode(chr(10), $header) as $headerData) {
            $headerParts = GeneralUtility::trimExplode(':', $headerData, true, 2);
            $this->headersOfPreviousRequest[$headerParts[0]] = $headerParts[1];
        }
    }

    protected function buildWallsUri(WallsIoRequest $request): string
    {
        return rtrim($this->wallsUri, '?/') . '/?' . http_build_query($this->getWallsUriParameters($request));
    }

    protected function getWallsUriParameters(WallsIoRequest $request): array
    {
        $wallsUriParameters = [
            'wallId' => (string)$request->getWallId(),
            'client' => 'wallsio-frontend',
            'cookieSupport' => (string)(int)$request->getUseCookies(),
            'network' => '',
            'EIO' => '3',
            'transport' => 'polling',
            't' => $this->getFormattedTimestamp()
        ];

        if ($request->getEntriesToLoad()) {
            $wallsUriParameters['initialCheckins'] = (string)$request->getEntriesToLoad();
        }

        if ($request->getUseBinarySupport() === false) {
            $wallsUriParameters['b64'] = '1';
        }

        if ($request->getSessionId()) {
            $wallsUriParameters['sid'] = $request->getSessionId();
        }

        return $wallsUriParameters;
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

    protected function getCookie()
    {
        $cookies = [];
        if (array_key_exists('Set-Cookie', $this->headersOfPreviousRequest)) {
            foreach (GeneralUtility::trimExplode(',', $this->headersOfPreviousRequest['Set-Cookie']) as $cookie) {
                $cookieParts = GeneralUtility::trimExplode(';', $cookie);
                $cookies[] = current($cookieParts);
            }
        }
        return implode(';', $cookies);
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
