<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Command\Command;
use Fyre\DateTime\DateTime;

class TypeOptionsCommand extends Command
{
    protected array $options = [
        'test' => [
            'text' => 'What is the date?',
            'as' => 'date',
            'required' => true,
        ],
    ];

    public function run(DateTime $test): int
    {
        return $test->isSameDay(DateTime::now()) ?
            static::CODE_SUCCESS :
            static::CODE_ERROR;
    }
}
