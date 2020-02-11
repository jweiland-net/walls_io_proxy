<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\DataProcessing;

/*
 * This file is part of the walls_io_proxy project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use JWeiland\WallsIoProxy\Service\WallsService;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * FLUIDTEMPLATE DataProcessor to retrieve the postings of various platforms like Facebook, Twitter and Instagram
 * through the service of walls.io and make them available in Template as {walls}
 */
class AddWallsProcessor implements DataProcessorInterface
{
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
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $this->updateProcessedData($processedData);

        $wallsService = GeneralUtility::makeInstance(WallsService::class);
        $walls = $wallsService->getWalls(
            (int)$processedData['conf']['wallId'],
            (int)$processedData['conf']['entriesToLoad']
        );
        if (
            array_key_exists('errors', $walls)
            && $walls['errors']['error'] !== 0
        ) {
            DebuggerUtility::var_dump($walls);
        }
        $processedData['walls'] = $this->sanitizeData($walls[1] ?? []);
        return $processedData;
    }

    protected function updateProcessedData(array &$processedData)
    {
        if (version_compare(TYPO3_branch, '9.4', '>=')) {
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
        } else {
            $flexFormService = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Service\FlexFormService::class);
        }
        $conf = $flexFormService->convertFlexFormContentToArray(
            $processedData['data']['pi_flexform'] ?? []
        );
        $processedData['conf'] = $conf;
    }

    protected function sanitizeData(array $walls): array
    {
        foreach ($walls as $key => $wall) {
            foreach ($wall as $property => $value) {
                if (is_string($value)) {
                    $walls[$key][$property] = utf8_decode($value);
                }
                if ($property === 'post_image_aspect_ratio') {
                    $walls[$key]['post_image_padding'] = 100 / $value;
                }
                if ($property === 'external_created_timestamp') {
                    $walls[$key]['external_created_timestamp_as_text'] = $this->getCreationText((float)$value);
                }
            }
        }
        return $walls;
    }

    protected function getCreationText(float $creationTime): string
    {
        $creationTime = (int)ceil($creationTime / 1000);
        $currentTimestamp = (int)date('U');
        $diffInSeconds = $currentTimestamp - $creationTime;

        $creationDate = new \DateTime(date('c', $creationTime));
        $currentDate = new \DateTime(date('c', $currentTimestamp));
        $dateInterval = $currentDate->diff($creationDate);

        if ($diffInSeconds <= 60) {
            return LocalizationUtility::translate(
                'creationTime.seconds',
                'walls_io_proxy'
            );
        } elseif ($diffInSeconds > 60 && $diffInSeconds <= 3600) {
            return LocalizationUtility::translate(
                'creationTime.minutes',
                'walls_io_proxy',
                [$dateInterval->format('%i')]
            );
        } elseif ($diffInSeconds > 3600 && $diffInSeconds <= 86400) {
            return LocalizationUtility::translate(
                'creationTime.hours',
                'walls_io_proxy',
                [$dateInterval->format('%h')]
            );
        } else {
            return LocalizationUtility::translate(
                'creationTime.date',
                'walls_io_proxy',
                [$creationDate->format('d.m.Y H:i')]
            );
        }
    }
}
