<?php

namespace Tests\Unit\Models;

use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_has_fillable_attributes()
    {
        $fillable = [
            'group', 'key', 'values'
        ];
        
        $translation = new Translation();
        $this->assertEquals($fillable, $translation->getFillable());
    }

    public function test_translation_casts_values_to_array()
    {
        $translation = Translation::factory()->create([
            'values' => [
                'en' => 'Hello',
                'lt' => 'Labas',
                'ru' => 'Привет'
            ]
        ]);

        $this->assertIsArray($translation->values);
        $this->assertArrayHasKey('en', $translation->values);
        $this->assertArrayHasKey('lt', $translation->values);
        $this->assertArrayHasKey('ru', $translation->values);
    }

    public function test_translation_factory_creates_valid_translation()
    {
        $translation = Translation::factory()->create();

        $this->assertNotNull($translation->group);
        $this->assertNotNull($translation->key);
        $this->assertIsArray($translation->values);
    }

    public function test_translation_key_uses_dot_notation()
    {
        $translation = Translation::factory()->create([
            'key' => 'auth.login.title'
        ]);

        $this->assertStringContainsString('.', $translation->key);
        $this->assertEquals('auth.login.title', $translation->key);
    }

    public function test_translation_values_can_contain_placeholders()
    {
        $translation = Translation::factory()->create([
            'values' => [
                'en' => 'Welcome, :name!',
                'lt' => 'Sveiki, :name!',
                'ru' => 'Добро пожаловать, :name!'
            ]
        ]);

        $this->assertStringContainsString(':name', $translation->values['en']);
        $this->assertStringContainsString(':name', $translation->values['lt']);
        $this->assertStringContainsString(':name', $translation->values['ru']);
    }

    public function test_translation_can_have_empty_values()
    {
        $translation = Translation::factory()->create([
            'values' => [
                'en' => '',
                'lt' => '',
                'ru' => ''
            ]
        ]);

        $this->assertEquals('', $translation->values['en']);
        $this->assertEquals('', $translation->values['lt']);
        $this->assertEquals('', $translation->values['ru']);
    }

    public function test_translation_group_organizes_related_translations()
    {
        $authGroup = 'auth';
        
        Translation::factory()->create([
            'group' => $authGroup,
            'key' => 'login'
        ]);
        
        Translation::factory()->create([
            'group' => $authGroup,
            'key' => 'logout'
        ]);

        $authTranslations = Translation::where('group', $authGroup)->get();
        
        $this->assertCount(2, $authTranslations);
    }

    public function test_get_distinct_groups_method()
    {
        Translation::factory()->create(['group' => 'auth']);
        Translation::factory()->create(['group' => 'validation']);
        Translation::factory()->create(['group' => 'auth']); // Duplicate group

        $groups = Translation::getDistinctGroups();
        
        $this->assertIsArray($groups);
        $this->assertArrayHasKey('auth', $groups);
        $this->assertArrayHasKey('validation', $groups);
        $this->assertCount(2, $groups);
    }

    public function test_translation_values_support_multiple_languages()
    {
        $translation = Translation::factory()->create([
            'values' => [
                'en' => 'English text',
                'lt' => 'Lithuanian text',
                'ru' => 'Russian text'
            ]
        ]);

        $this->assertEquals('English text', $translation->values['en']);
        $this->assertEquals('Lithuanian text', $translation->values['lt']);
        $this->assertEquals('Russian text', $translation->values['ru']);
    }

    public function test_same_key_can_exist_in_different_groups()
    {
        $translation1 = Translation::factory()->create([
            'group' => 'auth',
            'key' => 'title'
        ]);
        
        $translation2 = Translation::factory()->create([
            'group' => 'validation',
            'key' => 'title'
        ]);

        $this->assertEquals('title', $translation1->key);
        $this->assertEquals('title', $translation2->key);
        $this->assertNotEquals($translation1->group, $translation2->group);
    }

    public function test_translation_key_is_unique_per_group()
    {
        Translation::factory()->create([
            'group' => 'auth',
            'key' => 'login.title'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Translation::factory()->create([
            'group' => 'auth',
            'key' => 'login.title'
        ]);
    }
}
