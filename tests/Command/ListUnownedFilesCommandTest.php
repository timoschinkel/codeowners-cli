<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Tests\Command;

use CodeOwners\Cli\Command\ListUnownedFilesCommand;
use CodeOwners\Cli\FileLocator\FileLocatorFactoryInterface;
use CodeOwners\Cli\FileLocator\FileLocatorInterface;
use CodeOwners\Cli\FileLocator\UnableToLocateFileException;
use CodeOwners\Cli\PatternMatcherFactoryInterface;
use CodeOwners\Exception\NoMatchFoundException;
use CodeOwners\Pattern;
use CodeOwners\PatternMatcherInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ListUnownedFilesCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var FileLocatorFactoryInterface|ObjectProphecy */
    private $fileLocatorFactory;

    /** @var PatternMatcherFactoryInterface|ObjectProphecy */
    private $patternMatcherFactory;

    /** @var PatternMatcherInterface|ObjectProphecy */
    private $patternMatcher;

    protected function setUp(): void
    {
        $this->fileLocatorFactory = $this->prophesize(FileLocatorFactoryInterface::class);
        $this->patternMatcherFactory = $this->prophesize(PatternMatcherFactoryInterface::class);
        $this->patternMatcher = $this->prophesize(PatternMatcherInterface::class);

        parent::setUp();
    }

    public function testCommandListFilesOwnedBySpecifiedOwner(): void
    {
        $filesystem = vfsStream::setup('root', 444, [
            'CODEOWNERS' => '#',
            'folder' => [
                'owned-by-owner' => '#',
                'not-owned-by-owner' => '#',
            ],
        ]);

        $fileLocator = $this->prophesize(FileLocatorInterface::class);
        $fileLocator->locateFile()
            ->shouldBeCalled()
            ->willReturn($filesystem->url() . '/CODEOWNERS');

        $this->fileLocatorFactory
            ->getFileLocator(Argument::type('string'), null)
            ->shouldBeCalled()
            ->willReturn($fileLocator->reveal());

        $this->patternMatcher
            ->match('folder/owned-by-owner')
            ->willReturn(new Pattern('*', ['@owner']));

        $this->patternMatcher
            ->match('folder/not-owned-by-owner')
            ->willThrow(NoMatchFoundException::class);

        $this->patternMatcherFactory
            ->getPatternMatcher($filesystem->url() . '/CODEOWNERS')
            ->willReturn($this->patternMatcher->reveal());

        $command = new ListUnownedFilesCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->patternMatcherFactory->reveal()
        );

        $output = $this->executeCommand($command, [
            'paths' => [
                $filesystem->url() . '/folder',
            ]
        ]);

        self::assertEquals(
            $filesystem->url() . '/folder/not-owned-by-owner' . PHP_EOL,
            $output
        );
    }

    public function testCommandThrowsExceptionWhenCodeownersFileCannotBeFound(): void
    {
        $filesystem = vfsStream::setup('root', 444, []);

        $fileLocator = $this->prophesize(FileLocatorInterface::class);
        $fileLocator->locateFile()
            ->shouldBeCalled()
            ->willThrow(UnableToLocateFileException::class);

        $this->fileLocatorFactory
            ->getFileLocator(Argument::type('string'), null)
            ->shouldBeCalled()
            ->willReturn($fileLocator->reveal());

        $command = new ListUnownedFilesCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->patternMatcherFactory->reveal()
        );

        $this->expectException(UnableToLocateFileException::class);
        $this->executeCommand($command, [
            'paths' => []
        ]);
    }

    public function testCommandPassesSpecifiedCodeownersFileToFileLocator(): void
    {
        $filesystem = vfsStream::setup('root', 444, []);

        $fileLocator = $this->prophesize(FileLocatorInterface::class);
        $fileLocator->locateFile()
            ->shouldBeCalled()
            ->willThrow(UnableToLocateFileException::class);

        $this->fileLocatorFactory
            ->getFileLocator(Argument::type('string'), 'CODEOWNERS')
            ->shouldBeCalled()
            ->willReturn($fileLocator->reveal());

        $command = new ListUnownedFilesCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->patternMatcherFactory->reveal()
        );

        $this->expectException(UnableToLocateFileException::class);
        $this->executeCommand($command, [
            'paths' => [],
            '--codeowners' => 'CODEOWNERS'
        ]);
    }

    private function executeCommand(Command $command, array $parameters): string
    {
        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($application->find($command->getName()));
        $tester->execute($parameters);

        return $tester->getDisplay();
    }
}
