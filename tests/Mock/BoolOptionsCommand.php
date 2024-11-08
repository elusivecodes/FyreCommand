<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;

class BoolOptionsCommand extends Command
{
    protected array $options = [
        'test' => [
            'text' => 'Do you agree?',
            'boolean' => true,
            'required' => true,
        ],
    ];

    public function run(bool $test): int
    {
        return $test === true ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }
}
