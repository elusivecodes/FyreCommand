# FyreCommand

**FyreCommand** is a free, open-source CLI command library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)
- [Commands](#commands)
    - [Aliases](#aliases)
    - [Options](#options)



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

- `$container` is a [*Container*](https://github.com/elusivecodes/FyreContainer).
- `$inflector` is an [*Inflector*](https://github.com/elusivecodes/FyreInflector).
- `$loader` is a [*Loader*](https://github.com/elusivecodes/FyreLoader).
- `$io` is a [*Console*](https://github.com/elusivecodes/FyreConsole).
- `$namespaces` is an array containing the namespaces.

```php
$runner = new CommandRunner($container, $inflector, $loader, $io, $namespaces);
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

Command [options](#options) will be parsed from the provided arguments.


## Commands

Custom commands can be created by extending `\Fyre\Command\Command`, suffixing the class name with "*Command*", and ensuring a `run` method is implemented.

Any dependencies will be resolved automatically from the [*Container*](https://github.com/elusivecodes/FyreContainer).

The `run` method should return an integer representing the command exit code. The class constants `Command::CODE_SUCCESS` and `Command::CODE_ERROR` can be used.

### Aliases

You can define `$alias` and `$description` properties on the command. If no `$alias` is a provided, the command class name will be used (converted to snake_case).

### Options

You can also define an `$options` array on your custom commands, which will be used by the *CommandRunner* to parse the arguments and prompt for input if required.

- `prompt` is a string representing the prompt text, and will default to "".
- `values` is an array containing the values, and will default to *null*.
- `boolean` is a boolean indicating whether the option is a boolean value, and will default to *false*.
- `required` is a boolean indicating whether a value must be provided, and will default to *false*.
- `default` is the default value, and will default to *true*.

```php
protected array $options = [
    'name' => [
        'prompt' => 'What is your name?',
        'required' => true,
    ],
    'color' => [
        'prompt' => 'What is your favorite color?',
        'values' => [
            'red',
            'green',
            'blue',
        ],
    ],
    'confirmed' => [
        'prompt' => 'Do you want to continue?',
        'boolean' => true,
        'required' => true,
    ],
];

public function run(string $name, string|null $color, bool $confirmed): int;
```

If an option is marked as `required` and not provided as an argument, the *CommandRunner* will prompt for the value, otherwise the `default` value will be used.