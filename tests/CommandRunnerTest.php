<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Command\CommandRunner;
use Fyre\Console\Console;
use Fyre\Container\Container;
use Fyre\Loader\Loader;
use Fyre\Utility\Inflector;
use PHPUnit\Framework\TestCase;
use Tests\Mock\ArgumentsCommand;
use Tests\Mock\BoolOptionsCommand;
use Tests\Mock\OptionsCommand;
use Tests\Mock\TestCommand;

use function file_get_contents;
use function file_put_contents;
use function fopen;
use function unlink;

use const LOCK_EX;
use const PHP_EOL;

final class CommandRunnerTest extends TestCase
{
    protected static $in = __DIR__.'/input';

    protected static $out = __DIR__.'/output';

    protected CommandRunner $runner;

    public function testAll(): void
    {
        $commands = $this->runner->all();

        $this->assertSame(
            [
                'arguments' => [
                    'description' => '',
                    'options' => [
                        'value' => [
                            'text' => 'Please enter a value',
                            'required' => true,
                            'default' => 'value',
                        ],
                    ],
                    'className' => ArgumentsCommand::class,
                ],
                'bool_options' => [
                    'description' => '',
                    'options' => [
                        'test' => [
                            'text' => 'Do you agree?',
                            'boolean' => true,
                            'required' => true,
                        ],
                    ],
                    'className' => BoolOptionsCommand::class,
                ],
                'options' => [
                    'description' => '',
                    'options' => [
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
                    ],
                    'className' => OptionsCommand::class,
                ],
                'tester' => [
                    'description' => 'This is a test command.',
                    'options' => [],
                    'className' => TestCommand::class,
                ],
            ],
            $commands
        );
    }

    public function testGetNamepaces(): void
    {
        $this->assertSame(
            [
                'Tests\Mock\\',
            ],
            $this->runner->getNamespaces()
        );
    }

    public function testHandleCommand(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'tester'])
        );
    }

    public function testHandleCommandArgumentBoolMultipleOptions(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'bool_options', '--test', '--other', 'value'])
        );
    }

    public function testHandleCommandArgumentBoolOption(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'bool_options', '--test'])
        );
    }

    public function testHandleCommandArgumentBoolOptionValue(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'bool_options', '--test', 'y'])
        );
    }

    public function testHandleCommandArgumentOptions(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'options', 'a'])
        );
    }

    public function testHandleCommandArgumentOptionsNamed(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'options', '--value', 'a'])
        );
    }

    public function testHandleCommandArguments(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'arguments', 'value'])
        );
    }

    public function testHandleCommandArgumentsNamed(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'arguments', '--value', 'value'])
        );
    }

    public function testHandleCommandBool(): void
    {
        file_put_contents(self::$in, 'y'."\r\n", LOCK_EX);

        $this->assertSame(
            0,
            $this->runner->handle(['', 'bool_options'])
        );

        $this->assertSame(
            "\033[0;33mDo you agree?\033[0m".PHP_EOL.
            " (\033[1;36my\033[0m/\033[2;36mn\033[0m)".PHP_EOL,
            file_get_contents(self::$out)
        );
    }

    public function testHandleCommandBoolDefault(): void
    {
        file_put_contents(self::$in, "\r\n", LOCK_EX);

        $this->assertSame(
            0,
            $this->runner->handle(['', 'bool_options'])
        );

        $this->assertSame(
            "\033[0;33mDo you agree?\033[0m".PHP_EOL.
            " (\033[1;36my\033[0m/\033[2;36mn\033[0m)".PHP_EOL,
            file_get_contents(self::$out)
        );
    }

    public function testHandleCommandOption(): void
    {
        file_put_contents(self::$in, 'a'."\r\n", LOCK_EX);

        $this->assertSame(
            0,
            $this->runner->handle(['', 'options'])
        );

        $this->assertSame(
            "\033[0;33mWhich do you want?\033[0m".PHP_EOL.
            " (\033[1;36ma\033[0m/\033[2;36mb\033[0m/\033[2;36mc\033[0m)".PHP_EOL,
            file_get_contents(self::$out)
        );
    }

    public function testHandleCommandOptionDefault(): void
    {
        file_put_contents(self::$in, "\r\n", LOCK_EX);

        $this->assertSame(
            0,
            $this->runner->handle(['', 'options'])
        );

        $this->assertSame(
            "\033[0;33mWhich do you want?\033[0m".PHP_EOL.
            " (\033[1;36ma\033[0m/\033[2;36mb\033[0m/\033[2;36mc\033[0m)".PHP_EOL,
            file_get_contents(self::$out)
        );
    }

    public function testHandleCommandPrompt(): void
    {
        file_put_contents(self::$in, 'value'."\r\n", LOCK_EX);

        $this->assertSame(
            0,
            $this->runner->handle(['', 'arguments'])
        );

        $this->assertSame(
            "\033[0;33mPlease enter a value (value)\033[0m".PHP_EOL,
            file_get_contents(self::$out)
        );
    }

    public function testHandleCommandPromptDefault(): void
    {
        file_put_contents(self::$in, "\r\n", LOCK_EX);

        $this->assertSame(
            0,
            $this->runner->handle(['', 'arguments'])
        );

        $this->assertSame(
            "\033[0;33mPlease enter a value (value)\033[0m".PHP_EOL,
            file_get_contents(self::$out)
        );
    }

    public function testHasCommand(): void
    {
        $this->assertTrue(
            $this->runner->hasCommand('tester')
        );
    }

    public function testHasCommandInvalid(): void
    {
        $this->assertFalse(
            $this->runner->hasCommand('invalid')
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            $this->runner->hasNamespace('Tests\Mock')
        );
    }

    public function testHasNamespaceInvalid(): void
    {
        $this->assertFalse(
            $this->runner->hasNamespace('Tests\Invalid')
        );
    }

    public function testRemoveNamespace(): void
    {
        $this->assertSame(
            $this->runner,
            $this->runner->removeNamespace('Tests\Mock')
        );

        $this->assertFalse(
            $this->runner->hasNamespace('Tests\Mock')
        );
    }

    public function testRemoveNamespaceInvalid(): void
    {
        $this->assertSame(
            $this->runner,
            $this->runner->removeNamespace('Tests\Invalid')
        );
    }

    public function testRun(): void
    {
        $this->assertSame(
            0,
            $this->runner->run('tester')
        );
    }

    public function testRunArguments(): void
    {
        $this->assertSame(
            0,
            $this->runner->run('arguments', ['value'])
        );
    }

    protected function setUp(): void
    {
        file_put_contents(self::$in, '', LOCK_EX);
        file_put_contents(self::$out, '', LOCK_EX);

        $input = fopen(self::$in, 'r');
        $output = fopen(self::$out, 'w');

        $console = new Console($input, $output, $output);

        $container = new Container();
        $container->singleton(Loader::class);
        $container->singleton(Inflector::class);
        $container->instance(Console::class, $console);

        $container->use(Loader::class)->addNamespaces([
            'Tests' => 'tests',
        ]);

        $this->runner = $container->build(CommandRunner::class);
        $this->runner->addNamespace('Tests\Mock');
    }

    protected function tearDown(): void
    {
        @unlink(self::$in);
        @unlink(self::$out);
    }
}
