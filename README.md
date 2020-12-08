# Code owners CLI

Code owners CLI is a CLI interface to simplify common operations on Code owners files using [`timoschinkel/codeowners`][codeowners].

## Installation
Use [Composer][composer] for installation:

```bash
composer require timoschinkel/codeowners-cli
```

If you don't want Code owners CLI to be part of your project to can opt to install it globally:

```bash
composer global require timoschinkel/codeowners-cli
```

## Usage
When installed as dependency of your project:

```bash
./vendor/bin/codeowners [options] <command>
```

When installed globally:

```bash
codeowners [options] <command>
```

**NB** When installed globally you will need to install Composer itself globally add the global Composer binary folder to your `PATH` variable, eg by adding the following line to `~/.bash_profile` or `~/.bashrc`:

```bash
export PATH=~/.composer/vendor/bin:$PATH
```

All commands have the options supplied by Symfony Console:

* `-q`, `--quiet`; Do no output any message
* `-v|vv|vvv`, `--verbose`; Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

When no CODEOWNERS file is specified - using `-c` or `--codeowners` - the application will search the CODEOWNERS file in the following locations based on the working directory:
* `<working_dir>/.github/CODEOWNERS`
* `<working_dir>/.bitbucket/CODEOWNERS`
* `<working_dir>/.gitlab/CODEOWNERS`
* `<working_dir>/CODEOWNERS`
* `<working_dir>/docs/CODEOWNERS`

Calling the command with the verbose option will print what file is used when applicable.

### Available commands
#### `owner`
Shows the owner of the path(s) passed as parameter.

```bash
Usage:
  owner [options] [--] <paths>...

Arguments:
  paths                        Paths to files or directories to show code owner, separate with spaces

Options:
  -c, --codeowners=CODEOWNERS  Location of code owners file, defaults to <working_dir>/CODEOWNERS
```

For example:

```bash
codeowners owner ./src
```

#### `list-files`
Shows all files for an owner:

```bash
Usage:
  list-files [options] [--] <owner> <paths>...

Arguments:
  owner                        Codeowner for which the files should be listed
  paths                        Paths to files or directories to show code owner, separate with spaces

Options:
  -c, --codeowners=CODEOWNERS  Location of code owners file, defaults to <working_dir>/CODEOWNERS
``` 

For example:

```bash
codeowners list-files @team ./src
```

The output of this command can be used to feed into other tools using `xargs`:

```bash
codeowners list-files @team ./src | xargs <command>
```

#### `list-unowned-files`
Shows all files that don't have an owner specified:

```bash
Usage:
  list-unowned-files [options] [--] <paths>...

Arguments:
  paths                        Paths to files or directories to show code owner, separate with spaces

Options:
  -c, --codeowners=CODEOWNERS  Location of code owners file, defaults to <working_dir>/CODEOWNERS
```

For example:

```bash
codeowners list-unowned-files ./src
```

#### `list-owners`
Shows all available owners inside the found CODEOWNERS file.

```bash
Usage:
  list-owners [options]

Options:
  -c, --codeowners=CODEOWNERS  Location of code owners file, defaults to <working_dir>/CODEOWNERS
```

For example:

```bash
codeowners list-owners
```

[codeowners]: https://packagist.org/packages/timoschinkel/codeowners
[composer]: https://www.getcomposer.org
