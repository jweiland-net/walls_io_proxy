<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Service;

use JWeiland\WallsIoProxy\Html\CommentSanitizerBuilder;
use JWeiland\WallsIoProxy\Service\CommentSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test CommentSanitizer
 */
class CommentSanitizerTest extends UnitTestCase
{
    private CommentSanitizer $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new CommentSanitizer(new CommentSanitizerBuilder());
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    #[Test]
    public function sanitizeConvertsNewLinesToBr(): void
    {
        self::assertSame(
            'Line1<br />' . chr(10) . 'Line 2',
            $this->subject->sanitize('Line1' . chr(10) . 'Line 2'),
        );
    }

    #[Test]
    public function sanitizeKeepsPlainTextAndSimpleFormatting(): void
    {
        self::assertSame(
            'Hello <b>World</b> &amp; <em>friends</em>',
            $this->subject->sanitize('Hello <b>World</b> & <em>friends</em>'),
        );
    }

    #[Test]
    public function sanitizeKeepsLinksToHttpAndHttpsTargets(): void
    {
        self::assertSame(
            '<a href="https://example.com/?a=1&amp;b=2">Link</a>',
            $this->subject->sanitize('<a href="https://example.com/?a=1&b=2">Link</a>'),
        );
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function dangerousCommentsDataProvider(): array
    {
        return [
            'Script tag' => ['Hi<script>alert(1)</script>', ['<script']],
            'Script tag with src' => ['<script src="https://evil.example/x.js"></script>', ['<script']],
            'Event handler on image' => ['<img src="/a.png" onerror="alert(1)">', ['onerror']],
            'Event handler on link' => ['<a href="https://example.com" onclick="alert(1)">x</a>', ['onclick']],
            'Event handler on span' => ['<span onmouseover="alert(1)">x</span>', ['onmouseover']],
            'JavaScript link' => ['<a href="javascript:alert(1)">x</a>', ['javascript:']],
            'Obfuscated JavaScript link' => ['<a href="jav&#x09;ascript:alert(1)">x</a>', ['javascript:', 'ascript:alert']],
            'Data link' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>', ['data:text']],
            'Iframe' => ['<iframe src="https://evil.example"></iframe>', ['<iframe']],
            'Object' => ['<object data="x.swf"></object>', ['<object']],
            'SVG with script' => ['<svg onload="alert(1)"><script>alert(1)</script></svg>', ['<svg', '<script']],
            'Style attribute' => ['<span style="background:url(javascript:alert(1))">x</span>', ['style=']],
            'Form' => ['<form action="https://evil.example"><input name="x"></form>', ['<form', '<input']],
            'Meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://evil.example">', ['<meta']],
            'Image with external source' => ['<img src="https://evil.example/pixel.gif">', ['evil.example']],
            'Broken markup' => ['<scr<script>ipt>alert(1)</script>', ['<script']],
        ];
    }

    /**
     * @param list<string> $forbiddenParts
     */
    #[Test]
    #[DataProvider('dangerousCommentsDataProvider')]
    public function sanitizeRemovesOrEncodesDangerousMarkup(string $comment, array $forbiddenParts): void
    {
        $sanitizedComment = $this->subject->sanitize($comment);

        foreach ($forbiddenParts as $forbiddenPart) {
            self::assertStringNotContainsStringIgnoringCase($forbiddenPart, $sanitizedComment);
        }
    }

    #[Test]
    public function sanitizeKeepsImagesOfLocalFileCache(): void
    {
        self::assertStringContainsString(
            'src="/typo3temp/assets/walls_io_proxy/1/abc.png"',
            $this->subject->sanitize('<img src="/typo3temp/assets/walls_io_proxy/1/abc.png" alt="">'),
        );
    }

    #[Test]
    public function sanitizeEncodesUnknownTagsInsteadOfExecutingThem(): void
    {
        self::assertSame(
            '&lt;blink&gt;Text&lt;/blink&gt;',
            $this->subject->sanitize('<blink>Text</blink>'),
        );
    }
}
