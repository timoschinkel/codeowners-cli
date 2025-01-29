<?php

declare(strict_types=1);

namespace CodeOwners\Cli\FileLocator;

final class FileLocatorFactory implements FileLocatorFactoryInterface
{
    public function getFileLocator(string $workingDirectory, string $specifiedFile = null): FileLocatorInterface
    {
        return $specifiedFile !== null
            ? new SpecifiedFileLocator($specifiedFile)
            : new SearchFileLocator($workingDirectory);
    }
}
