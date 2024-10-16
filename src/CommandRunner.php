<?php
declare(strict_types=1);

namespace Fyre\Command;

use Fyre\Console\Console;
use Fyre\Loader\Loader;
use ReflectionClass;

use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_pop;
use function array_shift;
use function array_splice;
use function class_exists;
use function explode;
use function implode;
use function in_array;
use function is_dir;
use function is_subclass_of;
use function ksort;
use function lcfirst;
use function pathinfo;
use function preg_match;
use function scandir;
use function str_ends_with;
use function str_replace;
use function trim;
use function ucwords;

use const PATHINFO_FILENAME;
use const SORT_NATURAL;

/**
 * CommandRunner
 */
abstract class CommandRunner
{
    protected static array|null $commands = null;

    protected static array $namespaces = [];

    /**
     * Add a namespace for loading commands.
     *
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
     *
     * @return array The available commands.
     */
    public static function all(): array
    {
        if (static::$commands !== null) {
            return static::$commands;
        }

        $commands = [];

        foreach (static::$namespaces as $namespace) {
            $pathParts = [];
            $namespaceParts = explode('\\', $namespace);
            $namespaceParts = array_filter($namespaceParts);

            do {
                $currentNamespace = implode('\\', $namespaceParts).'\\';
                $paths = Loader::getNamespacePaths($currentNamespace);

                foreach ($paths as $path) {
                    $fullPath = $path;
                    if ($pathParts !== []) {
                        $fullPath .= '/'.implode('/', $pathParts);
                    }

                    if (!is_dir($fullPath)) {
                        continue;
                    }

                    $commands += static::findCommands($fullPath, $namespace);
                }

                $pathParts[] = array_pop($namespaceParts);
            } while ($namespaceParts !== []);
        }

        ksort($commands, SORT_NATURAL);

        return static::$commands = $commands;
    }

    /**
     * Clear all namespaces and loaded commands.
     */
    public static function clear(): void
    {
        static::$namespaces = [];
        static::$commands = null;
    }

    /**
     * Get the namespaces.
     *
     * @return array The namespaces.
     */
    public static function getNamespaces(): array
    {
        return static::$namespaces;
    }

    /**
     * Handle an argv command.
     *
     * @param array $argv The CLI arguments.
     * @return int The exit code of the command.
     */
    public static function handle(array $argv): int
    {
        [$command, $arguments] = static::parseArguments($argv);

        if ($command) {
            return static::run($command, $arguments);
        }

        $allCommands = static::all();

        $data = [];
        foreach ($allCommands as $alias => $command) {
            $data[] = [
                Console::style($alias, ['color' => Console::GREEN]),
                $command->getName(),
                $command->getDescription(),
            ];
        }

        Console::table($data, ['Command', 'Name', 'Description']);

        return Command::CODE_SUCCESS;
    }

    /**
     * Determine if a command exists.
     *
     * @param string $alias The command alias.
     * @return bool TRUE if the command exists, otherwise FALSE.
     */
    public static function hasCommand(string $alias): bool
    {
        return array_key_exists($alias, static::all());
    }

    /**
     * Determine if a namespace exists.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE if the namespace exists, otherwise FALSE.
     */
    public static function hasNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        return in_array($namespace, static::$namespaces);
    }

    /**
     * Remove a namespace.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE If the namespace was removed, otherwise FALSE.
     */
    public static function removeNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        foreach (static::$namespaces as $i => $otherNamespace) {
            if ($otherNamespace !== $namespace) {
                continue;
            }

            array_splice(static::$namespaces, $i, 1);

            return true;
        }

        return false;
    }

    /**
     * Run a command.
     *
     * @param string $alias The command alias.
     * @param array $arguments The arguments.
     * @return int The exit code.
     */
    public static function run(string $alias, array $arguments = []): int
    {
        $commands = static::all();

        if (array_key_exists($alias, $commands)) {
            return $commands[$alias]->run($arguments) ?? Command::CODE_SUCCESS;
        }

        Console::error('Invalid command: '.$alias);

        return Command::CODE_ERROR;
    }

    /**
     * Find commands in a Folder.
     *
     * @param string $path The path.
     * @param string $namespace The root namespace.
     * @return array The commands.
     */
    protected static function findCommands(string $path, string $namespace): array
    {
        $files = array_diff(scandir($path), ['.', '..']);

        $commands = [];

        foreach ($files as $file) {
            if (!str_ends_with($file, 'Command.php')) {
                continue;
            }

            $name = pathinfo($file, PATHINFO_FILENAME);

            $className = $namespace.$name;

            if (!class_exists($className) || !is_subclass_of($className, Command::class)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            $command = new $className();

            $alias = $command->getAlias();

            $commands[$alias] = $command;
        }

        return $commands;
    }

    /**
     * Normalize a namespace
     *
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
     *
     * @param array $argv The CLI arguments.
     * @return array The command and arguments.
     */
    protected static function parseArguments(array $argv): array
    {
        array_shift($argv);

        $command = array_shift($argv);

        $arguments = [];

        $key = null;
        foreach ($argv as $arg) {
            if (preg_match('/^--?([^\s]+)$/', $arg, $match)) {
                if ($key !== null) {
                    $arguments[$key] = true;
                }

                $key = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $match[1]))));
            } else if ($key !== null) {
                $arguments[$key] = $arg;
                $key = null;
            } else {
                $arguments[] = $arg;
            }
        }

        if ($key !== null) {
            $arguments[$key] = true;
        }

        return [$command, $arguments];
    }
}
