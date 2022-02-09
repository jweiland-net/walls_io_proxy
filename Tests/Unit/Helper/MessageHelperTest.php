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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;

/**
 * Test MessageHelper
 */
class MessageHelperTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var MessageHelper
     */
    protected $subject;

    /**
     * @var FlashMessageService|ObjectProphecy
     */
    protected $flashMessageServiceProphecy;

    /**
     * @var FlashMessageQueue|ObjectProphecy
     */
    protected $flashMessageQueueProphecy;

    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $backendUserAuthenticationProphecy;

    /**
     * @var string
     */
    protected $queueIdentifier = 'core.template.flashMessages';

    protected function setUp(): void
    {
        $this->flashMessageServiceProphecy = $this->prophesize(FlashMessageService::class);
        $this->flashMessageQueueProphecy = $this->prophesize(FlashMessageQueue::class);
        $this->backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);

        $this->flashMessageServiceProphecy
            ->getMessageQueueByIdentifier()
            ->shouldBeCalled()
            ->willReturn($this->flashMessageQueueProphecy);

        $this->subject = new MessageHelper(
            $this->flashMessageServiceProphecy->reveal()
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->flashMessageServiceProphecy,
            $this->flashMessageQueueProphecy,
            $this->backendUserAuthenticationProphecy
        );

        parent::tearDown();
    }

    public function dataProviderForAllSeverities(): array
    {
        return [
            'OK' => [AbstractMessage::OK, 'Ok'],
            'ERROR' => [AbstractMessage::ERROR, 'Error'],
            'INFO' => [AbstractMessage::INFO, 'Info'],
            'NOTICE' => [AbstractMessage::NOTICE, 'Notice'],
            'WARNING' => [AbstractMessage::WARNING, 'Warning'],
        ];
    }

    /**
     * @test
     */
    public function addFlashMessageWillAddMessageToQueue(): void
    {
        $this->flashMessageQueueProphecy
            ->enqueue(Argument::that(static function (FlashMessage $flashMessage) {
                return $flashMessage->getTitle() === 'header'
                    && $flashMessage->getMessage() === 'hello'
                    && $flashMessage->getSeverity() === AbstractMessage::OK
                    && $flashMessage->isSessionMessage() === true;
            }))
            ->shouldBeCalled();

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
        $this->flashMessageQueueProphecy
            ->getAllMessagesAndFlush()
            ->shouldNotBeCalled();

        $this->flashMessageQueueProphecy
            ->getAllMessages()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->subject->getAllFlashMessages(false);
    }

    /**
     * @test
     */
    public function getAllFlashMessagesWithFlushWillReturnAllFlashMessages(): void
    {
        $this->flashMessageQueueProphecy
            ->getAllMessagesAndFlush()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->flashMessageQueueProphecy
            ->getAllMessages()
            ->shouldNotBeCalled();

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
            AbstractMessage::OK,
            true
        );

        $this->flashMessageQueueProphecy
            ->getAllMessages()
            ->shouldBeCalled()
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
        $this->flashMessageQueueProphecy
            ->getAllMessages()
            ->shouldBeCalled()
            ->willReturn([]);

        self::assertFalse(
            $this->subject->hasMessages()
        );
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function getFlashMessagesBySeverityAndFlushWillReturnFlashMessageWithSeverity(int $severity, string $severityName): void
    {
        $flashMessage = new FlashMessage(
            'message',
            'title',
            $severity,
            true
        );

        $this->flashMessageQueueProphecy
            ->getAllMessagesAndFlush($severity)
            ->shouldBeCalled()
            ->willReturn([$flashMessage]);

        self::assertSame(
            [$flashMessage],
            $this->subject->getFlashMessagesBySeverityAndFlush($severity)
        );
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function hasSeverityMessagesWithMessagesWillReturnTrue(int $severity, string $severityName): void
    {
        $flashMessage = new FlashMessage(
            'message',
            'title',
            $severity,
            true
        );

        $this->flashMessageQueueProphecy
            ->getAllMessages($severity)
            ->shouldBeCalled()
            ->willReturn([$flashMessage]);

        $methodName = 'has' . $severityName . 'Messages';

        self::assertTrue(
            $this->subject->$methodName()
        );
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function hasSeverityMessagesWithoutMessagesWillReturnFalse(int $severity, string $severityName): void
    {
        $this->flashMessageQueueProphecy
            ->getAllMessages($severity)
            ->shouldBeCalled()
            ->willReturn([]);

        $methodName = 'has' . $severityName . 'Messages';

        self::assertFalse(
            $this->subject->$methodName()
        );
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function getWarningMessagesWithoutFlushWillReturnAllFlashMessages(int $severity, string $severityName): void
    {
        $this->flashMessageQueueProphecy
            ->getAllMessagesAndFlush($severity)
            ->shouldNotBeCalled();

        $this->flashMessageQueueProphecy
            ->getAllMessages($severity)
            ->shouldBeCalled()
            ->willReturn([]);

        $methodName = 'get' . $severityName . 'Messages';

        $this->subject->$methodName(false);
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForAllSeverities
     */
    public function getErrorMessagesWithFlushWillReturnAllFlashMessages(int $severity, string $severityName): void
    {
        $this->flashMessageQueueProphecy
            ->getAllMessagesAndFlush($severity)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->flashMessageQueueProphecy
            ->getAllMessages($severity)
            ->shouldNotBeCalled();

        $methodName = 'get' . $severityName . 'Messages';

        $this->subject->$methodName(true);
    }
}
