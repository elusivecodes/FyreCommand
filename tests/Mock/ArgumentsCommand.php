<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;

class ArgumentsCommand extends Command
{
    protected array $options = [
        'value' => [
            'text' => 'Please enter a value',
            'required' => true,
            'default' => 'value',
        ],
    ];

    public function run(string $value): int
    {
        return $value === 'value' ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }
}
