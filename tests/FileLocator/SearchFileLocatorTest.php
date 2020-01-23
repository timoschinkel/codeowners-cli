<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Tests\FileLocator;

use CodeOwners\Cli\FileLocator\SearchFileLocator;
use CodeOwners\Cli\FileLocator\UnableToLocateFileException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class SearchFileLocatorTest extends TestCase
{
    /**
     * @dataProvider provideLocateFileReturnsCorrectFile
     * @param array $filesystemStructure
     * @param string $expected
     */
    public function testLocateFileReturnsCorrectFile(array $filesystemStructure, string $expected): void
    {
        $filesystem = vfsStream::setup('root', 444, $filesystemStructure);
        $locator = new SearchFileLocator($filesystem->url());

        self::assertEquals($filesystem->url() . $expected, $locator->locateFile());
    }

    public function provideLocateFileReturnsCorrectFile(): array
    {
        return [
            'root' => [
                ['CODEOWNERS' => '#'],
                '/CODEOWNERS',
            ],
            'prioritize .github over root' => [
                [
                    'CODEOWNERS' => '#',
                    '.github' => ['CODEOWNERS' => '#']
                ],
                '/.github/CODEOWNERS',
            ],
            'prioritize .bitbucket over root' => [
                [
                    'CODEOWNERS' => '#',
                    '.bitbucket' => ['CODEOWNERS' => '#']
                ],
                '/.bitbucket/CODEOWNERS',
            ],
            'prioritize .gitlab over root' => [
                [
                    'CODEOWNERS' => '#',
                    '.gitlab' => ['CODEOWNERS' => '#']
                ],
                '/.gitlab/CODEOWNERS',
            ],
            'prioritize root over docs/' => [
                [
                    'CODEOWNERS' => '#',
                    'docs' => ['CODEOWNERS' => '#']
                ],
                '/CODEOWNERS',
            ],
        ];
    }

    public function testLocateFileThrowsExceptionIfNotFound(): void
    {
        $locator = new SearchFileLocator(__DIR__);

        $this->expectException(UnableToLocateFileException::class);
        $locator->locateFile();
    }
}
