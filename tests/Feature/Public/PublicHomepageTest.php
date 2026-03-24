<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the tester-first public homepage', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('min-h-screen bg-brand-ink text-white antialiased', false)
        ->assertSee('bg-[radial-gradient(circle_at_top', false)
        ->assertSeeText(__('landing.hero.title', [], 'en'))
        ->assertSeeText(__('landing.cta.login', [], 'en'))
        ->assertSeeText(__('landing.cta.register', [], 'en'))
        ->assertSeeText('Superadmin')
        ->assertSeeText('Admin')
        ->assertSeeText('Manager')
        ->assertSeeText('Tenant')
        ->assertSeeText(__('landing.tester.heading', [], 'en'))
        ->assertSeeText(__('landing.tester.items.0', [], 'en'))
        ->assertSeeText(__('landing.roadmap.heading', [], 'en'))
        ->assertSeeText(__('landing.roadmap.items.4.title', [], 'en'))
        ->assertSeeText(__('landing.cta.heading', [], 'en'));
});

it('renders the homepage locale switcher with configured guest locales', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('value="en"', false)
        ->assertSee('value="lt"', false)
        ->assertSee('value="es"', false)
        ->assertSee('value="ru"', false);
});

it('renders the homepage in lithuanian when the guest locale is lt', function () {
    $this->withSession([
        'guest_locale' => 'lt',
    ])->get('/')
        ->assertSuccessful()
        ->assertSeeText(__('landing.hero.title', [], 'lt'));
});

it('falls back to english when the guest locale is unsupported', function () {
    $this->withSession([
        'guest_locale' => 'de',
    ])->get('/')
        ->assertSuccessful()
        ->assertSeeText(__('landing.hero.title', [], 'en'))
        ->assertSeeText(__('landing.cta.login', [], 'en'))
        ->assertSeeText(__('landing.cta.register', [], 'en'));
});

it('renders the homepage in russian when the guest locale is ru', function () {
    $this->withSession([
        'guest_locale' => 'ru',
    ])->get('/')
        ->assertSuccessful()
        ->assertSeeText(__('landing.hero.title', [], 'ru'))
        ->assertSeeText(__('landing.cta.login', [], 'ru'))
        ->assertSeeText(__('landing.cta.register', [], 'ru'));
});

it('renders the homepage in spanish when the guest locale is es', function () {
    $this->withSession([
        'guest_locale' => 'es',
    ])->get('/')
        ->assertSuccessful()
        ->assertSeeText(__('landing.hero.title', [], 'es'))
        ->assertSeeText(__('landing.cta.login', [], 'es'))
        ->assertSeeText(__('landing.cta.register', [], 'es'));
});

it('links the favicon on public and guest pages', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('/favicon', false)
        ->assertSee('rel="icon"', false);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('/favicon', false)
        ->assertSee('rel="icon"', false);
});

it('serves the favicon asset', function () {
    $this->get('/favicon')
        ->assertSuccessful()
        ->assertHeader('content-type', 'image/x-icon');
});
