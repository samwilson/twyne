<?php

namespace App;

class Markdown
{

    public function toHtml(string $in): string
    {
        // Platform-independent newlines.
        $out = preg_replace("/(\r\n|\r)/", "\n", $in);
        // Paragraphs.
        $out = "<p>$out</p>";
        $out = preg_replace("|\n+\s*\n+|", "</p>\n\n<p>", $out);
        // Remove paragraphs if they contain nothing (including only whitespace).
        $out = preg_replace('|<p>\s*</p>|', '', $out);
        // Blockquotes
        $out = preg_replace('|<p>>\s*(.*)|', "<blockquote>\n$1", $out);
        $out = preg_replace('|\n>\s*(.*)</p>|', "\n$1\n</blockquote>", $out);
        $out = preg_replace('|\n>\s(.*)|', "\n$1", $out);
        // Emphasis.
        $out = preg_replace("|\*(.*?)\*|", "<em>$1</em>", $out);
        // Monospacing.
        $out = preg_replace("|`(.*?)`|", "<code>$1</code>", $out);
        // Em Dashes.
        $out = preg_replace("/---/", "&thinsp;&mdash;&thinsp;", $out);
        // Ellipses.
        $out = preg_replace("/\.\.\./", "&thinsp;&hellip;&thinsp;", $out);
        // Lists.
        $out = preg_replace("|<p>#(.*)|", "<ol>\n#$1", $out); // begin ordered
        $out = preg_replace("|#(.*)</p>|", "#$1\n</ol>", $out); // end ordered
        $out = preg_replace("|<p>\*(.*)|", "<ul>\n*$1", $out); // begin unordered
        $out = preg_replace("|\*(.*)</p>|", "*$1\n</ul>", $out); // end unordered
        $out = preg_replace("|[*#]\s*(.*)|", "<li>$1</li>", $out); // list items
        // Headings.
        $out = preg_replace("|<p>===\s*(.*)\s*===</p>|", "\n<h3>$1</h3>\n", $out);

        return trim($out);
    }
}
