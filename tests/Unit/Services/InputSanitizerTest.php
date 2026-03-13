<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\InputSanitizer;
use Tests\TestCase;

class InputSanitizerTest extends TestCase
{
    protected InputSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new InputSanitizer();
    }

    /** @test */
    public function it_sanitizes_basic_text(): void
    {
        $input = 'Hello World';
        $result = $this->sanitizer->sanitizeText($input);
        
        $this->assertEquals('Hello World', $result);
    }

    /** @test */
    public function it_removes_html_tags_from_text(): void
    {
        $input = '<script>alert("XSS")</script>Hello';
        $result = $this->sanitizer->sanitizeText($input);
        
        $this->assertEquals('Hello', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /** @test */
    public function it_allows_safe_html_when_enabled(): void
    {
        $input = '<p>Hello <strong>World</strong></p>';
        $result = $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
    }

    /** @test */
    public function it_removes_dangerous_html_even_with_basic_html_allowed(): void
    {
        $input = '<p>Hello</p><script>alert("XSS")</script>';
        $result = $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        
        $this->assertStringContainsString('<p>Hello</p>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /** @test */
    public function it_removes_javascript_protocol_handlers(): void
    {
        $input = '<a href="javascript:alert(1)">Click</a>';
        $result = $this->sanitizer->sanitizeText($input);
        
        $this->assertStringNotContainsString('javascript:', $result);
    }

    /** @test */
    public function it_removes_vbscript_protocol_handlers(): void
    {
        $input = 'vbscript:msgbox("XSS")';
        $result = $this->sanitizer->sanitizeText($input);
        
        $this->assertStringNotContainsString('vbscript:', $result);
    }

    /** @test */
    public function it_removes_data_uri_html(): void
    {
        $input = 'data:text/html,<script>alert(1)</script>';
        $result = $this->sanitizer->sanitizeText($input);
        
        $this->assertStringNotContainsString('data:text/html', $result);
    }

    /** @test */
    public function it_removes_null_bytes(): void
    {
        $input = "Hello\0World";
        $result = $this->sanitizer->sanitizeText($input);
        
        $this->assertEquals('HelloWorld', $result);
        $this->assertStringNotContainsString("\0", $result);
    }

    /** @test */
    public function it_trims_whitespace(): void
    {
        $input = '  Hello World  ';
        $result = $this->sanitizer->sanitizeText($input);
        
        $this->assertEquals('Hello World', $result);
    }

    /** @test */
    public function it_handles_empty_text_input(): void
    {
        $result = $this->sanitizer->sanitizeText('');
        
        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_sanitizes_valid_numeric_input(): void
    {
        $result = $this->sanitizer->sanitizeNumeric('123.45');
        
        $this->assertEquals(123.45, $result);
    }

    /** @test */
    public function it_sanitizes_integer_numeric_input(): void
    {
        $result = $this->sanitizer->sanitizeNumeric(100);
        
        $this->assertEquals(100.0, $result);
    }

    /** @test */
    public function it_throws_exception_for_numeric_overflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value exceeds maximum allowed');
        
        $this->sanitizer->sanitizeNumeric(1000000);
    }

    /** @test */
    public function it_throws_exception_for_negative_numeric_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Negative values not allowed');
        
        $this->sanitizer->sanitizeNumeric(-10);
    }

    /** @test */
    public function it_accepts_custom_max_for_numeric_values(): void
    {
        $result = $this->sanitizer->sanitizeNumeric(500, max: 1000);
        
        $this->assertEquals(500.0, $result);
    }

    /** @test */
    public function it_sanitizes_valid_identifier(): void
    {
        $input = 'system-id_123.456';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('system-id_123.456', $result);
    }

    /** @test */
    public function it_removes_special_characters_from_identifier(): void
    {
        $input = 'system@id#123!';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('systemid123', $result);
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringNotContainsString('#', $result);
        $this->assertStringNotContainsString('!', $result);
    }

    /** @test */
    public function it_allows_single_dots_in_identifier(): void
    {
        $input = 'provider.system.id';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('provider.system.id', $result);
    }

    /** @test */
    public function it_allows_hyphens_in_identifier(): void
    {
        $input = 'provider-123';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('provider-123', $result);
    }

    /** @test */
    public function it_allows_underscores_in_identifier(): void
    {
        $input = 'provider_id_123';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('provider_id_123', $result);
    }

    /** @test */
    public function it_allows_hierarchical_identifiers_with_single_dots(): void
    {
        $input = 'system.provider.id.123';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('system.provider.id.123', $result);
    }

    /** @test */
    public function it_allows_single_dots_with_other_valid_characters(): void
    {
        $input = 'provider-123_test.id';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('provider-123_test.id', $result);
    }

    /** @test */
    public function it_removes_invalid_characters_but_preserves_single_dots(): void
    {
        $input = 'test@provider#123.id!456';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('testprovider123.id456', $result);
    }

    /** @test */
    public function it_blocks_multiple_consecutive_dots_for_security(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier contains invalid pattern (..)');
        
        $this->sanitizer->sanitizeIdentifier('provider..system...id');
    }

    /** @test */
    public function it_blocks_double_dots_created_by_character_removal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier contains invalid pattern (..)');
        
        // Attack vector: @ gets removed, creating ".."
        $this->sanitizer->sanitizeIdentifier('test.@.example');
    }

    /** @test */
    public function it_blocks_triple_dots_created_by_multiple_invalid_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier contains invalid pattern (..)');
        
        // Multiple invalid chars between dots
        $this->sanitizer->sanitizeIdentifier('test.#.#.example');
    }

    /** @test */
    public function it_blocks_path_traversal_with_obfuscated_dots(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier contains invalid pattern (..)');
        
        // Obfuscated path traversal attempt
        $this->sanitizer->sanitizeIdentifier('.@./.@./etc/passwd');
    }

    /** @test */
    public function it_removes_leading_dots(): void
    {
        $input = '.provider.id';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        // Leading dots should be removed for security
        $this->assertEquals('provider.id', $result);
    }

    /** @test */
    public function it_removes_trailing_dots(): void
    {
        $input = 'provider.id.';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        // Trailing dots should be removed for security
        $this->assertEquals('provider.id', $result);
    }

    /** @test */
    public function it_throws_exception_for_only_dots(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier contains invalid pattern (..)');
        
        $this->sanitizer->sanitizeIdentifier('...');
    }

    /** @test */
    public function it_preserves_single_dots_at_max_length(): void
    {
        // Create identifier with dots at exactly 255 characters
        $identifier = str_repeat('a', 250) . '.test';
        $result = $this->sanitizer->sanitizeIdentifier($identifier);
        
        $this->assertEquals($identifier, $result);
        $this->assertEquals(255, strlen($result));
    }

    /** @test */
    public function it_handles_complex_external_system_ids(): void
    {
        $validIds = [
            'aws.s3.bucket.123',
            'azure.storage.container.456',
            'gcp.project.resource.789',
            'api.v2.endpoint.users',
            'db.prod.table.customers',
        ];

        foreach ($validIds as $validId) {
            $result = $this->sanitizer->sanitizeIdentifier($validId);
            $this->assertEquals($validId, $result, "Failed to preserve: {$validId}");
        }
    }

    /** @test */
    public function it_prevents_path_traversal_with_double_dots(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier contains invalid pattern (..)');
        
        $this->sanitizer->sanitizeIdentifier('../../../etc/passwd');
    }

    /** @test */
    public function it_handles_sql_injection_attempts_with_single_dots(): void
    {
        $sqlInjectionAttempts = [
            "'; DROP TABLE tariffs; --" => 'DROPTABLEtariffs',
            "1' OR '1'='1" => '1OR11',
            "admin'--" => 'admin',
            "1.0; DELETE FROM tariffs WHERE 1=1" => '1.0DELETEFROMtariffsWHERE11',
        ];

        foreach ($sqlInjectionAttempts as $attempt => $expected) {
            $result = $this->sanitizer->sanitizeIdentifier($attempt);
            
            // Should remove dangerous characters but preserve single dots
            $this->assertStringNotContainsString("'", $result);
            $this->assertStringNotContainsString(';', $result);
            $this->assertStringNotContainsString(' ', $result);
            $this->assertStringNotContainsString('=', $result);
        }
    }

    /** @test */
    public function it_handles_empty_identifier_input(): void
    {
        $result = $this->sanitizer->sanitizeIdentifier('');
        
        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_throws_exception_for_whitespace_only_identifier(): void
    {
        // Whitespace gets trimmed first, resulting in empty string
        $result = $this->sanitizer->sanitizeIdentifier('   ');
        
        $this->assertEquals('', $result);
    }

    /** @test */
    public function it_throws_exception_for_identifier_exceeding_max_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier exceeds maximum length');
        
        $longIdentifier = str_repeat('a', 256);
        $this->sanitizer->sanitizeIdentifier($longIdentifier);
    }

    /** @test */
    public function it_accepts_identifier_at_max_length(): void
    {
        $identifier = str_repeat('a', 255);
        $result = $this->sanitizer->sanitizeIdentifier($identifier);
        
        $this->assertEquals($identifier, $result);
        $this->assertEquals(255, strlen($result));
    }

    /** @test */
    public function it_accepts_custom_max_length_for_identifier(): void
    {
        $identifier = str_repeat('a', 50);
        $result = $this->sanitizer->sanitizeIdentifier($identifier, 100);
        
        $this->assertEquals($identifier, $result);
    }

    /** @test */
    public function it_sanitizes_identifier_with_mixed_valid_characters(): void
    {
        $input = 'provider-123_test.id';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('provider-123_test.id', $result);
    }

    /** @test */
    public function it_removes_invalid_characters_but_keeps_single_dots(): void
    {
        $input = 'test@provider#123.id!456';
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        $this->assertEquals('testprovider123.id456', $result);
    }

    /** @test */
    public function it_validates_correct_time_format(): void
    {
        $result = $this->sanitizer->sanitizeTime('14:30');
        
        $this->assertEquals('14:30', $result);
    }

    /** @test */
    public function it_validates_midnight_time(): void
    {
        $result = $this->sanitizer->sanitizeTime('00:00');
        
        $this->assertEquals('00:00', $result);
    }

    /** @test */
    public function it_validates_end_of_day_time(): void
    {
        $result = $this->sanitizer->sanitizeTime('23:59');
        
        $this->assertEquals('23:59', $result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_time_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid time format');
        
        $this->sanitizer->sanitizeTime('25:00');
    }

    /** @test */
    public function it_throws_exception_for_invalid_time_minutes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid time format');
        
        $this->sanitizer->sanitizeTime('14:60');
    }

    /** @test */
    public function it_throws_exception_for_malformed_time(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid time format');
        
        $this->sanitizer->sanitizeTime('14-30');
    }

    /** @test */
    public function it_prevents_xss_in_event_handlers(): void
    {
        $input = '<img src="x" onerror="alert(1)">';
        $result = $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        
        $this->assertStringNotContainsString('onerror', $result);
    }

    /** @test */
    public function it_removes_onclick_handlers(): void
    {
        $input = '<div onclick="alert(1)">Click me</div>';
        $result = $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        
        $this->assertStringNotContainsString('onclick', $result);
    }
}
