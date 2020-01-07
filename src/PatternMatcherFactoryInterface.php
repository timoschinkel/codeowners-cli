<?php

declare(strict_types=1);

namespace CodeOwners\Cli;

use CodeOwners\PatternMatcherInterface;

interface PatternMatcherFactoryInterface
{
    public function getPatternMatcher(string $file): PatternMatcherInterface;
}
