<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Client;

use JWeiland\WallsIoProxy\Helper\MessageHelper;
use JWeiland\WallsIoProxy\Request\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;

/**
 * This is the walls.io client which will send the request to the walls.io server
 */
class WallsIoClient
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var MessageHelper
     */
    protected $messageHelper;

    public function __construct(RequestFactory $requestFactory, MessageHelper $messageHelper)
    {
        $this->requestFactory = $requestFactory;
        $this->messageHelper = $messageHelper;
    }

    public function processRequest(RequestInterface $request): array
    {
        if (!$request->isValidRequest()) {
            $this->messageHelper->addFlashMessage(
                'URI is empty or contains invalid chars. URI: ' . $request->buildUri(),
                'Invalid request URI',
                AbstractMessage::ERROR
            );

            return [];
        }

        $processedResponse = [];
        try {
            $response = $this->requestFactory->request($request->buildUri());
            $this->checkClientResponseForErrors($response);

            if (!$this->hasErrors()) {
                $processedResponse = json_decode((string)$response->getBody(), true);
                if ($this->hasResponseErrors($processedResponse)) {
                    $processedResponse = [];
                }
            }
        } catch (\Exception $exception) {
            $this->messageHelper->addFlashMessage(
                str_replace($request->getParameter('access_token'), 'XXX', $exception->getMessage()),
                'Error Code: ' . $exception->getCode(),
                AbstractMessage::ERROR
            );
        }

        return $processedResponse;
    }

    public function hasErrors(): bool
    {
        return $this->messageHelper->hasErrorMessages();
    }

    /**
     * This method will only check the report of the client and not the result itself.
     */
    protected function checkClientResponseForErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            $this->messageHelper->addFlashMessage(
                'Walls.io responses with a status code different from 200',
                'Status Code: ' . $response->getStatusCode(),
                AbstractMessage::ERROR
            );
        }
    }

    /**
     * Check processed response from walls.io for errors
     *
     * @param array|null $response
     */
    protected function hasResponseErrors(array $response = null): bool
    {
        if ($response === null) {
            $this->messageHelper->addFlashMessage(
                'The response of walls.io was not a valid JSON response.',
                'Invalid JSON response',
                AbstractMessage::ERROR
            );

            return true;
        }

        if ($response['status'] !== 'success') {
            // SF: Haven't found an error request as example.
            // Correct following line, if you get one ;-)
            $this->messageHelper->addFlashMessage(
                implode($response['info']),
                $response['status'],
                AbstractMessage::ERROR
            );

            return true;
        }

        return false;
    }
}
