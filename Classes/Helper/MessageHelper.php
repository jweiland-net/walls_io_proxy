<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Helper;

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains methods to create FlashMessage.
 * Further we will implement a central position for Logging
 */
class MessageHelper
{
    /**
     * @var FlashMessageService
     */
    protected $flashMessageService;

    public function __construct(FlashMessageService $flashMessageService)
    {
        $this->flashMessageService = $flashMessageService;
    }

    public function addFlashMessage(string $message, string $title = '', int $severity = ContextualFeedbackSeverity::OK): void
    {
        // We activate storeInSession, so that messages can be displayed when click on Save&Close button.
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $severity,
            true
        );

        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    /**
     * @return FlashMessage[]
     */
    public function getAllFlashMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessageQueue()->getAllMessagesAndFlush();
        }

        return $this->getFlashMessageQueue()->getAllMessages();
    }

    public function hasMessages(): bool
    {
        return !empty($this->getAllFlashMessages(false));
    }

    /**
     * @param int $severity Must be one of the constants in AbstractMessage class
     * @return FlashMessage[]
     */
    protected function getFlashMessagesBySeverity(int $severity): array
    {
        return $this->getFlashMessageQueue()->getAllMessages($severity);
    }

    /**
     * @param int $severity Must be one of the constants in AbstractMessage class
     * @return FlashMessage[]
     */
    public function getFlashMessagesBySeverityAndFlush(int $severity): array
    {
        return $this->getFlashMessageQueue()->getAllMessagesAndFlush($severity);
    }

    public function hasErrorMessages(): bool
    {
        return !empty($this->getErrorMessages(false));
    }

    /**
     * @return FlashMessage[]
     */
    public function getErrorMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(ContextualFeedbackSeverity::ERROR);
        }

        return $this->getFlashMessagesBySeverity(ContextualFeedbackSeverity::ERROR);
    }

    public function hasWarningMessages(): bool
    {
        return !empty($this->getWarningMessages(false));
    }

    /**
     * @return FlashMessage[]
     */
    public function getWarningMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(ContextualFeedbackSeverity::WARNING);
        }

        return $this->getFlashMessagesBySeverity(ContextualFeedbackSeverity::WARNING);
    }

    public function hasOkMessages(): bool
    {
        return !empty($this->getOkMessages(false));
    }

    /**
     * @return FlashMessage[]
     */
    public function getOkMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(ContextualFeedbackSeverity::OK);
        }

        return $this->getFlashMessagesBySeverity(ContextualFeedbackSeverity::OK);
    }

    public function hasInfoMessages(): bool
    {
        return !empty($this->getInfoMessages(false));
    }

    /**
     * @return FlashMessage[]
     */
    public function getInfoMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(ContextualFeedbackSeverity::INFO);
        }

        return $this->getFlashMessagesBySeverity(ContextualFeedbackSeverity::INFO);
    }

    public function hasNoticeMessages(): bool
    {
        return !empty($this->getNoticeMessages(false));
    }

    /**
     * @return FlashMessage[]
     */
    public function getNoticeMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(ContextualFeedbackSeverity::NOTICE);
        }

        return $this->getFlashMessagesBySeverity(ContextualFeedbackSeverity::NOTICE);
    }

    protected function getFlashMessageQueue(): FlashMessageQueue
    {
        return $this->flashMessageService->getMessageQueueByIdentifier();
    }
}
