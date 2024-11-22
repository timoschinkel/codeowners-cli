<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Command;

use CodeOwners\Cli\FileLocator\FileLocatorFactoryInterface;
use CodeOwners\Cli\PatternMatcherFactoryInterface;
use CodeOwners\Exception\NoMatchFoundException;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

final class ListUnownedFilesCommand extends Command
{
    private const NAME = 'list-unowned-files';

    /** @var string */
    private $workingDirectory;

    /** @var FileLocatorFactoryInterface */
    private $fileLocatorFactory;

    /** @var PatternMatcherFactoryInterface */
    private $patternMatcherFactory;

    public function __construct(
        string $workingDirectory,
        FileLocatorFactoryInterface $fileLocatorFactory,
        PatternMatcherFactoryInterface $patternMatcherFactory
    ) {
        $this->fileLocatorFactory = $fileLocatorFactory;
        $this->workingDirectory = rtrim($workingDirectory, DIRECTORY_SEPARATOR);
        $this->patternMatcherFactory = $patternMatcherFactory;

        parent::__construct(self::NAME);
    }

    public function configure(): void
    {
        $this
            ->setDescription('List files that are not marked as owned by a code owner separated by newlines')
            ->addArgument(
                'paths',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Paths to files or directories to show code owner, separate with spaces'
            )
            ->addOption(
                'codeowners',
                'c',
                InputArgument::OPTIONAL,
                'Location of code owners file, defaults to <working_dir>/CODEOWNERS'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Parsing input parameters:
        $codeownersLocation = $input->getOption('codeowners');
        if (is_string($codeownersLocation) !== true) {
            $codeownersLocation = null;
        }
        $paths = $this->normalizePaths((array)$input->getArgument('paths'));

        $codeownersFile = $this->fileLocatorFactory
            ->getFileLocator($this->workingDirectory, $codeownersLocation)
            ->locateFile();

        $output->writeln(
            "Using CODEOWNERS definition from {$codeownersFile}" . PHP_EOL,
            OutputInterface::VERBOSITY_VERBOSE
        );

        if (is_file($codeownersFile) === false) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $codeownersFile));
        }

        $matcher = $this->patternMatcherFactory->getPatternMatcher($codeownersFile);

        $finder = new Finder();

        foreach ($finder->in($paths)->files() as $file) {
            /** @var SplFileInfo $file */
            try {
                $filePath = (string)$file;
                if (strpos($filePath, $this->workingDirectory . '/') === 0) {
                    $filePath = str_replace($this->workingDirectory . '/', '', $filePath);
                }

                $matcher->match($filePath);
            } catch (NoMatchFoundException $exception) {
                $output->writeln((string)$file);
            }
        }

        return 0;
    }

    private function normalizePaths(array $paths): array
    {
        // This will return the path as given if `realpath` returns `false`. One of the reasons is that vfsStream does
        // not support `realpath`.
        return array_map(
            function (string $path): string {
                return realpath($path) !== false ? realpath($path) : $path;
            },
            $paths
        );
    }
}
