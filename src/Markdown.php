<?php

namespace App;

/**
 * This class formats *some parts* of Markdown, or rather CommonMark. It doesn't support everything (notably not HTML),
 * and also adds a few extra things that are good only for this application.
 *
 * @link https://commonmark.org/help/
 */
class Markdown
{

    /**
     * Convert Twyne-flavoured Markdown to HTML.
     *
     * @param string $in The Markdown source.
     * @return string The (safe) HTML.
     */
    public function toHtml(string $in): string
    {
        // Platform-independent newlines.
        $out = preg_replace("/(\r\n|\r)/", "\n", trim($in));
        // Prevent HTML.
        $out = str_replace(['&', '<', '"', "'"], ['&amp;', '&lt;', '&quot;', '&#039;'], $out);
        $out = preg_replace("|([^\n])>|", "$1&gt;", $out);
        // Links.
        $out = preg_replace("|(https?://\S+)|", "<a href=\"$1\">$1</a>", $out);
        // Paragraphs.
        $out = "<p>$out</p>";
        $out = preg_replace("|\n+\s*\n+|", "</p>\n\n<p>", $out);
        // Remove paragraphs if they contain nothing (including only whitespace).
        $out = preg_replace('|<p>\s*</p>|', '', $out);
        // Blockquotes
        $out = preg_replace('|<p>>\s*(.*)|', "<blockquote>\n> $1", $out);
        $out = preg_replace('|\n>\s*(.*)</p>|', "\n$1\n</blockquote>", $out);
        $out = preg_replace('|\n>\s(.*)|', "\n$1", $out);
        // Separator.
        $out = preg_replace("|<p>[-*]{3}</p>|", "<hr />", $out);
        // Lists.
        $out = preg_replace("|<p>\d\.(.*)|", "<ol>\n#$1", $out); // begin ordered
        $out = preg_replace("|\n\d\.(.*)</p>|", "\n#$1\n</ol>", $out); // end ordered
        $out = preg_replace("|<p>\*(.*)|", "<ul>\n*$1", $out); // begin unordered
        $out = preg_replace("|\n\*(.*)</p>|", "\n*$1\n</ul>", $out); // end unordered
        $out = preg_replace("|\n[*#]\s*(.*)|", "\n<li>$1</li>", $out); // list items
        // Headings.
        $out = preg_replace("|<p>#{4}\s*(.*)</p>|", "\n<h4>$1</h4>\n", $out);
        $out = preg_replace("|<p>#{1,3}\s*(.*)</p>|", "\n<h3>$1</h3>\n", $out);
        // Emphasis.
        $out = preg_replace("|\*(.*?)\*|", "<em>$1</em>", $out);
        // Monospacing.
        $out = preg_replace("|`(.*?)`|", "<code>$1</code>", $out);

        return trim($out);
    }
}
