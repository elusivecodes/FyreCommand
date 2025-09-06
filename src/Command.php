<?php
declare(strict_types=1);

namespace Fyre\Command;

use Fyre\Utility\Traits\MacroTrait;

/**
 * Command
 */
abstract class Command
{
    use MacroTrait;

    public const CODE_ERROR = 1;

    public const CODE_SUCCESS = 0;

    protected string|null $alias = null;

    protected string $description = '';

    protected array $options = [];
}
