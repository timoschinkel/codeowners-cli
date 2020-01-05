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

[codeowners]: https://packagist.org/packages/timoschinkel/codeowners
[composer]: https://www.getcomposer.org
