<?php

declare(strict_types=1);

namespace CodeOwners\Cli\FileLocator;

final class SearchFileLocator implements FileLocatorInterface
{
    /** @var string */
    private $workingDirectory;

    public function __construct(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @return string
     * @throws UnableToLocateFileException
     */
    public function locateFile(): string
    {
        // Direct answer from Github: order is `.github/` > `root` > `docs/`
        // This is extrapolated to Gitlab and Bitbucket
        $suggestions = [
            "{$this->workingDirectory}/.github/CODEOWNERS",
            "{$this->workingDirectory}/.bitbucket/CODEOWNERS",
            "{$this->workingDirectory}/.gitlab/CODEOWNERS",
            "{$this->workingDirectory}/CODEOWNERS",
            "{$this->workingDirectory}/docs/CODEOWNERS",
        ];

        foreach ($suggestions as $suggestion) {
            if (file_exists($suggestion) === true) {
                return $suggestion;
            }
        }

        throw new UnableToLocateFileException("No CodeOwners file could be found");
    }
}
