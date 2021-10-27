<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Tests\Command;

use CodeOwners\Cli\Command\ListOwnersCommand;
use CodeOwners\Cli\FileLocator\FileLocatorFactoryInterface;
use CodeOwners\Cli\FileLocator\FileLocatorInterface;
use CodeOwners\Cli\FileLocator\UnableToLocateFileException;
use CodeOwners\Cli\PatternMatcherFactoryInterface;
use CodeOwners\ParserInterface;
use CodeOwners\Pattern;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

final class ListOwnersCommandTest extends TestCase
{
    use ProphecyTrait;

    /** @var FileLocatorFactoryInterface|ObjectProphecy */
    private $fileLocatorFactory;

    /** @var PatternMatcherFactoryInterface|ObjectProphecy */
    private $patternMatcherFactory;

    /** @var ParserInterface|ObjectProphecy */
    private $parser;

    protected function setUp(): void
    {
        $this->fileLocatorFactory = $this->prophesize(FileLocatorFactoryInterface::class);
        $this->patternMatcherFactory = $this->prophesize(PatternMatcherFactoryInterface::class);
        $this->parser = $this->prophesize(ParserInterface::class);

        parent::setUp();
    }

    public function testCommandDisplaysOwnersFoundInCodeOwnersFile(): void
    {
        $filesystem = vfsStream::setup('root', 444, [
            'CODEOWNERS' => '#',
        ]);

        $fileLocator = $this->prophesize(FileLocatorInterface::class);
        $fileLocator->locateFile()
            ->shouldBeCalled()
            ->willReturn($filesystem->url() . '/CODEOWNERS');

        $this->fileLocatorFactory
            ->getFileLocator(Argument::type('string'), null)
            ->shouldBeCalled()
            ->willReturn($fileLocator->reveal());

        $this->parser
            ->parseFile($filesystem->url() . '/CODEOWNERS')
            ->shouldBeCalled()
            ->willReturn([
                new Pattern('pattern 01', ['owner 01', 'owner 02']),
                new Pattern('pattern 02', ['owner 01', 'owner 03']),
                new Pattern('pattern 03', ['owner 04']),
            ]);

        $command = new ListOwnersCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->parser->reveal()
        );

        $output = $this->executeCommand($command, []);
        self::assertEquals(
            join(PHP_EOL, ['owner 01', 'owner 02', 'owner 03', 'owner 04']) . PHP_EOL,
            $output
        );
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

        $command = new ListOwnersCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->parser->reveal()
        );

        $this->expectException(UnableToLocateFileException::class);
        $this->executeCommand($command, [
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

        $command = new ListOwnersCommand(
            $filesystem->url(),
            $this->fileLocatorFactory->reveal(),
            $this->parser->reveal()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->executeCommand($command, []);
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
