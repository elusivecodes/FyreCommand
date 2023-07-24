<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;

use function count;

class ArgumentsCommand extends Command
{

    public function run(array $arguments = [])
    {
        return count($arguments) && $arguments[0] === 'value' ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }

}
