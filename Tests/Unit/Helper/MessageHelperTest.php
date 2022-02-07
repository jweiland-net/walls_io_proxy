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
     * @var FlashMessageQueue
     */
    protected $flashMessageQueue;

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
        $this->flashMessageQueue = new FlashMessageQueue($this->queueIdentifier);
        $this->backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);

        $this->flashMessageServiceProphecy
            ->getMessageQueueByIdentifier()
            ->shouldBeCalled()
            ->willReturn($this->flashMessageQueue);

        $this->backendUserAuthenticationProphecy
            ->getSessionData($this->queueIdentifier)
            ->shouldBeCalled()
            ->willReturn([]);

        $GLOBALS['BE_USER'] = $this->backendUserAuthenticationProphecy->reveal();

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
            $this->backendUserAuthenticationProphecy,
            $GLOBALS['BE_USER']
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function addFlashMessageWillAddMessageToQueue(): void
    {
        $this->subject->addFlashMessage(
            'hello',
            'header'
        );

        $this->backendUserAuthenticationProphecy
            ->setAndSaveSessionData(
                $this->queueIdentifier,
                Argument::that(static function ($flashMessages) {
                    /** @var FlashMessage $flashMessage */
                    $flashMessage = current($flashMessages);

                    return $flashMessage->getTitle() === 'header'
                        && $flashMessage->getMessage() === 'hello';
                })
            )
            ->shouldBeCalled();
    }
}
