<?php
declare(strict_types=1);

namespace Fyre\Command;

use Fyre\Console\Console;
use Fyre\Container\Container;
use Fyre\DB\TypeParser;
use Fyre\Event\EventDispatcherTrait;
use Fyre\Event\EventManager;
use Fyre\Loader\Loader;
use Fyre\Utility\Inflector;
use ReflectionClass;

use function array_diff;
use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_pop;
use function array_shift;
use function array_splice;
use function class_exists;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_dir;
use function is_subclass_of;
use function ksort;
use function method_exists;
use function pathinfo;
use function preg_match;
use function preg_replace;
use function scandir;
use function str_ends_with;
use function trim;

use const PATHINFO_FILENAME;
use const SORT_NATURAL;

/**
 * CommandRunner
 */
class CommandRunner
{
    use EventDispatcherTrait;

    protected array|null $commands = null;

    protected Container $container;

    protected Inflector $inflector;

    protected Console $io;

    protected Loader $loader;

    protected array $namespaces = [];

    protected TypeParser $typeParser;

    /**
     * New CommandRunner constructor.
     *
     * @param Container $container The Container.
     * @param Loader $loader The Loader.
     * @param Inflector $inflector The Inflector.
     * @param Console $io The Console.
     * @param EventManager $eventManager The EventManager.
     * @param TypeParser $typeParser The TypeParser.
     */
    public function __construct(Container $container, Loader $loader, Inflector $inflector, Console $io, EventManager $eventManager, TypeParser $typeParser)
    {
        $this->container = $container;
        $this->loader = $loader;
        $this->inflector = $inflector;
        $this->io = $io;
        $this->eventManager = $eventManager;
        $this->typeParser = $typeParser;
    }

    /**
     * Add a namespace for loading commands.
     *
     * @param string $namespace The namespace.
     * @return CommandRunner The CommandRunner.
     */
    public function addNamespace(string $namespace): static
    {
        $namespace = static::normalizeNamespace($namespace);

        if (!in_array($namespace, $this->namespaces)) {
            $this->namespaces[] = $namespace;
        }

        return $this;
    }

    /**
     * Get all available commands.
     *
     * @return array The available commands.
     */
    public function all(): array
    {
        if ($this->commands !== null) {
            return $this->commands;
        }

        $commands = [];

        foreach ($this->namespaces as $namespace) {
            $pathParts = [];
            $namespaceParts = explode('\\', $namespace);
            $namespaceParts = array_filter($namespaceParts);

            do {
                $currentNamespace = implode('\\', $namespaceParts).'\\';
                $paths = $this->loader->getNamespacePaths($currentNamespace);

                foreach ($paths as $path) {
                    $fullPath = $path;
                    if ($pathParts !== []) {
                        $fullPath .= '/'.implode('/', $pathParts);
                    }

                    if (!is_dir($fullPath)) {
                        continue;
                    }

                    $commands += $this->findCommands($fullPath, $namespace);
                }

                $pathParts[] = array_pop($namespaceParts);
            } while ($namespaceParts !== []);
        }

        ksort($commands, SORT_NATURAL);

        $this->dispatchEvent('Command.buildCommands', ['commands' => $commands], false);

        return $this->commands = $commands;
    }

    /**
     * Clear all namespaces and loaded commands.
     */
    public function clear(): void
    {
        $this->namespaces = [];
        $this->commands = null;
    }

    /**
     * Get the namespaces.
     *
     * @return array The namespaces.
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Handle an argv command.
     *
     * @param array $argv The CLI arguments.
     * @return int The exit code of the command.
     */
    public function handle(array $argv): int
    {
        [$command, $arguments] = $this->parseArguments($argv);

        if ($command) {
            return $this->run($command, $arguments);
        }

        $allCommands = $this->all();

        $data = [];
        foreach ($allCommands as $alias => $command) {
            $data[] = [
                Console::style($alias, ['color' => Console::GREEN]),
                $command['description'],
                implode(', ', array_keys($command['options'])),
            ];
        }

        $this->io->table($data, ['Command', 'Description', 'Options']);

        return Command::CODE_SUCCESS;
    }

    /**
     * Determine whether a command exists.
     *
     * @param string $alias The command alias.
     * @return bool TRUE if the command exists, otherwise FALSE.
     */
    public function hasCommand(string $alias): bool
    {
        return array_key_exists($alias, $this->all());
    }

    /**
     * Determine whether a namespace exists.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE if the namespace exists, otherwise FALSE.
     */
    public function hasNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        return in_array($namespace, $this->namespaces);
    }

    /**
     * Remove a namespace.
     *
     * @param string $namespace The namespace.
     * @return CommandRunner The CommandRunner.
     */
    public function removeNamespace(string $namespace): static
    {
        $namespace = static::normalizeNamespace($namespace);

        foreach ($this->namespaces as $i => $otherNamespace) {
            if ($otherNamespace !== $namespace) {
                continue;
            }

            array_splice($this->namespaces, $i, 1);
            break;
        }

        return $this;
    }

    /**
     * Run a command.
     *
     * @param string $alias The command alias.
     * @param array $arguments The arguments.
     * @return int The exit code.
     */
    public function run(string $alias, array $arguments = []): int
    {
        $commands = $this->all();
        $command = $commands[$alias] ?? null;

        if (!$command) {
            $this->io->error('Invalid command: '.$alias);

            return Command::CODE_ERROR;
        }

        if (!method_exists($command['className'], 'run')) {
            $this->io->error('Missing run method: '.$alias);

            return Command::CODE_ERROR;
        }

        $options = [];

        $namedArguments = array_intersect_key($arguments, $command['options']);
        $listArguments = array_diff_key($arguments, $command['options']);

        foreach ($command['options'] as $key => $data) {
            if (array_key_exists($key, $namedArguments)) {
                $value = $namedArguments[$key];
            } else if ($listArguments !== []) {
                $value = array_shift($listArguments);
            } else {
                $value = null;
            }

            if (!is_array($data)) {
                $data = ['text' => (string) $data];
            }

            $data['text'] ??= '';
            $data['values'] ??= null;
            $data['required'] ??= false;
            $data['as'] ??= 'string';
            $data['default'] ??= null;

            $type = $this->typeParser->use($data['as']);

            if (is_array($data['values'])) {
                $optionKeys = array_is_list($data['values']) ?
                    $data['values'] :
                    array_keys($data['values']);

                if ($value !== null && !in_array($value, $optionKeys)) {
                    $this->io->error('Invalid option value for: '.$key);
                    $value = null;
                }

                if ($data['required']) {
                    while ($value === null) {
                        $value = $this->io->choice($data['text'], $data['values'], $data['default']);
                        $value = $type->parse($value);
                    }
                } else {
                    $value ??= $data['default'];
                    $value = $type->parse($value);
                }
            } else if ($data['as'] === 'boolean') {
                if ($value === null) {
                    if ($data['required']) {
                        $value = $this->io->confirm($data['text'], (bool) ($data['default'] ?? true));
                    } else {
                        $value = (bool) $data['default'];
                    }
                } else {
                    $value = $value && !in_array($value, ['false', 'n', 'no'], true);
                }
            } else {
                if (is_bool($value)) {
                    $this->io->error('Invalid value for: '.$key);
                    $value = null;
                }

                $value = $type->parse($value);

                if ($value === null) {
                    if ($data['required']) {
                        $text = $data['text'];

                        if ($data['default']) {
                            $text .= ' ('.$data['default'].')';
                        }

                        while ($value === null) {
                            $value = $this->io->prompt($text) ?: $data['default'];
                            $value = $type->parse($value);
                        }
                    } else {
                        $value = $data['default'];
                        $value = $type->parse($value);
                    }
                }
            }

            if ($value !== null) {
                $options[$key] = $value;
            }
        }

        $instance = $this->container->build($command['className']);

        $this->dispatchEvent('Command.beforeExecute', ['options' => $options], false, $instance);

        $result = $this->container->call([$instance, 'run'], $options) ?? Command::CODE_SUCCESS;

        $this->dispatchEvent('Command.afterExecute', ['options' => $options, 'result' => $result], false, $instance);

        return $result;
    }

    /**
     * Find commands in a Folder.
     *
     * @param string $path The path.
     * @param string $namespace The root namespace.
     * @return array The commands.
     */
    protected function findCommands(string $path, string $namespace): array
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

            $alias = $reflection->getProperty('alias')->getDefaultValue();

            if (!$alias) {
                $alias = preg_replace('/Command$/', '', $reflection->getShortName());
                $alias = $this->inflector->underscore($alias);
            }

            $commands[$alias] = [
                'description' => $reflection->getProperty('description')->getDefaultValue(),
                'options' => $reflection->getProperty('options')->getDefaultValue(),
                'className' => $className,
            ];
        }

        return $commands;
    }

    /**
     * Parse the command and arguments from argv.
     *
     * @param array $argv The CLI arguments.
     * @return array The command and arguments.
     */
    protected function parseArguments(array $argv): array
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

                $key = $this->inflector->variable($match[1]);
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

    /**
     * Normalize a namespace
     *
     * @param string $namespace The namespace.
     * @return string The normalized namespace.
     */
    protected static function normalizeNamespace(string $namespace): string
    {
        return trim($namespace, '\\').'\\';
    }
}
