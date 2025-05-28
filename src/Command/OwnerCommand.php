<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Command;

use CodeOwners\Cli\FileLocator\FileLocatorFactoryInterface;
use CodeOwners\Cli\PatternMatcherFactoryInterface;
use CodeOwners\Exception\NoMatchFoundException;
use CodeOwners\Pattern;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class OwnerCommand extends Command
{
    private const NAME = 'owner';

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
        $this->workingDirectory = $workingDirectory;
        $this->patternMatcherFactory = $patternMatcherFactory;

        parent::__construct(self::NAME);
    }

    public function configure(): void
    {
        $this
            ->setDescription('Show the owner of the path')
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
            )
            ->addOption(
                'owner-only',
                'o',
                InputOption::VALUE_NONE,
                'Suppress normal output, only output the owner when applicable'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Parsing input parameters:
        $codeownersLocation = $input->getOption('codeowners');
        if (is_string($codeownersLocation) !== true) {
            $codeownersLocation = null;
        }
        $paths = array_filter((array)$input->getArgument('paths'));

        $file = $this->fileLocatorFactory
            ->getFileLocator($this->workingDirectory, $codeownersLocation)
            ->locateFile();

        $output->writeln("Using CODEOWNERS definition from {$file}" . PHP_EOL, OutputInterface::VERBOSITY_VERBOSE);

        if (is_file($file) === false) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $file));
        }

        $matcher = $this->patternMatcherFactory->getPatternMatcher($file);

        foreach ($paths as $path) {
            if (file_exists($this->workingDirectory . '/' . $path) === false) {
                if ($input->getOption('owner-only') !== true) {
                    $output->writeln("🚫 \"{$path}\" does not exist");
                }
                continue;
            }

            try {
                $pattern = $matcher->match($path);

                if ($input->getOption('owner-only') === true) {
                    $output->writeln(join(PHP_EOL, $pattern->getOwners()));
                } else {
                    $owners = $this->formatOwners($pattern);
                    $output->writeln(
                        "✅ \"{$path}\" is owned by {$owners} according to pattern \"{$pattern->getPattern()}\""
                    );
                }
            } catch (NoMatchFoundException $exception) {
                if ($input->getOption('owner-only') !== true) {
                    $output->writeln("🚫 \"{$path}\" has no code owner");
                }
            }
        }

        return 0;
    }

    private function formatOwners(Pattern $pattern): string
    {
        $owners = $pattern->getOwners();
        $last = array_pop($owners);

        return join(
            ', ',
            array_map(function (string $in): string {
                return "\"{$in}\"";
            }, $owners)
        ) .
            (count($owners) === 0 ? '' : ' and ') .
            "\"{$last}\"";
    }
}
