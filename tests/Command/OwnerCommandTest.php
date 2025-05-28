<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Tests\Command;

use CodeOwners\Cli\Command\OwnerCommand;
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
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class OwnerCommandTest extends TestCase
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

    public function testCommandDisplaysOwnersFoundForPaths(): void
    {
        $filesystem = vfsStream::setup('root', 444, [
            'CODEOWNERS' => '#',
            'file-a' => '#',
            'file-b' => '#',
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
            ->match('file-a')
            ->shouldBeCalled()
            ->willReturn(new Pattern('file-a', ['@owner-a', '@owner-b']));
        $this->patternMatcher
            ->match('file-b')
            ->shouldBeCalled()
            ->willThrow(NoMatchFoundException::class);

        $this->patternMatcherFactory
            ->getPatternMatcher($filesystem->url() . '/CODEOWNERS')
            ->shouldBeCalled()
            ->willReturn($this->patternMatcher->reveal());

        $command = new OwnerCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->patternMatcherFactory->reveal()
        );

        $output = $this->executeCommand($command, ['paths' => ['file-a', 'file-b', 'file-non-existent']]);

        self::assertMatchesRegularExpression('/"file-a".+"@owner-a" and "@owner-b"/m', $output);
        self::assertMatchesRegularExpression('/"file-b".+no code owner/m', $output);
        self::assertMatchesRegularExpression('/"file-non-existent".+does not exist/m', $output);
    }

    public function testCommandDisplaysOwnersOnlyFoundForPaths(): void
    {
        $filesystem = vfsStream::setup('root', 444, [
            'CODEOWNERS' => '#',
            'file-a' => '#',
            'file-b' => '#',
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
            ->match('file-a')
            ->shouldBeCalled()
            ->willReturn(new Pattern('file-a', ['@owner-a', '@owner-b']));
        $this->patternMatcher
            ->match('file-b')
            ->shouldBeCalled()
            ->willThrow(NoMatchFoundException::class);

        $this->patternMatcherFactory
            ->getPatternMatcher($filesystem->url() . '/CODEOWNERS')
            ->shouldBeCalled()
            ->willReturn($this->patternMatcher->reveal());

        $command = new OwnerCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->patternMatcherFactory->reveal()
        );

        $output = $this->executeCommand(
            $command,
            ['paths' => ['file-a', 'file-b', 'file-non-existent'], '--owner-only' => true]
        );

        self::assertEquals('@owner-a' . PHP_EOL . '@owner-b' . PHP_EOL, $output);
    }

    public function testCommandPassesCodeownerFileLocation(): void
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

        $command = new OwnerCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->patternMatcherFactory->reveal()
        );

        $this->expectException(UnableToLocateFileException::class);
        $this->executeCommand($command, [
            'paths' => [],
            '--codeowners' => 'CODEOWNERS',
        ]);
    }

    public function testCommandThrowsExceptionIfCodeOwnersFileDoesNotExist(): void
    {
        $filesystem = vfsStream::setup('root', 444, []);

        $fileLocator = $this->prophesize(FileLocatorInterface::class);
        $fileLocator->locateFile()
            ->shouldBeCalled()
            ->willReturn($filesystem->url() . '/CODEOWNERS');

        $this->fileLocatorFactory
            ->getFileLocator(Argument::type('string'), Argument::any())
            ->willReturn($fileLocator->reveal());

        $command = new OwnerCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->patternMatcherFactory->reveal()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand($command, ['paths' => ['non-existent-file']]);
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
