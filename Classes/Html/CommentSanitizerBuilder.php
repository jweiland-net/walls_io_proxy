<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Html;

use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Behavior\Attr\UriAttrValueBuilder;
use TYPO3\HtmlSanitizer\Builder\CommonBuilder;

/**
 * Builds the TYPO3 HTML sanitizer for comments of walls.io posts. These comments are external content
 * and will be output without further escaping, so only simple text formatting, links to http(s) targets
 * and images from our local file cache are allowed. Everything else (scripts, event handlers, styles, ...)
 * will be encoded or removed.
 */
final class CommentSanitizerBuilder extends CommonBuilder
{
    private const TEXT_TAGS = ['b', 'strong', 'i', 'em', 'u', 'p', 'span', 'ul', 'ol', 'li', 'blockquote'];

    protected function createBehavior(): Behavior
    {
        $hrefAttr = (new Behavior\Attr('href'))->addValues(
            ...(new UriAttrValueBuilder())->allowLocal(false)->allowSchemes('http', 'https')->getValues(),
        );
        // Images are replaced by our local cached copies before sanitizing. So, only local paths are allowed.
        $srcAttr = (new Behavior\Attr('src'))->addValues(
            ...(new UriAttrValueBuilder())->allowLocal(true)->getValues(),
        );

        $tags = [];
        foreach (self::TEXT_TAGS as $name) {
            $tags[] = new Behavior\Tag($name, Behavior\Tag::ALLOW_CHILDREN);
        }

        $tags[] = new Behavior\Tag('br');
        $tags[] = (new Behavior\Tag('a', Behavior\Tag::ALLOW_CHILDREN))->addAttrs($hrefAttr);
        $tags[] = (new Behavior\Tag('img', Behavior\Tag::PURGE_WITHOUT_ATTRS))
            ->addAttrs($srcAttr, new Behavior\Attr('alt'));

        return (new Behavior())
            ->withFlags(
                Behavior::ENCODE_INVALID_TAG
                | Behavior::REMOVE_UNEXPECTED_CHILDREN
                | Behavior::ENCODE_INVALID_PROCESSING_INSTRUCTION,
            )
            ->withName('walls_io_proxy_comment')
            ->withTags(...$tags);
    }
}
