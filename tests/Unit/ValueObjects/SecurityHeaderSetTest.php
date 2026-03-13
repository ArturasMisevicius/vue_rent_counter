<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\SecurityHeaderSet;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\ValueObjects\SecurityHeaderSet
 */
final class SecurityHeaderSetTest extends TestCase
{
    public function test_creates_empty_set(): void
    {
        $set = SecurityHeaderSet::empty();

        $this->assertEquals(0, $set->count());
        $this->assertEquals([], $set->toArray());
    }

    public function test_creates_with_headers(): void
    {
        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $set = SecurityHeaderSet::create($headers);

        $this->assertEquals(2, $set->count());
        $this->assertEquals($headers, $set->toArray());
    }

    public function test_adds_header(): void
    {
        $set = SecurityHeaderSet::empty()
            ->withHeader('X-Frame-Options', 'SAMEORIGIN');

        $this->assertTrue($set->has('X-Frame-Options'));
        $this->assertEquals('SAMEORIGIN', $set->get('X-Frame-Options'));
    }

    public function test_removes_header(): void
    {
        $set = SecurityHeaderSet::create(['X-Frame-Options' => 'SAMEORIGIN'])
            ->withoutHeader('X-Frame-Options');

        $this->assertFalse($set->has('X-Frame-Options'));
        $this->assertNull($set->get('X-Frame-Options'));
    }

    public function test_merges_sets(): void
    {
        $set1 = SecurityHeaderSet::create(['X-Frame-Options' => 'SAMEORIGIN']);
        $set2 = SecurityHeaderSet::create(['X-Content-Type-Options' => 'nosniff']);

        $merged = $set1->merge($set2);

        $this->assertTrue($merged->has('X-Frame-Options'));
        $this->assertTrue($merged->has('X-Content-Type-Options'));
        $this->assertEquals(2, $merged->count());
    }

    public function test_merge_overwrites_existing(): void
    {
        $set1 = SecurityHeaderSet::create(['X-Frame-Options' => 'SAMEORIGIN']);
        $set2 = SecurityHeaderSet::create(['X-Frame-Options' => 'DENY']);

        $merged = $set1->merge($set2);

        $this->assertEquals('DENY', $merged->get('X-Frame-Options'));
    }

    public function test_immutability(): void
    {
        $original = SecurityHeaderSet::create(['X-Frame-Options' => 'SAMEORIGIN']);
        $modified = $original->withHeader('X-Content-Type-Options', 'nosniff');

        $this->assertEquals(1, $original->count());
        $this->assertEquals(2, $modified->count());
        $this->assertNotSame($original, $modified);
    }

    public function test_validates_header_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header names and values must be strings');

        SecurityHeaderSet::create([123 => 'value']);
    }

    public function test_validates_header_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header names and values must be strings');

        SecurityHeaderSet::create(['name' => 123]);
    }

    public function test_rejects_empty_header_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name cannot be empty');

        SecurityHeaderSet::create(['' => 'value']);
    }

    public function test_validates_header_name_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header name: Invalid Header!');

        SecurityHeaderSet::create(['Invalid Header!' => 'value']);
    }

    public function test_validates_header_value_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header value for X-Test');

        SecurityHeaderSet::create(['X-Test' => "value\x00with\x01control\x02chars"]);
    }

    public function test_allows_valid_header_names(): void
    {
        $validNames = [
            'X-Frame-Options',
            'Content-Security-Policy',
            'X-Content-Type-Options',
            'Strict-Transport-Security',
        ];

        foreach ($validNames as $name) {
            $set = SecurityHeaderSet::create([$name => 'value']);
            $this->assertTrue($set->has($name));
        }
    }

    public function test_allows_tab_in_header_value(): void
    {
        $set = SecurityHeaderSet::create(['X-Test' => "value\twith\ttabs"]);
        $this->assertEquals("value\twith\ttabs", $set->get('X-Test'));
    }
}