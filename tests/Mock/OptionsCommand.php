<?php
declare(strict_types=1);

namespace Tests\Mock;

use
    Fyre\Command\Command;

use function
    array_key_exists;

class OptionsCommand extends Command
{

    public function run(array $arguments = [])
    {
        return array_key_exists('test', $arguments) && $arguments['test'] === 'value' ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }

}
