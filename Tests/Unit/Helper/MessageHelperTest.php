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
    protected MessageHelper $subject;

    protected FlashMessageService|MockObject $flashMessageServiceMockObject;

    protected FlashMessageQueue|MockObject $flashMessageQueueMockObject;

    protected BackendUserAuthentication|MockObject $backendUserAuthenticationMockObject;

    protected string $queueIdentifier = 'core.template.flashMessages';

    protected function setUp(): void
    {
        // Create mock objects for dependencies
        $this->flashMessageQueueMockObject = $this->createMock(FlashMessageQueue::class);
        $this->backendUserAuthenticationMockObject = $this->createMock(BackendUserAuthentication::class);

        // Create a mock object for FlashMessageService
        $this->flashMessageServiceMockObject = $this->getMockBuilder(FlashMessageService::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set expectation for getMessageQueueByIdentifier method
        $this->flashMessageServiceMockObject->expects($this->once())
            ->method('getMessageQueueByIdentifier')
            ->willReturn($this->flashMessageQueueMockObject);

        // Initialize the subject with the mocked FlashMessageService
        $this->subject = new MessageHelper($this->flashMessageServiceMockObject);
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->flashMessageServiceMockObject,
            $this->flashMessageQueueMockObject,
            $this->backendUserAuthenticationMockObject
        );

        parent::tearDown();
    }

    public function dataProviderForAllSeverities(): array
    {
        return [
            [(int)ContextualFeedbackSeverity::WARNING, 'Warning'],
            [(int)ContextualFeedbackSeverity::ERROR, 'Error'],
            [(int)ContextualFeedbackSeverity::OK, 'OK'],
            [(int)ContextualFeedbackSeverity::INFO, 'INFO'],
            [(int)ContextualFeedbackSeverity::NOTICE, 'NOTICE'],
        ];
    }

    /**
     * @test
     */
    public function addFlashMessageWillAddMessageToQueue(): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Set up the expectation for the enqueue method
        $flashMessageQueueMock->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (FlashMessage $flashMessage) {
                return $flashMessage->getTitle() === 'header'
                    && $flashMessage->getMessage() === 'hello'
                    && $flashMessage->getSeverity() === ContextualFeedbackSeverity::OK
                    && $flashMessage->isSessionMessage() === true;
            }));

        // Set the mock object in the subject (assuming it has a setter method or similar)
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Call the method being tested
        $this->subject->addFlashMessage('hello', 'header');
    }

    /**
     * @test
     */
    public function getAllFlashMessagesWithoutFlushWillReturnAllFlashMessages(): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Set up the expectation that getAllMessagesAndFlush should not be called
        $flashMessageQueueMock->expects($this->never())
            ->method('getAllMessagesAndFlush');

        // Set up the expectation that getAllMessages should be called and return an empty array
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessages')
            ->willReturn([]);

        // Set the mock object in the subject (assuming it has a setter method or similar)
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Call the method being tested
        $result = $this->subject->getAllFlashMessages(false);

        // Add assertions to verify the result, if needed
        $this->assertSame([], $result);
    }

    /**
     * @test
     */
    public function getAllFlashMessagesWithFlushWillReturnAllFlashMessages(): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Set up the expectation that getAllMessagesAndFlush should be called and return an empty array
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessagesAndFlush')
            ->willReturn([]);

        // Set up the expectation that getAllMessages should not be called
        $flashMessageQueueMock->expects($this->never())
            ->method('getAllMessages');

        // Set the mock object in the subject (assuming it has a setter method or similar)
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Call the method being tested
        $result = $this->subject->getAllFlashMessages(true);

        // Add assertions to verify the result, if needed
        $this->assertSame([], $result);
    }

    /**
     * @test
     */
    public function hasMessagesWithMessagesWillReturnTrue(): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Create an instance of FlashMessage
        $flashMessage = new FlashMessage(
            'message',
            'title',
            ContextualFeedbackSeverity::OK,
            true
        );

        // Set up the expectation that getAllMessages should be called and return the flash message
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessages')
            ->willReturn([$flashMessage]);

        // Set the mock object in the subject (assuming it has a setter method or similar)
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Call the method being tested
        $result = $this->subject->hasMessages();

        // Add an assertion to verify the result
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function hasMessagesWithoutMessagesWillReturnFalse(): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Set up the expectation that getAllMessages should be called and return an empty array
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessages')
            ->willReturn([]);

        // Set the mock object in the subject (assuming it has a setter method or similar)
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Call the method being tested
        $result = $this->subject->hasMessages();

        // Add an assertion to verify the result
        $this->assertFalse($result);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function getFlashMessagesBySeverityAndFlushWillReturnFlashMessageWithSeverity(int $severity, string $severityName): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Create an instance of FlashMessage
        $flashMessage = new FlashMessage(
            'message',
            'title',
            $severity,
            true
        );

        // Set up the expectation that getAllMessagesAndFlush should be called with the specified severity and return the flash message
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessagesAndFlush')
            ->with($this->equalTo($severity))
            ->willReturn([$flashMessage]);

        // Set the mock object in the subject (assuming it has a setter method or similar)
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Call the method being tested
        $result = $this->subject->getFlashMessagesBySeverityAndFlush($severity);

        // Add an assertion to verify the result
        $this->assertSame([$flashMessage], $result);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function hasSeverityMessagesWithMessagesWillReturnTrue(int $severity, string $severityName): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Create an instance of FlashMessage
        $flashMessage = new FlashMessage(
            'message',
            'title',
            $severity,
            true
        );

        // Set up the expectation that getAllMessages should be called with the specified severity and return the flash message
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessages')
            ->with($this->equalTo($severity))
            ->willReturn([$flashMessage]);

        // Set the mock object in the subject (assuming it has a setter method or similar)
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Build the method name dynamically
        $methodName = 'has' . $severityName . 'Messages';

        // Call the dynamically named method and assert the result
        $result = $this->subject->$methodName();
        $this->assertTrue($result);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function hasSeverityMessagesWithoutMessagesWillReturnFalse(int $severity, string $severityName): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Set up the expectation that getAllMessages should be called with the specified severity and return an empty array
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessages')
            ->with($this->equalTo($severity))
            ->willReturn([]);

        // Set the mock object in the subject
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Build the method name dynamically
        $methodName = 'has' . $severityName . 'Messages';

        // Call the dynamically named method and assert the result
        $result = $this->subject->$methodName();
        $this->assertFalse($result);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function getWarningMessagesWithoutFlushWillReturnAllFlashMessages(int $severity, string $severityName): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Set up the expectation that getAllMessagesAndFlush should not be called
        $flashMessageQueueMock->expects($this->never())
            ->method('getAllMessagesAndFlush');

        // Set up the expectation that getAllMessages should be called with the specified severity and return an empty array
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessages')
            ->with($this->equalTo($severity))
            ->willReturn([]);

        // Set the mock object in the subject
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Build the method name dynamically
        $methodName = 'get' . $severityName . 'Messages';

        // Call the dynamically named method
        $this->subject->$methodName(false);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function getErrorMessagesWithFlushWillReturnAllFlashMessages(int $severity, string $severityName): void
    {
        // Create a mock for the FlashMessageQueue
        $flashMessageQueueMock = $this->createMock(FlashMessageQueue::class);

        // Set up the expectation that getAllMessagesAndFlush should be called with the specified severity and return the flash message
        $flashMessageQueueMock->expects($this->once())
            ->method('getAllMessagesAndFlush')
            ->with($this->equalTo($severity))
            ->willReturn([]);

        // Set up the expectation that getAllMessages should not be called
        $flashMessageQueueMock->expects($this->never())
            ->method('getAllMessages');

        // Set the mock object in the subject
        $this->subject->setFlashMessageQueue($flashMessageQueueMock);

        // Build the method name dynamically
        $methodName = 'get' . $severityName . 'Messages';

        // Call the dynamically named method
        $this->subject->$methodName(true);
    }
}
