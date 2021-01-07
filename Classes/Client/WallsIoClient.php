<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Client;

use JWeiland\WallsIoProxy\Service\WallsService;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use React\Socket\Connector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is the walls.io client which will send the request to the walls.io server
 */
class WallsIoClient
{
    /**
     * @var string
     */
    protected $wallsUri = 'wss://broadcaster.walls.io:443/socket.io/';

    /**
     * @var WallsService
     */
    protected $wallsService;

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
        $wallsIoResponse = GeneralUtility::makeInstance(WallsIoResponse::class);

        if (!$this->isValidRequest($request)) {
            return $wallsIoResponse;
        }

        $loop = Factory::create();
        $reactConnector = new Connector(
            $loop,
            [
                'dns' => '8.8.8.8',
                'timeout' => 6,
            ]
        );

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);
        $connector($this->buildWallsUri($request), ['protocol1', 'subprotocol2'], $this->getHeaders())
            ->then(function (WebSocket $conn) use ($wallsIoResponse) {
                $conn->on('message', function (\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn, $wallsIoResponse) {
                    $matches = [];
                    if (preg_match('/(?<json>[\{|\[].*[\]|\}])/', $msg->getContents(), $matches)) {
                        if (isset($matches['json']) && !empty($matches['json'])) {
                            $data = json_decode($matches['json'], true);
                            if (!empty($data)) {
                                if (array_key_exists(0, $data) && $data[0] === 'new checkins') {
                                    $wallsIoResponse->setBody($matches['json']);
                                    $conn->close();
                                }
                            }
                        }
                    }
                });
            }, function (\Exception $e) use ($loop) {
                $this->error = [
                    'message' => 'An error occurred while retrieving data from walls.io',
                    'title' => 'Error from walls.io: ' . $e->getMessage()
                ];
                $loop->stop();
            });

        $loop->run();

        return $wallsIoResponse;
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

    public function hasError(): bool
    {
        return !empty($this->error['message']);
    }

    public function getError(): array
    {
        if (!empty($this->error['message'])) {
            return $this->error;
        }
        return [];
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
            'network' => '',
            'EIO' => '3',
            'transport' => 'websocket',
            't' => $this->getTimestamp()
        ];

        if ($request->getEntriesToLoad()) {
            $wallsUriParameters['initialCheckins'] = (string)$request->getEntriesToLoad();
        }

        if ($request->getUseBinarySupport() === false) {
            $wallsUriParameters['b64'] = '1';
        }

        return $wallsUriParameters;
    }

    public function getTimestamp(): string
    {
        return $this->wallsService->getFormattedTimestamp((int)date('U'));
    }

    protected function getHeaders(): array
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

        return $headers;
    }
}
