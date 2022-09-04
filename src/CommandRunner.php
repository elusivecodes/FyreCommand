<?php
declare(strict_types=1);

namespace Fyre\Command;

use
    Fyre\Console\Console,
    Fyre\FileSystem\Folder,
    Fyre\Loader\Loader,
    InvalidArgumentException,
    ReflectionClass,
    Throwable;

use function    
    array_filter,
    array_map,
    array_pop,
    array_shift,
    class_exists,
    explode,
    implode,
    in_array,
    is_subclass_of,
    lcfirst,
    preg_match,
    preg_replace,
    str_ends_with,
    str_replace,
    strtolower,
    substr,
    trim,
    ucwords;

/**
 * CommandRunner
 */
abstract class CommandRunner
{

    protected static array $namespaces = [];

    /**
     * Add a namespace for loading commands.
     * @param string $namespace The namespace.
     */
    public static function addNamespace(string $namespace): void
    {
        $namespace = static::normalizeNamespace($namespace);

        if (!in_array($namespace, static::$namespaces)) {
            static::$namespaces[] = $namespace;
        }
    }

    /**
     * Get all available commands.
     * @return array The available commands.
     */
    public static function all(): array
    {
        $commands = [];

        foreach (static::$namespaces AS $namespace) {
            $pathParts = [];
            $namespaceCommands = [];
            $namespaceParts = explode('\\', $namespace);
            $namespaceParts = array_filter($namespaceParts);

            do {
                $currentNamespace = implode('\\', $namespaceParts).'\\';
                $paths = Loader::getNamespace($currentNamespace);

                foreach ($paths AS $path) {
                    $fullPath = $path;
                    if ($pathParts !== []) {
                        $fullPath .= '/'.implode('/', $pathParts);
                    }

                    $folder = new Folder($fullPath);
                    static::findCommands($folder, $namespace, $namespaceCommands);
                }

                $pathParts[] = array_pop($namespaceParts);
            } while ($namespaceParts !== []);


            $commands[$namespace] = $namespaceCommands;
        }

        return $commands;
    }

    /**
     * Clear all namespaces.
     */
    public static function clear(): void
    {
        static::$namespaces = [];
    }

    /**
     * Handle an argv command.
     * @param array $argv The CLI arguments.
     * @return int The exit code of the command.
     */
    public static function handle(array $argv): int
    {
        [$command, $arguments] = static::parseArguments($argv);

        if ($command) {
            try {
                return static::run($command, $arguments);
            } catch (Throwable $e) {
                Console::error($e->getMessage());

                return Command::CODE_ERROR;
            }
        }

        $allCommands = static::all();

        $data = [];
        foreach ($allCommands AS $namespace => $commands) {
            foreach ($commands AS $commandName => $info) {
                $command = substr($commandName, 0, -7);
                $command = static::commandify($command);

                $data[] = [
                    Console::color($command, ['foreground' => 'green']),
                    $info['name'],
                    $info['description']
                ];
            }
        }

        Console::table($data, ['Command', 'Name', 'Description']);

        return Command::CODE_SUCCESS;
    }

    /**
     * Run a command.
     * @param string $command The command.
     * @param array $arguments The arguments.
     * @return int The exit code.
     * @throws InvalidArgumentException if the command is not valid.
     */
    public static function run(string $command, array $arguments = []): int
    {
        $segments = explode('/', $command);
        $segments = array_filter($segments);

        $segments = array_map(
            fn(string $segment): string => static::classify($segment),
            $segments
        );

        $commandSegments = implode('\\', $segments);

        foreach (static::$namespaces AS $namespace) {
            $class = $namespace.$commandSegments.'Command';

            if (!class_exists($class)) {
                continue;
            }

            $command = new $class;

            return $command->run($arguments) ?? Command::CODE_SUCCESS;
        }

        throw new InvalidArgumentException('Invalid command: '.$command);
    }

    /**
     * Convert a string as a class name.
     * @param string $string The input string.
     * @return string The class name.
     */
    protected static function classify(string $string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);

        return $string;
    }

    /**
     * Convert a string as a command name.
     * @param string $string The input string.
     * @return string The command name.
     */
    protected static function commandify(string $string): string
    {
        $string = preg_replace('/(?<!^)[A-Z]/', '_\0', $string);
        $string = strtolower($string);

        return $string;
    }

    /**
     * Find commands in a Folder.
     * @param Folder $folder The Folder.
     * @param string $namespace The root namespace.
     * @param array $commands The commands.
     * @param string $prefix The command prefix.
     */
    protected static function findCommands(Folder $folder, string $namespace, array &$commands, string $prefix = ''): void
    {
        $children = $folder->contents();

        foreach ($children AS $child) {
            if ($child instanceof Folder) {
                $commandName = $prefix.$child->name();
    
                static::findCommands($child, $namespace, $commands, $commandName.'\\');
                continue;
            }

            if ($child->extension() !== 'php') {
                continue;
            }

            $name = $child->fileName();

            if (!str_ends_with($name, 'Command')) {
                continue;
            }

            $commandName = $prefix.$name;

            $className = $namespace.$commandName;

            if (!class_exists($className) || !is_subclass_of($className, Command::class)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            $command = new $className;

            $commands[$commandName] = [
                'name' => $command->getName(),
                'description' => $command->getDescription()
            ];
        }
    }

    /**
     * Normalize a namespace
     * @param string $namespace The namespace.
     * @return string The normalized namespace.
     */
    protected static function normalizeNamespace(string $namespace): string
    {
        $namespace = trim($namespace, '\\');

        return $namespace ?
            '\\'.$namespace.'\\' :
            '\\';
    }

    /**
     * Parse the command and arguments from argv.
     * @param array $argv The CLI arguments.
     * @return array The command and arguments.
     */
    protected static function parseArguments(array $argv): array
    {
        array_shift($argv);

        $command = array_shift($argv);

        $arguments = [];

        $key = null;
        foreach ($argv AS $arg) {
            if (preg_match('/^--?(.*)$/', $arg, $match)) {
                $key = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $match[1]))));
            } else if ($key !== null) {
                $arguments[$key] = $arg;
                $key = null;
            } else {
                $arguments[] = $arg;
            }
        }

        return [$command, $arguments];
    }

}
