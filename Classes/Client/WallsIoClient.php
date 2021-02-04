<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Client;

use JWeiland\WallsIoProxy\Client\Request\RequestInterface;
use JWeiland\WallsIoProxy\Helper\MessageHelper;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This is the walls.io client which will send the request to the walls.io server
 */
class WallsIoClient
{
    /**
     * @var MessageHelper
     */
    protected $messageHelper;

    public function __construct(MessageHelper $messageHelper = null)
    {
        $this->messageHelper = $messageHelper ?? GeneralUtility::makeInstance(MessageHelper::class);
    }

    public function processRequest(RequestInterface $request): array
    {
        if (!$request->isValidRequest()) {
            $this->messageHelper->addFlashMessage(
                'URI is empty or contains invalid chars. URI: ' . $request->buildUri(),
                'Invalid request URI',
                FlashMessage::ERROR
            );
            return [];
        }

        $processedResponse = [];
        $clientReport = [];
        $response = GeneralUtility::getUrl($request->buildUri(), 0, null, $clientReport);
        $this->checkClientReportForErrors($clientReport);
        if (!$this->hasErrors()) {
            $processedResponse = json_decode($response, true);
            $this->checkResponseForErrors($processedResponse);
        }

        if ($this->hasErrors()) {
            $processedResponse = [];
        }

        return $processedResponse;
    }

    public function hasErrors(): bool
    {
        return $this->messageHelper->hasErrorMessages();
    }

    /**
     * @return FlashMessage[]
     */
    public function getErrors(): array
    {
        return $this->messageHelper->getErrorMessages();
    }

    /**
     * This method will only check the report of the client and not the result itself.
     *
     * @param array $clientReport
     */
    protected function checkClientReportForErrors(array $clientReport)
    {
        if (!empty($clientReport['message'])) {
            $this->messageHelper->addFlashMessage(
                $clientReport['message'],
                $clientReport['title'],
                $clientReport['severity']
            );
        }
    }

    /**
     * Check processed response from Google Maps Server for errors
     *
     * @param array|null $response
     */
    protected function checkResponseForErrors($response)
    {
        if ($response === null) {
            $this->messageHelper->addFlashMessage(
                'The response of walls.io was not a valid JSON response.',
                'Invalid JSON response',
                FlashMessage::ERROR
            );
        } elseif ($response['status'] !== 'success') {
            // SF: Haven't found an error request as example.
            // Correct following line, if you get one ;-)
            $this->messageHelper->addFlashMessage(
                implode($response['info']),
                $response['status'],
                FlashMessage::ERROR
            );
        }
    }
}
