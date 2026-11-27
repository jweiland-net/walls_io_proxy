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
 * This is the walls.io client that will send the request to the walls.io server
 */
final readonly class WallsIoClient
{
    private const REDACTED_TOKEN = 'XXX';

    public function __construct(
        private RequestFactory $requestFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function processRequest(RequestInterface $request): array
    {
        if (!$request->isValidRequest()) {
            $this->logger->error(
                'Request URI is empty or contains invalid chars.',
                [
                    'uri' => $this->redactAccessToken($request, $request->buildUri()),
                ],
            );

            return [];
        }

        try {
            $response = $this->requestFactory->request($request->buildUri());
        } catch (\Exception $exception) {
            $this->logger->error(
                $this->redactAccessToken($request, $exception->getMessage()),
                [
                    'exceptionCode' => $exception->getCode(),
                ],
            );

            return [];
        }

        if ($this->hasClientResponseErrors($response)) {
            return [];
        }

        $processedResponse = json_decode((string)$response->getBody(), true);
        if (!is_array($processedResponse)) {
            $this->logger->error('The response of walls.io was not a valid JSON response.');

            return [];
        }

        if ($this->hasResponseErrors($processedResponse)) {
            return [];
        }

        return $processedResponse;
    }

    /**
     * This method will only check the report of the client and not the result itself.
     *
     * @return bool Returns true if errors were found
     */
    private function hasClientResponseErrors(ResponseInterface $response): bool
    {
        if ($response->getStatusCode() === 200) {
            return false;
        }

        $this->logger->error(
            'Walls.io responses with a status code different from 200',
            [
                'statusCode' => $response->getStatusCode(),
            ],
        );

        return true;
    }

    /**
     * Check the processed response from walls.io for errors
     *
     * @param array<mixed> $response
     */
    private function hasResponseErrors(array $response): bool
    {
        if (($response['status'] ?? '') === 'success') {
            return false;
        }

        $info = $response['info'] ?? [];
        $message = is_array($info) ? implode('', array_filter($info, is_scalar(...))) : '';
        $this->logger->error(
            $message !== '' ? $message : 'Unknown error',
            [
                'status' => $response['status'] ?? 'unknown',
            ],
        );

        return true;
    }

    /**
     * The access token is part of the URI and may be part of exception messages.
     * It must never end up in log files.
     */
    private function redactAccessToken(RequestInterface $request, string $value): string
    {
        if (!$request->hasParameter('access_token')) {
            return $value;
        }

        $accessToken = (string)$request->getParameter('access_token');
        if ($accessToken === '') {
            return $value;
        }

        return str_replace(
            [$accessToken, rawurlencode($accessToken), urlencode($accessToken)],
            self::REDACTED_TOKEN,
            $value,
        );
    }
}
