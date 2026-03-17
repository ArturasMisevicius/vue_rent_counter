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
        ->assertSeeText('For system testers');
});

it('renders the homepage in lithuanian when the guest locale is lt', function () {
    $this->withSession([
        'guest_locale' => 'lt',
    ])->get('/')
        ->assertSuccessful()
        ->assertSeeText('Turto operacijų platforma, pateikta kaip kryptinga testavimo erdvė.');
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
