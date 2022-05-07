<?php
declare(strict_types=1);

namespace Tests\Mock;

use
    Fyre\Command\Command;

use function
    count;

class Arguments extends Command
{

    public function run(array $arguments = []): int|null
    {
        return count($arguments) && $arguments[0] === 'value' ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }

}
