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
            ["auf\n\n* *emph* word\n* bar", "<p>auf</p>\n\n<ul>\n<li><em>emph</em> word</li>\n<li>bar</li>\n</ul>"],
            ["1. foo\n2. bar\n", "<ol>\n<li>foo</li>\n<li>bar</li>\n</ol>"],
            ["1. foo\n2. bar\n\nbaz", "<ol>\n<li>foo</li>\n<li>bar</li>\n</ol>\n\n<p>baz</p>"],
            // Emphasis and code.
            ['the *foo* `bar` baz', '<p>the <em>foo</em> <code>bar</code> baz</p>'],
            // Blockquotes.
            ["foo\n\n> bar\n> baz\n>last", "<p>foo</p>\n\n<blockquote>\nbar\nbaz\nlast\n</blockquote>"],
            // Separator.
            ["foo\n\n---\n\nbar", "<p>foo</p>\n\n<hr />\n\n<p>bar</p>"],
            // Header.
            ["# Foo", "<h3>Foo</h3>"],
            ["##Foo", "<h3>Foo</h3>"],
            ["###Foo", "<h3>Foo</h3>"],
            ["#### Foo", "<h4>Foo</h4>"],
        ];
    }
}
