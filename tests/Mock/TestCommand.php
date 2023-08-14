<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;

class TestCommand extends Command
{

    protected string|null $name = 'Test Command';

    protected string $description = 'This is a test command.';

    public function run(array $arguments = []): void
    {
    }

}
