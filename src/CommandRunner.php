<?php
declare(strict_types=1);

namespace Fyre\Command;

use
    Fyre\FileSystem\Folder,
    Fyre\Loader\Loader,
    Throwable;

use function    
    array_filter,
    array_map,
    basename,
    class_exists,
    explode,
    implode,
    in_array,
    is_subclass_of,
    ltrim,
    str_replace,
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
            $paths = Loader::getNamespace(ltrim($namespace, '\\'));
            $namespaceCommands = [];

            foreach ($paths AS $path) {
                $folder = new Folder($path);
                static::findCommands($folder, $namespace, $namespaceCommands);
            }

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
     * Run a command.
     * @param string $command The command.
     * @param array $arguments The arguments.
     * @return int The exit code.
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
            $class = $namespace.$commandSegments;

            if (!class_exists($class)) {
                continue;
            }

            return static::runCommand($class, $arguments);
        }

        return Command::CODE_ERROR;
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
                $path = $child->path();
                $name = basename($path);
                $commandName = $prefix.$name;
    
                static::findCommands($child, $namespace, $commands, $commandName.'\\');
                continue;
            }

            if ($child->extension() !== 'php') {
                continue;
            }

            $name = $child->fileName();
            $commandName = $prefix.$name;

            $className = $namespace.$commandName;

            if (!class_exists($className) || !is_subclass_of($className, Command::class)) {
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
     * Run a command.
     * @param string $class The class name.
     * @param array $arguments The arguments.
     * @return int The exit code.
     */
    protected static function runCommand(string $class, array $arguments): int
    {
        try {
            $command = new $class;

            return $command->run($arguments) ?? Command::CODE_SUCCESS;
        } catch (Throwable $e) {
            return $e->getCode();
        }
    }

}
