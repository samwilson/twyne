<?php

namespace App;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\MarkdownConverter;
use Samwilson\CommonMarkLatex\LatexRendererExtension;

/**
 * Wrapper for rendering Markdown with the league/commonmark package.
 */
class Markdown
{
    /**
     * Convert Markdown to HTML.
     *
     * @param string $in The Markdown source.
     * @return string The (safe) HTML.
     */
    public function toHtml(string $in): string
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => true,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new SmartPunctExtension());
        $converter = new MarkdownConverter($environment);
        return trim($converter->convert($in)->getContent());
    }

    public function toLatex(string $in): string
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => true,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new LatexRendererExtension());
        $converter = new MarkdownConverter($environment);
        return trim($converter->convert($in)->getContent());
    }
}
