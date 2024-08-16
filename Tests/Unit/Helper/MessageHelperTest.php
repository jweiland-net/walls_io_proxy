<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Helper;

use JWeiland\WallsIoProxy\Helper\MessageHelper;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test MessageHelper
 */
class MessageHelperTest extends UnitTestCase
{
    protected string $queueIdentifier = 'core.template.flashMessages';

    /**
     * @var BackendUserAuthentication|MockObject
     */
    protected $backendUserAuthenticationMock;

    /**
     * @var FlashMessageService|MockObject|(FlashMessageService&MockObject)
     */
    protected $flashMessageServiceMock;

    /**
     * @var FlashMessageQueue|MockObject|(FlashMessageQueue&MockObject)
     */
    protected $flashMessageQueueMock;

    protected MessageHelper $subject;

    protected function setUp(): void
    {
        $this->flashMessageServiceMock = $this->getMockBuilder(FlashMessageService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->flashMessageQueueMock = $this->getMockBuilder(FlashMessageQueue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->flashMessageServiceMock->expects(self::once())
            ->method('getMessageQueueByIdentifier')
            ->willReturn($this->flashMessageQueueMock);

        $this->backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);

        $this->subject = new MessageHelper(
            $this->flashMessageServiceMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->flashMessageServiceMock,
            $this->flashMessageQueueMock,
            $this->backendUserAuthenticationMock
        );

        parent::tearDown();
    }

    public function dataProviderForAllSeverities(): array
    {
        return [
            'OK' => [ContextualFeedbackSeverity::OK, 'Ok'],
            'ERROR' => [ContextualFeedbackSeverity::ERROR, 'Error'],
            'INFO' => [ContextualFeedbackSeverity::INFO, 'Info'],
            'NOTICE' => [ContextualFeedbackSeverity::NOTICE, 'Notice'],
            'WARNING' => [ContextualFeedbackSeverity::WARNING, 'Warning'],
        ];
    }

    /**
     * @test
     */
    public function addFlashMessageWillAddMessageToQueue(): void
    {
        $this->flashMessageQueueMock
            ->method('enqueue')
            ->with(
                self::callback(static function (FlashMessage $flashMessage) {
                    return $flashMessage->getTitle() === 'header'
                        && $flashMessage->getMessage() === 'hello'
                        && $flashMessage->getSeverity() === ContextualFeedbackSeverity::OK
                        && $flashMessage->isSessionMessage() === true;
                })
            );

        $this->subject->addFlashMessage(
            'hello',
            'header'
        );
    }

    /**
     * @test
     */
    public function getAllFlashMessagesWithoutFlushWillReturnAllFlashMessages(): void
    {
        $this->flashMessageQueueMock
            ->method('getAllMessagesAndFlush');

        $this->flashMessageQueueMock
            ->method('getAllMessages')
            ->willReturn([]);

        $this->subject->getAllFlashMessages(false);
    }

    /**
     * @test
     */
    public function getAllFlashMessagesWithFlushWillReturnAllFlashMessages(): void
    {
        $this->flashMessageQueueMock
            ->method('getAllMessagesAndFlush')
            ->willReturn([]);

        $this->flashMessageQueueMock
            ->method('getAllMessages');

        $this->subject->getAllFlashMessages(true);
    }

    /**
     * @test
     */
    public function hasMessagesWithMessagesWillReturnTrue(): void
    {
        $flashMessage = new FlashMessage(
            'message',
            'title',
            ContextualFeedbackSeverity::OK,
            true
        );

        $this->flashMessageQueueMock
            ->method('getAllMessages')
            ->willReturn([$flashMessage]);

        self::assertTrue(
            $this->subject->hasMessages()
        );
    }

    /**
     * @test
     */
    public function hasMessagesWithoutMessagesWillReturnFalse(): void
    {
        $this->flashMessageQueueMock
            ->method('getAllMessages')
            ->willReturn([]);

        self::assertFalse(
            $this->subject->hasMessages()
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForAllSeverities
     */
    public function getFlashMessagesBySeverityAndFlushWillReturnFlashMessageWithSeverity(ContextualFeedbackSeverity $severity, string $severityName): void
    {
        $flashMessage = new FlashMessage(
            'message',
            'title',
            $severity,
            true
        );

        $this->flashMessageQueueMock
            ->method('getAllMessagesAndFlush')
            ->with($severity)
            ->willReturn([$flashMessage]);

        self::assertSame(
            [$flashMessage],
            $this->subject->getFlashMessagesBySeverityAndFlush($severity)
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForAllSeverities
     */
    public function hasSeverityMessagesWithMessagesWillReturnTrue(ContextualFeedbackSeverity $severity, string $severityName): void
    {
        $flashMessage = new FlashMessage(
            'message',
            'title',
            $severity,
            true
        );

        $this->flashMessageQueueMock
            ->method('getAllMessages')
            ->with($severity)
            ->willReturn([$flashMessage]);

        $methodName = 'has' . $severityName . 'Messages';

        self::assertTrue(
            $this->subject->$methodName()
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForAllSeverities
     */
    public function hasSeverityMessagesWithoutMessagesWillReturnFalse(ContextualFeedbackSeverity $severity, string $severityName): void
    {
        $this->flashMessageQueueMock
            ->method('getAllMessages')
            ->with($severity)
            ->willReturn([]);

        $methodName = 'has' . $severityName . 'Messages';

        self::assertFalse(
            $this->subject->$methodName()
        );
    }

    /**
     * @test
     * @dataProvider dataProviderForAllSeverities
     */
    public function getWarningMessagesWithoutFlushWillReturnAllFlashMessages(ContextualFeedbackSeverity $severity, string $severityName): void
    {
        $this->flashMessageQueueMock
            ->method('getAllMessagesAndFlush')
            ->with($severity);

        $this->flashMessageQueueMock
            ->method('getAllMessages')
            ->with($severity)
            ->willReturn([]);

        $methodName = 'get' . $severityName . 'Messages';

        $this->subject->$methodName(false);
    }

    /**
     * @test
     * @dataProvider dataProviderForAllSeverities
     */
    public function getErrorMessagesWithFlushWillReturnAllFlashMessages(ContextualFeedbackSeverity $severity, string $severityName): void
    {
        $this->flashMessageQueueMock
            ->method('getAllMessagesAndFlush')
            ->with($severity)
            ->willReturn([]);

        $this->flashMessageQueueMock
            ->method('getAllMessages')
            ->with($severity);

        $methodName = 'get' . $severityName . 'Messages';

        $this->subject->$methodName(true);
    }
}
