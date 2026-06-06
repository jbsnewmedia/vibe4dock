<?php

declare(strict_types=1);

namespace Vibe4Dock\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vibe4Dock\PortNormalizer;

#[CoversClass(PortNormalizer::class)]
final class PortNormalizerTest extends TestCase
{
    public function testNormalizesInteger(): void
    {
        self::assertSame(8080, PortNormalizer::normalize(8080));
    }

    public function testNormalizesNumericString(): void
    {
        self::assertSame(8080, PortNormalizer::normalize('8080'));
    }

    public function testReturnsZeroForInvalidValues(): void
    {
        self::assertSame(0, PortNormalizer::normalize('abc'));
        self::assertSame(0, PortNormalizer::normalize('80a'));
        self::assertSame(0, PortNormalizer::normalize(true));
        self::assertSame(0, PortNormalizer::normalize(null));
    }

    public function testValidatesRange(): void
    {
        self::assertTrue(PortNormalizer::isValid(1));
        self::assertTrue(PortNormalizer::isValid(65535));
        self::assertFalse(PortNormalizer::isValid(0));
        self::assertFalse(PortNormalizer::isValid(65536));
    }
}
