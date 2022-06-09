<?php
declare(strict_types=1);

namespace Tests\Mock\Deep;

use
    Fyre\Command\Command;

class AnotherCommand extends Command
{

    public function run(array $arguments = [])
    {
        return static::CODE_ERROR;
    }

}
