<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;

class TestCommand extends Command
{
    protected string|null $alias = 'tester';

    protected string $description = 'This is a test command.';

    protected string|null $name = 'Test Command';

    public function run(array $arguments = []): void {}
}
