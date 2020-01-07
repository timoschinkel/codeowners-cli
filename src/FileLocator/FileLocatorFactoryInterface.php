<?php

declare(strict_types=1);

namespace CodeOwners\Cli\FileLocator;

interface FileLocatorFactoryInterface
{
    public function getFileLocator(string $workingDirectory, string $specifiedFile = null): FileLocatorInterface;
}
