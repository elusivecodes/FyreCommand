<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;

use function array_key_exists;

class BoolOptionsCommand extends Command
{
    public function run(array $arguments = []): int
    {
        return array_key_exists('test', $arguments) && $arguments['test'] === true ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }
}
