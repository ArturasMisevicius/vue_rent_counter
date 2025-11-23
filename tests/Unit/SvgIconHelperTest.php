<?php

use App\Enums\IconType;
use function Pest\Laravel\{get};

describe('IconType enum', function () {
    it('provides heroicon identifiers', function () {
        expect(IconType::METER->heroicon())->toBe('heroicon-o-cpu-chip');
        expect(IconType::INVOICE->heroicon())->toBe('heroicon-o-document-text');
        expect(IconType::SHIELD->heroicon())->toBe('heroicon-o-shield-check');
        expect(IconType::CHART->heroicon())->toBe('heroicon-o-chart-bar');
        expect(IconType::ROCKET->heroicon())->toBe('heroicon-o-rocket-launch');
        expect(IconType::USERS->heroicon())->toBe('heroicon-o-user-group');
        expect(IconType::DEFAULT->heroicon())->toBe('heroicon-o-check-circle');
    });

    it('resolves from legacy keys', function () {
        expect(IconType::fromLegacyKey('meter'))->toBe(IconType::METER);
        expect(IconType::fromLegacyKey('invoice'))->toBe(IconType::INVOICE);
        expect(IconType::fromLegacyKey('shield'))->toBe(IconType::SHIELD);
        expect(IconType::fromLegacyKey('chart'))->toBe(IconType::CHART);
        expect(IconType::fromLegacyKey('rocket'))->toBe(IconType::ROCKET);
        expect(IconType::fromLegacyKey('users'))->toBe(IconType::USERS);
        expect(IconType::fromLegacyKey('unknown'))->toBe(IconType::DEFAULT);
    });
});

describe('svgIcon helper (backward compatibility)', function () {
    it('returns valid SVG markup for all icon types', function () {
        $icons = ['meter', 'invoice', 'shield', 'chart', 'rocket', 'users', 'default'];
        
        foreach ($icons as $icon) {
            $svg = svgIcon($icon);
            
            expect($svg)
                ->toContain('<svg')
                ->toContain('</svg>')
                ->toContain('class="h-5 w-5"');
        }
    });

    it('returns default icon for unknown key', function () {
        $svg = svgIcon('unknown-key');
        
        expect($svg)
            ->toContain('<svg')
            ->toContain('</svg>');
    });
});

describe('icon component', function () {
    it('renders icon component with correct attributes', function () {
        $component = new \App\View\Components\Icon('chart');
        $view = $component->render();
        
        expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
        expect($component->icon)->toBe('heroicon-o-chart-bar');
        expect($component->class)->toBe('h-5 w-5');
    });
});

describe('icon in welcome page', function () {
    it('renders welcome page with icons', function () {
        $response = get('/');
        
        $response->assertStatus(200);
        $response->assertSee('<svg', false);
    });
});
