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
            ["foo\n\nbar", "<p>foo</p>\n<p>bar</p>"],
            // Lists.
            ["auf\n\n* *emph* word\n* bar", "<p>auf</p>\n<ul>\n<li>\n<em>emph</em> word</li>\n<li>bar</li>\n</ul>"],
            ["1. foo\n2. bar\n", "<ol>\n<li>foo</li>\n<li>bar</li>\n</ol>"],
            ["1. foo\n2. bar\n\nbaz", "<ol>\n<li>foo</li>\n<li>bar</li>\n</ol>\n<p>baz</p>"],
            ['Not a list 10.20 just numbers.', '<p>Not a list 10.20 just numbers.</p>'],
            // Emphasis and code.
            ['the *foo* `bar` baz', '<p>the <em>foo</em> <code>bar</code> baz</p>'],
            // Blockquotes.
            ["foo\n\n> bar\n> baz\n>last", "<p>foo</p>\n<blockquote>\n<p>bar\nbaz\nlast</p>\n</blockquote>"],
            ["> foo\n\nbar", "<blockquote>\n<p>foo</p>\n</blockquote>\n<p>bar</p>"],
            // Separator.
            ["foo\n\n---\n\nbar", "<p>foo</p>\n<hr />\n<p>bar</p>"],
            // Header.
            ["# Foo", "<h1>Foo</h1>"],
            ["## Foo", "<h2>Foo</h2>"],
            ["### Foo", "<h3>Foo</h3>"],
            ["#### Foo", "<h4>Foo</h4>"],
            // Links.
            ['foo https://example.org bar', '<p>foo <a href="https://example.org">https://example.org</a> bar</p>'],
            ['https://x.net/foo_bar', '<p><a href="https://x.net/foo_bar">https://x.net/foo_bar</a></p>'],
            // No HTML.
            ['a <p>T & <stuff x="y"></p>', '<p>a &lt;p&gt;T &amp; &lt;stuff x="y"&gt;&lt;/p&gt;</p>']
        ];
    }
}
