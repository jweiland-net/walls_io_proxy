<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\Client;

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

/**
 * Walls.io Request
 */
class WallsIoRequest
{
    /**
     * @var int
     */
    protected $wallId = 0;

    /**
     * @var string
     */
    protected $sessionId = '';

    /**
     * @var int
     */
    protected $entriesToLoad = 0;

    /**
     * GeneralUtility::getUrl expects this value to be int
     *
     * @var int
     */
    protected $includeHeader = 0;

    /**
     * It's really hard to interpret walls.io binary support.
     * Keep this value disabled to switch over to base64.
     * We don't support binary requests!
     *
     * @var bool
     */
    protected $useBinarySupport = false;

    /**
     * Keep this value enabled, as walls.io needs the Cookie information of first request
     * in second/further request, too.
     *
     * @var bool
     */
    protected $useCookies = true;

    public function getWallId(): int
    {
        return $this->wallId;
    }

    public function setWallId(int $wallId)
    {
        $this->wallId = $wallId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function getEntriesToLoad(): int
    {
        return $this->entriesToLoad;
    }

    public function setEntriesToLoad(int $entriesToLoad)
    {
        $this->entriesToLoad = $entriesToLoad;
    }

    public function getIncludeHeader(): int
    {
        return $this->includeHeader;
    }

    public function setIncludeHeader(int $includeHeader)
    {
        $this->includeHeader = $includeHeader;
    }

    public function getUseBinarySupport(): bool
    {
        return $this->useBinarySupport;
    }

    public function setUseBinarySupport(bool $useBinarySupport)
    {
        $this->useBinarySupport = $useBinarySupport;
    }

    public function getUseCookies(): bool
    {
        return $this->useCookies;
    }

    public function setUseCookies(bool $useCookies)
    {
        $this->useCookies = $useCookies;
    }
}
