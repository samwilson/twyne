<?php

namespace App\Twig;

use Symfony\Component\Process\Process;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{

    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
            // new TwigFilter('filter_name', [$this, 'doSomething']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('git_tag', [$this, 'gitTag']),
        ];
    }

    /**
     * Get the current Git tag, or the short hash if there's no tags.
     * @return string
     */
    public function gitTag(): string
    {
        $process = new Process(['git', 'describe', '--tags', '--always']);
        $process->run();
        if (!$process->isSuccessful()) {
            $process = new Process(['git', 'rev-parse', '--short', 'HEAD']);
            $process->run();
        }
        return trim($process->getOutput());
    }
}
