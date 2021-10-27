<?php

declare(strict_types=1);

namespace CodeOwners\Cli;

use CodeOwners\Parser;
use CodeOwners\PatternMatcher;
use CodeOwners\PatternMatcherInterface;

final class PatternMatcherFactory implements PatternMatcherFactoryInterface
{
    public function getPatternMatcher(string $file): PatternMatcherInterface
    {
        return new PatternMatcher(...(new Parser())->parseFile($file));
    }
}
