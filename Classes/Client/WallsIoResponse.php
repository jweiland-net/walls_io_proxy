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
