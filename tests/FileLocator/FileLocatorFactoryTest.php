<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Tests\FileLocator;

use CodeOwners\Cli\FileLocator\FileLocatorFactory;
use CodeOwners\Cli\FileLocator\SearchFileLocator;
use CodeOwners\Cli\FileLocator\SpecifiedFileLocator;
use PHPUnit\Framework\TestCase;

final class FileLocatorFactoryTest extends TestCase
{
    public function testGetFileLocatorReturnsSpecifiedFileLocator(): void
    {
        $factory = new FileLocatorFactory();
        self::assertInstanceOf(
            SpecifiedFileLocator::class,
            $factory->getFileLocator(__DIR__, __FILE__)
        );
    }

    public function testGetFileLocatorReturnsSearchFileLocator(): void
    {
        $factory = new FileLocatorFactory();
        self::assertInstanceOf(
            SearchFileLocator::class,
            $factory->getFileLocator(__DIR__, null)
        );
    }
}
