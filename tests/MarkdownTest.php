<?php

namespace App\Tests;

use App\Markdown;
use PHPUnit\Framework\TestCase;

class MarkdownTest extends TestCase
{

    /**
     * @dataProvider provideToHtml()
     * @param $markdown
     * @param $expected
     */
    public function testToHtml($markdown, $expected)
    {
        $md = new Markdown();
        $this->assertSame($expected, $md->toHtml($markdown));
    }

    public function provideToHtml()
    {
        return [
            // Paragraphs.
            ['foo', '<p>foo</p>'],
            ["foo\nbar", "<p>foo\nbar</p>"],
            ["foo\n\nbar", "<p>foo</p>\n\n<p>bar</p>"],
            // Lists.
            ["auf\n\n* foo\n* bar", "<p>auf</p>\n\n<ul>\n<li>foo</li>\n<li>bar</li>\n</ul>"],
            ["# foo\n# bar\n\nbaz", "<ol>\n<li>foo</li>\n<li>bar</li>\n</ol>\n\n<p>baz</p>"],
            // Emphasis.
            ['the *foo* bar', '<p>the <em>foo</em> bar</p>'],
            // Blockquotes.
            ["foo\n\n> bar\n> baz\n>last", "<p>foo</p>\n\n<blockquote>\nbar\nbaz\nlast\n</blockquote>"],
        ];
    }
}
