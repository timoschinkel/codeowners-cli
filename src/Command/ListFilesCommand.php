<?php
declare(strict_types=1);

namespace CodeOwners\Cli\Command;

use CodeOwners\Cli\FileLocator\FileLocatorFactoryInterface;
use CodeOwners\Cli\PatternMatcherFactoryInterface;
use CodeOwners\Exception\NoMatchFoundException;
use Symfony\Component\Console\Command\Command;
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

    public function __construct(string $workingDirectory, FileLocatorFactoryInterface $fileLocatorFactory, PatternMatcherFactoryInterface $patternMatcherFactory)
    {
        $this->fileLocatorFactory = $fileLocatorFactory;
        $this->workingDirectory = $workingDirectory;
        $this->patternMatcherFactory = $patternMatcherFactory;

        parent::__construct(self::NAME);
    }

    public function configure()
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
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $this->fileLocatorFactory
            ->getFileLocator($this->workingDirectory, $input->getOption('codeowners'))
            ->locateFile();

        $output->writeln("Using CODEOWNERS definition from {$file}" . PHP_EOL, OutputInterface::VERBOSITY_VERBOSE);

        if (is_file($file) === false) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $file));
        }

        $matcher = $this->patternMatcherFactory->getPatternMatcher($file);

        $owner = $input->getArgument('owner');
        $finder = new Finder();

        foreach ($finder->in($input->getArgument('paths'))->files() as $file) {

            try {
                $pattern = $matcher->match($file->getRealPath());

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
