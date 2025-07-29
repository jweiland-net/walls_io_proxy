<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Functional\ViewHelpers\Be\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Test IsAdministratorViewHelper
 */
class IsAdministratorViewHelperTest extends FunctionalTestCase
{
    protected const TEMPLATE_SOURCE = <<<'EOT'
        <html lang="en"
              xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
              xmlns:w="http://typo3.org/ns/JWeiland/WallsIoProxy/ViewHelpers"
              data-namespace-typo3-fluid="true">
            <w:be.security.isAdministrator>
                <f:then>IS ADMIN</f:then>
                <f:else>IS NOT ADMIN</f:else>
            </w:be.security.isAdministrator>
        </html>
    EOT;

    protected bool $resetSingletonInstances = true;

    protected array $testExtensionsToLoad = [
        'jweiland/walls-io-proxy',
    ];

    public static function beUserDataProvider(): array
    {
        return [
            'BE user is administrator' => [1, 'IS ADMIN'],
            'BE user is editor' => [2, 'IS NOT ADMIN'],
        ];
    }

    #[Test]
    #[DataProvider('beUserDataProvider')]
    public function isAdminWithLoggedInUserWillReturnExpectedResult(int $beUserUid, string $expectedResult): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/Database/be_users.csv');
        $this->setUpBackendUser($beUserUid);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(self::TEMPLATE_SOURCE);

        $view = new TemplateView($context);

        self::assertStringContainsString(
            $expectedResult,
            $view->render()
        );
    }

    #[Test]
    public function isAdminWithNoLoggedInUserWillReturnIsNoAdmin(): void
    {
        /** @var BackendUserAuthentication|MockObject $backendUserAuthenticationMock */
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);

        $GLOBALS['BE_USER'] = $backendUserAuthenticationMock;

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(self::TEMPLATE_SOURCE);

        $view = new TemplateView($context);

        self::assertStringContainsString(
            'IS NOT ADMIN',
            $view->render()
        );
    }

    #[Test]
    public function isAdminWithNoGlobalBeUserWillReturnIsNoAdmin(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(self::TEMPLATE_SOURCE);

        $view = new TemplateView($context);
        self::assertStringContainsString(
            'IS NOT ADMIN',
            $view->render()
        );
    }
}
