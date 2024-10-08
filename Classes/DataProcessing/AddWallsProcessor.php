<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\DataProcessing;

use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\Service\WallsService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * FLUIDTEMPLATE DataProcessor to retrieve the postings of various platforms like Facebook, Twitter and Instagram
 * through the service of walls.io and make them available in Template as {walls}
 */
class AddWallsProcessor implements DataProcessorInterface
{
    protected WallsService $wallsService;

    protected FlexFormService $flexFormService;

    protected ServerRequest $request;

    public function __construct(WallsService $wallsService, FlexFormService $flexFormService, ServerRequest $request)
    {
        $this->wallsService = $wallsService;
        $this->flexFormService = $flexFormService;
        $this->request = $request;
    }

    /**
     * Process data of a record to resolve File objects to the view
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<string, mixed> the processed data as key/value store
     * @throws \Exception
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $processedData['conf'] = $this->flexFormService->convertFlexFormContentToArray(
            $processedData['data']['pi_flexform'] ?? ''
        );

        $processedData['walls'] = $this->wallsService->getWallPosts(
            $this->getPluginConfiguration($processedData),
            $this->request
        );

        return $processedData;
    }

    /**
     * @param array<string, mixed> $processedData
     */
    protected function getPluginConfiguration(array $processedData): PluginConfiguration
    {
        return GeneralUtility::makeInstance(
            PluginConfiguration::class,
            $processedData
        );
    }
}
