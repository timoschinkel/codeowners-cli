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

final class ListFilesCommand extends Command
{
    private const NAME = 'list-files';

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
            ->setDescription('List files owned by the specified code owner separated by newlines')
            ->addArgument(
                'owner',
                InputArgument::REQUIRED,
                'Codeowner for which the files should be listed'
            )
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
        $owner = $input->getArgument('owner');
        $paths = array_filter(array_map('realpath', (array)$input->getArgument('paths')));

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
                $filePath = $file->getRealPath();
                $pattern = $matcher->match($filePath);

                if (in_array($owner, $pattern->getOwners())) {
                    $output->writeln($file->getRealPath());
                }
            } catch (NoMatchFoundException $exception) {
                // we can ignore this
            }
        }

        return 0;
    }
}
