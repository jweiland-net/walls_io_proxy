<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Client;

use JWeiland\WallsIoProxy\Request\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * This is the walls.io client which will send the request to the walls.io server
 */
class WallsIoClient
{
    protected RequestFactory $requestFactory;

    protected LoggerInterface $logger;

    public function __construct(
        RequestFactory $requestFactory,
        LoggerInterface $logger
    ) {
        $this->requestFactory = $requestFactory;
        $this->logger = $logger;
    }

    /**
     * @return array<string, mixed>
     */
    public function processRequest(RequestInterface $request): array
    {
        if (!$request->isValidRequest()) {
            $this->logger->error(
                'Request URI is empty or contains invalid chars.',
                [
                    'uri' => $request->buildUri(),
                ]
            );

            return [];
        }

        $processedResponse = [];
        try {
            $response = $this->requestFactory->request($request->buildUri());

            if (!$this->checkClientResponseForErrors($response)) {
                $processedResponse = json_decode((string)$response->getBody(), true);
                if ($this->hasResponseErrors($processedResponse)) {
                    $processedResponse = [];
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                str_replace($request->getParameter('access_token'), 'XXX', $exception->getMessage()),
                [
                    'Exception Code' => $exception->getCode(),
                ]
            );
        }

        return $processedResponse;
    }

    /**
     * This method will only check the report of the client and not the result itself.
     *
     * @return bool Returns false, if no errors were found
     */
    protected function checkClientResponseForErrors(ResponseInterface $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            $this->logger->error(
                'Walls.io responses with a status code different from 200',
                [
                    'Status Code' => $response->getStatusCode(),
                ]
            );
            return true;
        }

        return false;
    }

    /**
     * Check processed response from walls.io for errors
     *
     * @param array<string, mixed>|null $response
     */
    protected function hasResponseErrors(array $response = null): bool
    {
        if ($response === null) {
            $this->logger->error('The response of walls.io was not a valid JSON response.');

            return true;
        }

        if ($response['status'] !== 'success') {
            $this->logger->error(
                implode('', $response['info']),
                [
                    'status' => $response['status'],
                ]
            );

            return true;
        }

        return false;
    }
}
