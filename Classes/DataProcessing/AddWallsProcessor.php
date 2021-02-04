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
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $this->updateProcessedData($processedData);
        $entriesToLoad = (int)$processedData['conf']['entriesToLoad'];
        $wallsService = GeneralUtility::makeInstance(
            WallsService::class,
            (int)$processedData['conf']['wallId']
        );

        $this->targetDirectory = $wallsService->getTargetDirectory();
        $walls = $wallsService->getWalls($entriesToLoad);

        $processedData['walls'] = $this->sanitizeData($walls);

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
        foreach ($walls as $key => &$wall) {
            foreach ($wall as $property => $value) {
                if ($property === 'created_timestamp') {
                    $walls[$key]['created_timestamp_as_text'] = $this->getCreationText((int)$value);
                }

                if (
                    in_array($property, ['external_image', 'post_image'], true)
                    && !empty($value)
                    && StringUtility::beginsWith($value, 'http')
                ) {
                    $wall[$property] = $this->cacheExternalResources($value);
                }

                $matches = [];
                if (
                    $property === 'comment'
                    && !empty($value)
                    && preg_match_all('/<img.*?src=["|\'](?<src>.*?)["|\'].*?>/', $value, $matches)
                ) {
                    if (
                        array_key_exists('src', $matches)
                        && is_array($matches['src'])
                    ) {
                        foreach ($matches['src'] as $uri) {
                            if (StringUtility::beginsWith($uri, 'http')) {
                                $wall[$property] = str_replace(
                                    $matches['src'],
                                    $this->cacheExternalResources($uri),
                                    $value
                                );
                            }
                        }
                    }
                }
            }
        }
        return $walls;
    }

    protected function cacheExternalResources(string $resource): string
    {
        if (!is_dir($this->targetDirectory)) {
            GeneralUtility::mkdir_deep($this->targetDirectory);
        }

        $pathParts = GeneralUtility::split_fileref(parse_url($resource, PHP_URL_PATH));
        $filePath = sprintf(
            '%s%s.%s',
            $this->targetDirectory,
            $pathParts['filebody'],
            $pathParts['fileext']
        );

        if (!file_exists($filePath)) {
            GeneralUtility::writeFile($filePath, GeneralUtility::getUrl($resource));
        }

        return PathUtility::getAbsoluteWebPath($filePath);
    }

    protected function getCreationText(int $creationTime): string
    {
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
        }
        if ($diffInSeconds > 60 && $diffInSeconds <= 3600) {
            return LocalizationUtility::translate(
                'creationTime.minutes',
                'walls_io_proxy',
                [$dateInterval->format('%i')]
            );
        }
        if ($diffInSeconds > 3600 && $diffInSeconds <= 86400) {
            return LocalizationUtility::translate(
                'creationTime.hours',
                'walls_io_proxy',
                [$dateInterval->format('%h')]
            );
        }
        return LocalizationUtility::translate(
            'creationTime.date',
            'walls_io_proxy',
            [$creationDate->format('d.m.Y H:i')]
        );
    }
}
