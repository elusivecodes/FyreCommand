# FyreCommand

**FyreCommand** is a free, open-source CLI command library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)
- [Commands](#commands)



## Installation

**Using Composer**

```
composer require fyre/command
```

In PHP:

```php
use Fyre\Command\CommandRunner;
```


## Basic Usage

- `$container` is a  [*Container*](https://github.com/elusivecodes/FyreContainer).
- `$loader` is a [*Loader*](https://github.com/elusivecodes/FyreLoader).
- `$io` is a [*Console*](https://github.com/elusivecodes/FyreConsole).
- `$namespaces` is an array containing the namespaces.

```php
$runner = new CommandRunner($container, $loader, $io, $namespaces);
```

**Autoloading**

It is recommended to bind the *CommandRunner* to the [*Container*](https://github.com/elusivecodes/FyreContainer) as a singleton.

```php
$container->singleton(CommandRunner::class);
```

Any dependencies will be injected automatically when loading from the [*Container*](https://github.com/elusivecodes/FyreContainer).

```php
$runner = $container->use(CommandRunner::class);
```


## Methods

**Add Namespace**

Add a namespace for loading commands.

- `$namespace` is a string representing the namespace.

```php
$runner->addNamespace($namespace);
```

**All**

Get all available commands.

```php
$commands = $runner->all();
```

This method will return an array where the key is the command alias, and the value is an instance of the command.

**Clear**

Clear all namespaces and loaded commands.

```php
$runner->clear();
```

**Get Namespaces**

Get the namespaces.

```php
$namespaces = $runner->getNamespaces();
```

**Handle**

Handle an argv [*Command*](#commands).

- `$argv` is an array containing the CLI arguments.

```php
$code = $runner->handle($argv);
```

**Has Command**

Check if a command exists.

- `$alias` is a string representing the command alias.

```php
$hasCommand = $runner->hasCommand($alias);
```

**Has Namespace**

Check if a namespace exists.

- `$namespace` is a string representing the namespace.

```php
$hasNamespace = $runner->hasNamespace($namespace);
```

**Remove Namespace**

Remove a namespace.

- `$namespace` is a string representing the namespace.

```php
$runner->removeNamespace($namespace);
```

**Run**

Run a [*Command*](#commands).

- `$alias` is a string representing the command alias.
- `$arguments` is an array containing arguments for the command, and will default to *[]*.

```php
$code = $runner->run($alias, $arguments);
```


## Commands

Custom commands can be created by extending `\Fyre\Command\Command`, suffixing the class name with "*Command*", and ensuring the `run` method is implemented.

**Alias**

Get the command alias.

```php
$alias = $command->getAlias();
```

The alias can be set by defining the `$alias` property on the command, otherwise the class name will be used by default.

**Get Description**

Get the command description.

```php
$description = $command->getDescription();
```

The description can be set by defining the `$description` property on the command.

**Get Name**

Get the command name.

```php
$name = $command->getName();
```

The name can be set by defining the `$name` property on the command, otherwise the class name will be used by default.

**Run**

Run the command.

- `$arguments` is an array containing the command arguments.

```php
$code = $command->run($arguments);
```

This method should return an integer representing the command exit code. The class constants `Command::CODE_SUCCESS` and `Command::CODE_ERROR` can be used.