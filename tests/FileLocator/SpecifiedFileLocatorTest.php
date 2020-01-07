<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Tests\FileLocator;

use CodeOwners\Cli\FileLocator\SpecifiedFileLocator;
use CodeOwners\Cli\FileLocator\UnableToLocateFileException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class SpecifiedFileLocatorTest extends TestCase
{
    public function testLocateFileReturnsFileWhenSpecifiedFileDoesNotExist(): void
    {
        $filesystem = vfsStream::setup('root', 444, [
            'CODEOWNERS' => '#',
        ]);
        $locator = new SpecifiedFileLocator($filesystem->url() . '/CODEOWNERS');

        self::assertEquals($filesystem->url() . '/CODEOWNERS', $locator->locateFile());
    }

    public function testLocateFileThrowsExceptionWhenSpecifiedFileExists(): void
    {
        $filesystem = vfsStream::setup('root', 444, []);
        $locator = new SpecifiedFileLocator($filesystem->url() . '/CODEOWNERS');

        $this->expectException(UnableToLocateFileException::class);
        $locator->locateFile();
    }
}
