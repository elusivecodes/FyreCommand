<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;

class OptionsCommand extends Command
{
    protected array $options = [
        'value' => [
            'text' => 'Which do you want?',
            'values' => [
                'a',
                'b',
                'c',
            ],
            'required' => true,
            'default' => 'a',
        ],
    ];

    public function run(string $value): int
    {
        return $value === 'a' ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }
}
