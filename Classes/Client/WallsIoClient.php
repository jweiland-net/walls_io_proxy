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

use JWeiland\WallsIoProxy\Service\WallsService;
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
     * @var WallsService
     */
    protected $wallsService;

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

    public function __construct(WallsService $wallsService)
    {
        $this->wallsService = $wallsService;
    }

    public function processRequest(WallsIoRequest $request): WallsIoResponse
    {
        $this->error = [];
        $response = GeneralUtility::makeInstance(WallsIoResponse::class);

        if (!$this->isValidRequest($request)) {
            return $response;
        }

        $result = GeneralUtility::getUrl(
            $this->buildWallsUri($request),
            $request->getIncludeHeader(),
            $this->getHeaders(),
            $this->error
        );

        if ($this->hasError()) {
            return $response;
        }

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

    protected function isValidRequest(WallsIoRequest $request): bool
    {
        if (!$request->getWallId()) {
            $this->error = [
                'message' => 'You have forgotten to define a Wall ID in Plugin Configuration',
                'title' => 'Missing mandatory Wall ID in Request'
            ];
            return false;
        }
        return true;
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
            't' => $this->wallsService->getFormattedTimestamp((int)date('U'))
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
}
