# FyreCommand

**FyreCommand** is a free, open-source CLI command library for *PHP*.


## Table Of Contents
- [Installation](#installation)
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


## Methods

**Add Namespace**

Add a namespace for loading commands.

- `$namespace` is a string representing the namespace.

```php
CommandRunner::addNamespace($namespace);
```

**All**

Get all available commands.

```php
$commands = CommandRunner::all();
```

This method will return an array where the key is the command alias, and the value is an instance of the command.

**Clear**

Clear all namespaces and loaded commands.

```php
CommandRunner::clear();
```

**Get Namespaces**

Get the namespaces.

```php
$namespaces = CommandRunner::getNamespaces();
```

**Handle**

Handle an argv [*Command*](#commands).

- `$argv` is an array containing the CLI arguments.

```php
$code = CommandRunner::handle($argv);
```

**Has Command**

Check if a command exists.

- `$alias` is a string representing the command alias.

```php
$hasCommand = CommandRunner::hasCommand($alias);
```

**Has Namespace**

Check if a namespace exists.

- `$namespace` is a string representing the namespace.

```php
$hasNamespace = CommandRunner::hasNamespace($namespace);
```

**Remove Namespace**

Remove a namespace.

- `$namespace` is a string representing the namespace.

```php
$removed = CommandRunner::removeNamespace($namespace);
```

**Run**

Run a [*Command*](#commands).

- `$alias` is a string representing the command alias.
- `$arguments` is an array containing arguments for the command, and will default to *[]*.

```php
$code = CommandRunner::run($alias, $arguments);
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