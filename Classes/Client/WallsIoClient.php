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
<<<<<<< HEAD
=======
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
>>>>>>> 610ce82 ([TASK] Replaced ERROR and notices namespaces)

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

    public function processRequest(RequestInterface $request): array
    {
        if (!$request->isValidRequest()) {
<<<<<<< HEAD
            $this->logger->error(
                'Request URI is empty or contains invalid chars.',
                [
                    'uri' => $request->buildUri(),
                ]
=======
            $this->messageHelper->addFlashMessage(
                'URI is empty or contains invalid chars. URI: ' . $request->buildUri(),
                'Invalid request URI',
                ContextualFeedbackSeverity::ERROR
>>>>>>> 610ce82 ([TASK] Replaced ERROR and notices namespaces)
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
<<<<<<< HEAD
                [
                    'Exception Code' => $exception->getCode(),
                ]
=======
                'Error Code: ' . $exception->getCode(),
                ContextualFeedbackSeverity::ERROR
>>>>>>> 610ce82 ([TASK] Replaced ERROR and notices namespaces)
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
<<<<<<< HEAD
                [
                    'Status Code' => $response->getStatusCode(),
                ]
=======
                'Status Code: ' . $response->getStatusCode(),
                ContextualFeedbackSeverity::ERROR
>>>>>>> 610ce82 ([TASK] Replaced ERROR and notices namespaces)
            );
            return true;
        }

        return false;
    }

    /**
     * Check processed response from walls.io for errors
     *
     * @param array|null $response
     */
    protected function hasResponseErrors(array $response = null): bool
    {
        if ($response === null) {
<<<<<<< HEAD
            $this->logger->error('The response of walls.io was not a valid JSON response.');
=======
            $this->messageHelper->addFlashMessage(
                'The response of walls.io was not a valid JSON response.',
                'Invalid JSON response',
                ContextualFeedbackSeverity::ERROR
            );
>>>>>>> 610ce82 ([TASK] Replaced ERROR and notices namespaces)

            return true;
        }

        if ($response['status'] !== 'success') {
            $this->logger->error(
                implode($response['info']),
<<<<<<< HEAD
                [
                    'status' => $response['status'],
                ]
=======
                $response['status'],
                ContextualFeedbackSeverity::ERROR
>>>>>>> 610ce82 ([TASK] Replaced ERROR and notices namespaces)
            );

            return true;
        }

        return false;
    }
}
