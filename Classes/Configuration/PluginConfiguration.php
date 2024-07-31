<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Configuration;

/**
 * This class will streamline the values from tt_content plugin configuration
 */
class PluginConfiguration
{
    protected array $record = [];

    protected string $accessToken = '';

    /**
     * It contains the name of the request class
     */
    protected string $requestType = '';

    protected int $entriesToLoad = 24;

    protected int $entriesToShow = 8;

    protected int $showWallsSince = 365;

    public function __construct(array $processedData)
    {
        $this->record = $processedData['data'] ?? [];
        $this->accessToken = $processedData['conf']['accessToken'] ?? '';
        $this->requestType = $processedData['conf']['requestType'] ?? '';
        $this->entriesToLoad = (int)($processedData['conf']['entriesToLoad'] ?? 24);
        $this->entriesToShow = (int)($processedData['conf']['entriesToShow'] ?? 8);
        $this->showWallsSince = (int)($processedData['conf']['showWallsSince'] ?? 365);
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
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
}
