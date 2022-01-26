<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\DataProcessing;

use JWeiland\WallsIoProxy\Service\WallsService;
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
    /**
     * @var string
     */
    protected $targetDirectory = '';

    /**
     * Process data of a record to resolve File objects to the view
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
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

        $this->updateProcessedData($processedData);
        $maxPosts = (int)$processedData['conf']['entriesToLoad'];
        $since = (int)$processedData['conf']['showWallsSince'];
        $wallsService = GeneralUtility::makeInstance(
            WallsService::class,
            (int)$processedData['data']['uid'],
            $processedData['conf']['accessToken']
        );

        $this->targetDirectory = $wallsService->getTargetDirectory();

        $processedData['walls'] = $wallsService->getWallPosts($maxPosts, $since);

        return $processedData;
    }

    protected function updateProcessedData(array &$processedData): void
    {
        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        $conf = $flexFormService->convertFlexFormContentToArray(
            $processedData['data']['pi_flexform'] ?? []
        );
        $processedData['conf'] = $conf;
    }
}
