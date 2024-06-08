<?php
declare(strict_types=1);

namespace Fyre\Command;

use ReflectionClass;

use function preg_replace;

/**
 * Command
 */
abstract class Command
{

    public const CODE_SUCCESS = 0;

    public const CODE_ERROR = 1;

    protected string|null $alias = null;

    protected string|null $name = null;

    protected string $description = '';

    /**
     * Get the command alias.
     * @return string The command alias.
     */
    public function getAlias(): string
    {
        return $this->alias ??= strtolower($this->getName());
    }

    /**
     * Get the command description.
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the command name.
     * @return string The command name.
     */
    public function getName(): string
    {
        return $this->name ??= preg_replace('/Command$/', '', (new ReflectionClass($this))->getShortName());
    }

    /**
     * Run the command.
     * @param array $arguments The command arguments.
     * @return mixed The exit code.
     */
    abstract public function run(array $arguments = []);

}
