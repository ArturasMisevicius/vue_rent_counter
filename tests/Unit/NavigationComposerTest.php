<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\User;
use App\View\Composers\NavigationComposer;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

beforeEach(function () {
    $this->auth = Mockery::mock(Guard::class);
    $this->router = Mockery::mock(Router::class);
    $this->composer = new NavigationComposer($this->auth, $this->router);
    $this->view = Mockery::mock(View::class);
});

afterEach(function () {
    Mockery::close();
});

it('does not compose view data when user is not authenticated', function () {
    $this->auth->shouldReceive('check')->once()->andReturn(false);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn(null);
    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['userRole'] === null
            && $data['currentRoute'] === null
            && $data['showTopLocaleSwitcher'] === false
            && $data['backofficeLinks'] === [];
    });

    $this->composer->compose($this->view);
});

it('composes view data for authenticated admin user', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    Language::factory()->count(3)->create(['is_active' => true]);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['userRole'] === 'admin'
            && $data['currentRoute'] === 'admin.dashboard'
            && $data['showTopLocaleSwitcher'] === false
            && $data['languages']->count() === 3;
    });

    $this->composer->compose($this->view);
});

it('hides locale switcher for manager role', function () {
    $user = User::factory()->make([
        'role' => UserRole::MANAGER,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('manager.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['userRole'] === 'manager'
            && $data['showTopLocaleSwitcher'] === false
            && $data['languages']->isEmpty();
    });

    $this->composer->compose($this->view);
});

it('hides locale switcher for tenant role', function () {
    $user = User::factory()->make([
        'role' => UserRole::TENANT,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('tenant.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['userRole'] === 'tenant'
            && $data['showTopLocaleSwitcher'] === false;
    });

    $this->composer->compose($this->view);
});

it('hides locale switcher for superadmin role', function () {
    $user = User::factory()->make([
        'role' => UserRole::SUPERADMIN,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('superadmin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['userRole'] === 'superadmin'
            && $data['showTopLocaleSwitcher'] === false;
    });

    $this->composer->compose($this->view);
});

it('returns only active languages ordered by display_order', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    Language::factory()->create(['is_active' => true, 'display_order' => 2, 'code' => 'lt']);
    Language::factory()->create(['is_active' => false, 'display_order' => 1, 'code' => 'ru']);
    Language::factory()->create(['is_active' => true, 'display_order' => 1, 'code' => 'en']);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        $languages = $data['languages'];

        return $languages->count() === 2
            && $languages->first()->code === 'en'
            && $languages->last()->code === 'lt';
    });

    $this->composer->compose($this->view);
});

it('provides consistent CSS classes for active and inactive states', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['activeClass'] === $data['mobileActiveClass']
            && $data['inactiveClass'] === $data['mobileInactiveClass']
            && ! empty($data['activeClass'])
            && ! empty($data['inactiveClass']);
    });

    $this->composer->compose($this->view);
});

it('disables locale switching when language route does not exist', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(false);

    Language::factory()->count(3)->create(['is_active' => true]);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['showTopLocaleSwitcher'] === false
            && $data['canSwitchLocale'] === false
            && $data['languages']->count() === 3;
    });

    $this->composer->compose($this->view);
});

it('includes current locale in view data', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    app()->setLocale('lt');

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['currentLocale'] === 'lt';
    });

    $this->composer->compose($this->view);
});

it('handles null current route gracefully', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn(null);
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['currentRoute'] === null;
    });

    $this->composer->compose($this->view);
});

it('filters out inactive languages even when admin', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    Language::factory()->create(['is_active' => true, 'code' => 'en']);
    Language::factory()->create(['is_active' => false, 'code' => 'ru']);
    Language::factory()->create(['is_active' => false, 'code' => 'de']);
    Language::factory()->create(['is_active' => true, 'code' => 'lt']);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        $languages = $data['languages'];

        return $languages->count() === 2
            && $languages->pluck('code')->contains('en')
            && $languages->pluck('code')->contains('lt')
            && ! $languages->pluck('code')->contains('ru')
            && ! $languages->pluck('code')->contains('de');
    });

    $this->composer->compose($this->view);
});

it('respects display_order for language sorting', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    Language::factory()->create(['is_active' => true, 'display_order' => 3, 'code' => 'ru']);
    Language::factory()->create(['is_active' => true, 'display_order' => 1, 'code' => 'en']);
    Language::factory()->create(['is_active' => true, 'display_order' => 2, 'code' => 'lt']);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        $languages = $data['languages'];
        $codes = $languages->pluck('code')->toArray();

        return $codes === ['en', 'lt', 'ru'];
    });

    $this->composer->compose($this->view);
});

it('provides all required view variables', function () {
    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        $requiredKeys = [
            'userRole',
            'currentRoute',
            'activeClass',
            'inactiveClass',
            'mobileActiveClass',
            'mobileInactiveClass',
            'canSwitchLocale',
            'showTopLocaleSwitcher',
            'languages',
            'currentLocale',
        ];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    });

    $this->composer->compose($this->view);
});

it('does not query database when user is not authenticated', function () {
    $this->auth->shouldReceive('check')->once()->andReturn(false);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn(null);
    $this->view->shouldReceive('with')->once();

    // No database queries should be executed
    $queryCount = 0;
    DB::listen(function ($query) use (&$queryCount) {
        $queryCount++;
    });

    $this->composer->compose($this->view);

    expect($queryCount)->toBe(0);
});

it('still queries languages even when locale switcher is hidden', function () {
    $user = User::factory()->make([
        'role' => UserRole::MANAGER,
    ]);

    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('manager.dashboard');
    $this->router->shouldReceive('has')->with('language.switch')->times(2)->andReturn(true);

    // Track queries
    $languageQueriesExecuted = false;
    DB::listen(function ($query) use (&$languageQueriesExecuted) {
        if (str_contains($query->sql, 'languages')) {
            $languageQueriesExecuted = true;
        }
    });

    $this->view->shouldReceive('with')->once();

    $this->composer->compose($this->view);

    expect($languageQueriesExecuted)->toBeTrue();
});
