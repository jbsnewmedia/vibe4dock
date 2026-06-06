<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vibe4Dock\OptionParser;

#[CoversClass(OptionParser::class)]
final class OptionParserTest extends TestCase
{
    public function testParsesHelpFlag(): void
    {
        self::assertSame([
            'help' => true,
        ], OptionParser::parse(['--help']));
        self::assertSame([
            'help' => true,
        ], OptionParser::parse(['-h']));
    }

    public function testParsesVersionFlag(): void
    {
        self::assertSame([
            'version' => true,
        ], OptionParser::parse(['--version']));
        self::assertSame([
            'version' => true,
        ], OptionParser::parse(['-v']));
    }

    public function testParsesKeyValueOptions(): void
    {
        $options = OptionParser::parse(['--project-name=demo', '--web-port=8080']);

        self::assertSame('demo', $options['project-name']);
        self::assertSame('8080', $options['web-port']);
    }

    public function testTrimsValues(): void
    {
        $options = OptionParser::parse(['--project-name=  demo  ']);

        self::assertSame('demo', $options['project-name']);
    }

    public function testFlagWithoutValueIsTrue(): void
    {
        $options = OptionParser::parse(['--force']);

        self::assertTrue($options['force']);
    }

    public function testIgnoresPositionalArguments(): void
    {
        self::assertSame([], OptionParser::parse(['positional', 'another']));
    }
}
