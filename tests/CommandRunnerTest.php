<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\Command\CommandRunner,
    Fyre\Loader\Loader,
    InvalidArgumentException,
    PHPUnit\Framework\TestCase;

final class CommandRunnerTest extends TestCase
{

    public function testAll(): void
    {
        $this->assertSame(
            [
                '\Tests\Mock\\' => [
                    'ArgumentsCommand' => [
                        'name' => 'Arguments',
                        'description' => ''
                    ],
                    'Deep\AnotherCommand' => [
                        'name' => 'Another',
                        'description' => ''
                    ],
                    'OptionsCommand' => [
                        'name' => 'Options',
                        'description' => ''
                    ],
                    'TestCommand' => [
                        'name' => 'Test Command',
                        'description' => 'This is a test command.'
                    ]
                ]
            ],
            CommandRunner::all()
        );
    }

    public function testHandleCommand()
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'test'])
        );
    }

    public function testHandleCommandArguments()
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'arguments', 'value'])
        );
    }

    public function testHandleCommandArgumentOpts()
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'options', '--test', 'value'])
        );
    }

    public function testRun(): void
    {
        $this->assertSame(
            0,
            CommandRunner::run('test')
        );
    }

    public function testRunDeep(): void
    {
        $this->assertSame(
            1,
            CommandRunner::run('deep/another')
        );
    }

    public function testRunArguments(): void
    {
        $this->assertSame(
            0,
            CommandRunner::run('arguments', ['value'])
        );
    }

    public function testRunInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommandRunner::run('invalid');
    }

    protected function setUp(): void
    {
        Loader::clear();
        CommandRunner::clear();

        Loader::addNamespaces([
            'Tests' => 'tests'
        ]);
        CommandRunner::addNamespace('Tests\Mock');
    }

}
