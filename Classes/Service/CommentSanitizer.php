<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Service;

use JWeiland\WallsIoProxy\Html\CommentSanitizerBuilder;
use TYPO3\HtmlSanitizer\Sanitizer;

/**
 * Makes the comment of a walls.io post safe to be output as HTML in frontend.
 */
final readonly class CommentSanitizer
{
    private Sanitizer $sanitizer;

    public function __construct(CommentSanitizerBuilder $sanitizerBuilder)
    {
        $this->sanitizer = $sanitizerBuilder->build();
    }

    public function sanitize(string $comment): string
    {
        return nl2br($this->sanitizer->sanitize($comment));
    }
}
