<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Controller;

use JWeiland\WallsIoProxy\Service\WallsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * AJAX endpoint of the backend to clear the cached posts of one walls.io plugin.
 * Only administrators are allowed to use it.
 */
#[AsController]
final readonly class ClearCacheController
{
    private const PLUGIN_CTYPE = 'wallsioproxy';

    public function __construct(
        private WallsService $wallsService,
        private ConnectionPool $connectionPool,
        private LanguageServiceFactory $languageServiceFactory,
        private LoggerInterface $logger,
    ) {}

    public function clearAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $request->getAttribute('backend.user');
        $languageService = $this->languageServiceFactory->createFromUserPreferences(
            $backendUser instanceof BackendUserAuthentication ? $backendUser : null,
        );

        if ($request->getMethod() !== 'POST') {
            return $this->createResponse($languageService, 405, 'clearCache.error.request.message');
        }

        if (!$backendUser instanceof BackendUserAuthentication || !$backendUser->isAdmin()) {
            $this->logger->warning(
                'A non-administrator tried to clear the cache of a walls.io plugin.',
                ['beUserUid' => $backendUser instanceof BackendUserAuthentication ? $this->getUserUid($backendUser) : 0],
            );

            return $this->createResponse($languageService, 403, 'clearCache.error.forbidden');
        }

        $parsedBody = $request->getParsedBody();
        $contentRecordUid = is_array($parsedBody) ? (int)($parsedBody['contentRecordUid'] ?? 0) : 0;
        $beUserUid = $this->getUserUid($backendUser);

        if ($contentRecordUid <= 0 || !$this->isWallsIoPlugin($contentRecordUid)) {
            $this->logger->warning(
                'Clearing the cache of a walls.io plugin was requested for an invalid content record.',
                ['contentRecordUid' => $contentRecordUid, 'beUserUid' => $beUserUid],
            );

            return $this->createResponse($languageService, 404, 'clearCache.error.invalidRecord', $contentRecordUid);
        }

        if (!$this->wallsService->clearCache($contentRecordUid)) {
            $this->logger->error(
                'The cache of a walls.io plugin could not be cleared.',
                ['contentRecordUid' => $contentRecordUid, 'beUserUid' => $beUserUid],
            );

            return $this->createResponse($languageService, 500, 'clearCache.error.failed', $contentRecordUid);
        }

        $this->logger->info(
            'The cache of a walls.io plugin was cleared.',
            ['contentRecordUid' => $contentRecordUid, 'beUserUid' => $beUserUid],
        );

        return $this->createResponse($languageService, 200, 'clearCache.success.message', $contentRecordUid);
    }

    private function createResponse(
        LanguageService $languageService,
        int $statusCode,
        string $messageKey,
        int $contentRecordUid = 0,
    ): ResponseInterface {
        $isSuccess = $statusCode === 200;
        $titleKey = $isSuccess ? 'clearCache.success.title' : 'clearCache.error.title';

        return new JsonResponse(
            [
                'success' => $isSuccess,
                'title' => $languageService->sL('walls_io_proxy.messages:' . $titleKey),
                'message' => sprintf(
                    $languageService->sL('walls_io_proxy.messages:' . $messageKey),
                    $contentRecordUid,
                ),
            ],
            $statusCode,
        );
    }

    private function isWallsIoPlugin(int $contentRecordUid): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        return (int)$queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($contentRecordUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter(self::PLUGIN_CTYPE)),
            )
            ->executeQuery()
            ->fetchOne() > 0;
    }

    private function getUserUid(BackendUserAuthentication $backendUser): int
    {
        return (int)($backendUser->user['uid'] ?? 0);
    }
}
