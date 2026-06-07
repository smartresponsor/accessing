<?php
declare(strict_types=1);

namespace PHPUnit\Framework;

abstract class TestCase
{
    public static function assertSame(mixed $expected, mixed $actual, string $message = ''): void {}
    public static function assertNotSame(mixed $expected, mixed $actual, string $message = ''): void {}
    public static function assertTrue(bool $condition, string $message = ''): void {}
    public static function assertFalse(bool $condition, string $message = ''): void {}
    public static function assertNull(mixed $actual, string $message = ''): void {}
    public static function assertNotNull(mixed $actual, string $message = ''): void {}
    public static function assertInstanceOf(string $expected, mixed $actual, string $message = ''): void {}
    public static function assertStringStartsWith(string $prefix, string $string, string $message = ''): void {}
    /** @param iterable<mixed>|string $haystack */
    public static function assertNotContains(mixed $needle, iterable|string $haystack, string $message = ''): void {}
    public function expectException(string $exception): void {}
    public function expectExceptionMessage(string $message): void {}
}
