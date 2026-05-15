<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\FormRequestScenarioFactory;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);
it('accepts valid payloads for every form request', function (array $scenario): void {
    $context = FormRequestScenarioFactory::context();
    $request = buildRequestFromScenario($scenario, $context);
    $payload = payloadFromScenario($scenario, $context);
    $user = authorizedUserForScenario($scenario, $context);

    syncAuthenticatedUser($user);

    $validated = FormRequestScenarioFactory::validatedPayload($request, $context, $payload, $user);

    expect($validated)->toBeArray();
})->with(fn (): array => scenarioDatasets());

it('rejects missing required fields for every form request', function (array $scenario): void {
    $context = FormRequestScenarioFactory::context();
    $request = buildRequestFromScenario($scenario, $context);
    $payload = payloadFromScenario($scenario, $context);
    $user = authorizedUserForScenario($scenario, $context);
    $requiredFields = $scenario['required'];

    syncAuthenticatedUser($user);

    if ($requiredFields === []) {
        $validator = FormRequestScenarioFactory::validatorFor($request, $context, [], $user);

        expect($validator->fails())->toBeFalse();

        return;
    }

    foreach ($requiredFields as $field) {
        $validator = FormRequestScenarioFactory::validatorFor(
            $request,
            $context,
            FormRequestScenarioFactory::withoutField($payload, $field),
            $user,
        );

        expect($validator->fails(), $field)->toBeTrue()
            ->and($validator->errors()->keys())->toContain($field);
    }
})->with(fn (): array => scenarioDatasets());

it('rejects malformed data for every form request', function (array $scenario): void {
    $context = FormRequestScenarioFactory::context();
    $request = buildRequestFromScenario($scenario, $context);
    $user = authorizedUserForScenario($scenario, $context);
    $invalidCases = FormRequestScenarioFactory::invalidCases($scenario, $context);

    syncAuthenticatedUser($user);

    expect($invalidCases)->not->toBeEmpty();

    foreach ($invalidCases as $name => $case) {
        $validator = FormRequestScenarioFactory::validatorFor(
            $request,
            $context,
            $case['input'],
            $user,
        );

        expect($validator->fails(), $name)->toBeTrue()
            ->and($validator->errors()->keys(), $name)->toContain($case['field']);
    }
})->with(fn (): array => scenarioDatasets());

it('renders Lithuanian validation messages for every form request', function (array $scenario): void {
    app()->setLocale('lt');

    $context = FormRequestScenarioFactory::context();
    $request = buildRequestFromScenario($scenario, $context);
    $payload = payloadFromScenario($scenario, $context);
    $user = authorizedUserForScenario($scenario, $context);
    $requiredFields = $scenario['required'];

    syncAuthenticatedUser($user);

    foreach ($requiredFields as $field) {
        $validator = FormRequestScenarioFactory::validatorFor(
            $request,
            $context,
            FormRequestScenarioFactory::withoutField($payload, $field),
            $user,
        );

        expect($validator->fails(), $field)->toBeTrue();

        assertLithuanianValidationMessage(
            $validator->errors()->first($field),
            class_basename($request).' '.$field,
        );
    }

    foreach (FormRequestScenarioFactory::invalidCases($scenario, $context) as $name => $case) {
        $validator = FormRequestScenarioFactory::validatorFor(
            $request,
            $context,
            $case['input'],
            $user,
        );

        expect($validator->fails(), $name)->toBeTrue();

        assertLithuanianValidationMessage(
            $validator->errors()->first($case['field']),
            class_basename($request).' '.$name,
        );
    }
})->with(fn (): array => scenarioDatasets());

it('authorizes the expected roles for every form request', function (array $scenario): void {
    $context = FormRequestScenarioFactory::context();
    $request = buildRequestFromScenario($scenario, $context);
    $payload = payloadFromScenario($scenario, $context);

    foreach (FormRequestScenarioFactory::authorizationMap($scenario) as $role => $expected) {
        $user = FormRequestScenarioFactory::userForRole($context, $role);

        syncAuthenticatedUser($user);

        expect($request->authorizePayload($user, $payload), class_basename($request).' '.$role)->toBe($expected);
    }
})->with(fn (): array => scenarioDatasets());

/**
 * @param  array<string, mixed>  $scenario
 * @param  array<string, mixed>  $context
 */
function buildRequestFromScenario(array $scenario, array $context): FormRequest
{
    /** @var FormRequest $request */
    $request = $scenario['request']($context);

    return $request;
}

/**
 * @param  array<string, mixed>  $scenario
 * @param  array<string, mixed>  $context
 * @return array<string, mixed>
 */
function payloadFromScenario(array $scenario, array $context): array
{
    /** @var array<string, mixed> $payload */
    $payload = $scenario['valid']($context);

    return $payload;
}

/**
 * @param  array<string, mixed>  $scenario
 * @param  array<string, mixed>  $context
 */
function authorizedUserForScenario(array $scenario, array $context): ?User
{
    $authorization = FormRequestScenarioFactory::authorizationMap($scenario);

    foreach (['admin', 'manager', 'tenant', 'superadmin'] as $role) {
        if ($authorization[$role] ?? false) {
            return FormRequestScenarioFactory::userForRole($context, $role);
        }
    }

    return null;
}

function syncAuthenticatedUser(?User $user): void
{
    if ($user === null) {
        Auth::logout();

        return;
    }

    Auth::login($user);
}

function assertLithuanianValidationMessage(string $message, string $context): void
{
    $englishFragments = [
        'The ',
        ' field',
        ' must ',
        ' required',
        ' valid',
        ' selected',
        ' confirmation',
        ' does not match',
        ' may not ',
        ' invalid',
        ' incorrect',
        ' greater than',
        ' less than',
        ' at least',
        ' already been taken',
        'validation.',
        'requests.',
        'tenant.',
    ];

    expect($message, $context)
        ->not->toBe('')
        ->and($message, $context)->not->toContain(...$englishFragments);
}

/**
 * @return array<string, array{0: array<string, mixed>}>
 */
function scenarioDatasets(): array
{
    return collect(FormRequestScenarioFactory::scenarios())
        ->map(static fn (array $scenario): array => [$scenario])
        ->all();
}
