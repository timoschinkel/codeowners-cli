<?php

declare(strict_types=1);

namespace CodeOwners\Cli\Command;

use CodeOwners\Cli\FileLocator\FileLocatorFactoryInterface;
use CodeOwners\ParserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListOwnersCommand extends Command
{
    private const NAME = 'list-owners';

    /** @var string */
    private $workingDirectory;

    /** @var FileLocatorFactoryInterface */
    private $fileLocatorFactory;

    /** @var ParserInterface */
    private $parser;

    public function __construct(
        string $workingDirectory,
        FileLocatorFactoryInterface $fileLocatorFactory,
        ParserInterface $parser
    ) {
        $this->workingDirectory = rtrim($workingDirectory, DIRECTORY_SEPARATOR);
        $this->fileLocatorFactory = $fileLocatorFactory;
        $this->parser = $parser;

        parent::__construct(self::NAME);
    }

    public function configure(): void
    {
        $this
            ->setDescription('List all code owners in a CODEOWNERS file')
            ->addOption(
                'codeowners',
                'c',
                InputArgument::OPTIONAL,
                'Location of code owners file, defaults to <working_dir>/CODEOWNERS'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Parsing input parameters:
        $codeownersLocation = $input->getOption('codeowners');
        if (is_string($codeownersLocation) !== true) {
            $codeownersLocation = null;
        }

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

        $owners = [];

        foreach ($this->parser->parseFile($codeownersFile) as $pattern) {
            $owners = array_merge($owners, $pattern->getOwners());
        }

        foreach (array_unique($owners) as $owner) {
            $output->writeln($owner);
        }

        return 0;
    }
}
