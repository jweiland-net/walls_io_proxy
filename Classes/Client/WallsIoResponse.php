<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Client;

/**
 * Walls.io Response
 */
class WallsIoResponse
{
    /**
     * @var string
     */
    protected $header = '';

    /**
     * @var string
     */
    protected $body = '';

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setHeader(string $header)
    {
        $this->header = $header;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body)
    {
        $this->body = $body;
    }
}
