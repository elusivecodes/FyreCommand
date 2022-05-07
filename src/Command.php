<?php
declare(strict_types=1);

namespace Fyre\Command;

use
    ReflectionClass;

/**
 * Command
 */
abstract class Command
{

    public const CODE_SUCCESS = 0;

    public const CODE_ERROR = 1;

    protected string $name = '';

    protected string $description = '';

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
        return $this->name ?: (new ReflectionClass($this))->getShortName();
    }

    /**
     * Run the command.
     * @param array $arguments The command arguments.
     * @return int|null The exit code.
     */
    abstract public function run(array $arguments = []): int|null;

}
