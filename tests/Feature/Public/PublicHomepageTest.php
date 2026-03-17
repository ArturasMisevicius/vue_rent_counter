<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the tester-first public homepage', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSeeText('Property operations platform, presented as a guided testing lab.')
        ->assertSeeText('Login')
        ->assertSeeText('Register')
        ->assertSeeText('Superadmin')
        ->assertSeeText('Admin')
        ->assertSeeText('Manager')
        ->assertSeeText('Tenant')
        ->assertSeeText('For system testers')
        ->assertSeeText('What Tenanto is growing into')
        ->assertSeeText('Ready to explore the public flow?');
});

it('renders the homepage locale switcher with all supported guest locales', function () {
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
        ->assertSeeText('Turto operacijų platforma, pateikta kaip kryptinga testavimo erdvė.');
});

it('renders the homepage in spanish when the guest locale is es', function () {
    $this->withSession([
        'guest_locale' => 'es',
    ])->get('/')
        ->assertSuccessful()
        ->assertSeeText('Plataforma de operaciones inmobiliarias presentada como un laboratorio de pruebas guiado.');
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
