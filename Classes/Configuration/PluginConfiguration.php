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
    /**
     * @var array
     */
    protected $record = [];

    /**
     * @var string
     */
    protected $accessToken = '';

    /**
     * It contains the name of the request class
     *
     * @var string
     */
    protected $requestType = '';

    /**
     * @var int
     */
    protected $entriesToLoad = 24;

    /**
     * @var int
     */
    protected $entriesToShow = 8;

    /**
     * @var int
     */
    protected $showWallsSince = 365;

    public function __construct(array $processedData)
    {
        $this->setRecord($processedData['data'] ?? []);
        $this->setAccessToken($processedData['conf']['accessToken'] ?? '');
        $this->setRequestType($processedData['conf']['requestType'] ?? '');
        $this->setEntriesToLoad((int)($processedData['conf']['entriesToLoad'] ?? 24));
        $this->setEntriesToShow((int)($processedData['conf']['entriesToShow'] ?? 8));
        $this->setShowWallsSince((int)($processedData['conf']['showWallsSince'] ?? 365));
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function setRequestType(string $requestType): void
    {
        $this->requestType = $requestType;
    }

    public function getEntriesToLoad(): int
    {
        return $this->entriesToLoad;
    }

    public function setEntriesToLoad(int $entriesToLoad): void
    {
        $this->entriesToLoad = $entriesToLoad;
    }

    public function getEntriesToShow(): int
    {
        return $this->entriesToShow;
    }

    public function setEntriesToShow(int $entriesToShow): void
    {
        $this->entriesToShow = $entriesToShow;
    }

    public function getShowWallsSince(): int
    {
        return $this->showWallsSince;
    }

    public function setShowWallsSince(int $showWallsSince): void
    {
        $this->showWallsSince = $showWallsSince;
    }

    public function getRecordUid(): int
    {
        return (int)($this->record['uid'] ?? 0);
    }
}
