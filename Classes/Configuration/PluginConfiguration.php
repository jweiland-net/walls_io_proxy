<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Configuration;

use JWeiland\WallsIoProxy\Request\RequestType;

/**
 * This class will streamline the values from tt_content plugin configuration
 */
final readonly class PluginConfiguration
{
    private const DEFAULT_ENTRIES_TO_LOAD = 24;

    private const DEFAULT_ENTRIES_TO_SHOW = 8;

    private const DEFAULT_SHOW_WALLS_SINCE = 365;

    /**
     * @param array<string, mixed> $record
     */
    public function __construct(
        private array $record = [],
        private string $accessToken = '',
        private string $requestType = '',
        private int $entriesToLoad = self::DEFAULT_ENTRIES_TO_LOAD,
        private int $entriesToShow = self::DEFAULT_ENTRIES_TO_SHOW,
        private int $showWallsSince = self::DEFAULT_SHOW_WALLS_SINCE,
    ) {}

    /**
     * @param array<string, mixed> $processedData Processed data of the DataProcessor with the keys "data" (tt_content
     *                                            record) and "conf" (converted FlexForm)
     */
    public static function fromProcessedData(array $processedData): self
    {
        $record = $processedData['data'] ?? [];
        $configuration = $processedData['conf'] ?? [];
        if (!is_array($configuration)) {
            $configuration = [];
        }

        return new self(
            is_array($record) ? $record : [],
            trim(self::getString($configuration, 'accessToken')),
            self::getString($configuration, 'requestType'),
            (int)($configuration['entriesToLoad'] ?? self::DEFAULT_ENTRIES_TO_LOAD),
            (int)($configuration['entriesToShow'] ?? self::DEFAULT_ENTRIES_TO_SHOW),
            (int)($configuration['showWallsSince'] ?? self::DEFAULT_SHOW_WALLS_SINCE),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Returns the value as it was stored in the FlexForm.
     * Use resolveRequestType() to get the validated request type.
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function resolveRequestType(): ?RequestType
    {
        return RequestType::tryFromStoredValue($this->requestType);
    }

    public function getEntriesToLoad(): int
    {
        return $this->entriesToLoad;
    }

    public function getEntriesToShow(): int
    {
        return $this->entriesToShow;
    }

    public function getShowWallsSince(): int
    {
        return $this->showWallsSince;
    }

    public function getRecordUid(): int
    {
        return (int)($this->record['uid'] ?? 0);
    }

    /**
     * Names of all FlexForm fields which prevent the plugin from rendering any posts.
     * The names are used as part of the label identifier "invalidConfiguration.<field>".
     *
     * @return list<string>
     */
    public function getInvalidFields(): array
    {
        $invalidFields = [];

        if ($this->accessToken === '') {
            $invalidFields[] = 'accessToken';
        }

        if ($this->resolveRequestType() === null) {
            $invalidFields[] = 'requestType';
        }

        if ($this->entriesToLoad <= 0) {
            $invalidFields[] = 'entriesToLoad';
        }

        if ($this->entriesToShow <= 0) {
            $invalidFields[] = 'entriesToShow';
        }

        return $invalidFields;
    }

    /**
     * @param array<mixed> $configuration
     */
    private static function getString(array $configuration, string $key): string
    {
        $value = $configuration[$key] ?? '';

        return is_scalar($value) ? (string)$value : '';
    }
}
