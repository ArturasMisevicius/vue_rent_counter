<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use BackedEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use UnitEnum;

trait InteractsWithValidationPayload
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validatePayload(array $input, ?Authenticatable $user = null): array
    {
        $resolvedUser = $user ?? auth()->user();
        $request = $this->validationRequest($input, $resolvedUser);

        if ($resolvedUser !== null && ! $request->authorize()) {
            throw new AuthorizationException;
        }

        $request->prepareForValidation();

        /** @var array<string, mixed> $validated */
        $validated = Validator::make(
            $request->all(),
            $request->rules(),
            $request->messages(),
            $request->attributes(),
        )->validate();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function authorizePayload(?Authenticatable $user = null, array $input = []): bool
    {
        return $this->validationRequest($input, $user)->authorize();
    }

    protected function trimStrings(array $keys): void
    {
        $input = $this->all();

        foreach ($keys as $key) {
            $value = data_get($input, $key);

            if (is_string($value)) {
                data_set($input, $key, trim($value));
            }
        }

        $this->replace($input);
    }

    protected function emptyStringsToNull(array $keys): void
    {
        $input = $this->all();

        foreach ($keys as $key) {
            $value = data_get($input, $key);

            if (is_string($value) && trim($value) === '') {
                data_set($input, $key, null);
            }
        }

        $this->replace($input);
    }

    protected function castBooleans(array $keys): void
    {
        $input = $this->all();

        foreach ($keys as $key) {
            if (! Arr::has($input, $key)) {
                continue;
            }

            $value = data_get($input, $key);

            if (is_bool($value)) {
                continue;
            }

            if (is_int($value) && in_array($value, [0, 1], true)) {
                data_set($input, $key, (bool) $value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                data_set($input, $key, true);

                continue;
            }

            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                data_set($input, $key, false);
            }
        }

        $this->replace($input);
    }

    /**
     * @param  list<string>  $attributes
     * @return array<string, string>
     */
    protected function translatedAttributes(array $attributes): array
    {
        return collect($attributes)
            ->mapWithKeys(fn (string $attribute): array => [
                $attribute => $this->translateAttribute($attribute),
            ])
            ->all();
    }

    /**
     * @param  array<string, array{0: string, 1: string, 2?: array<string, mixed>}>  $messages
     * @return array<string, string>
     */
    protected function translatedMessages(array $messages): array
    {
        return collect($messages)
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => $this->translateValidationMessage(
                    $definition[0],
                    $definition[1],
                    $definition[2] ?? [],
                ),
            ])
            ->all();
    }

    protected function translateAttribute(string $attribute): string
    {
        return (string) __('requests.attributes.'.$attribute);
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    protected function translateValidationMessage(
        string $validationKey,
        string $attribute,
        array $replace = [],
    ): string {
        return (string) __('validation.'.$validationKey, [
            'attribute' => $this->translateAttribute($attribute),
            ...$replace,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function validationRequest(array $input, ?Authenticatable $user = null): static
    {
        $request = clone $this;

        $request->replace($this->normalizeRequestInput($input));
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setUserResolver(static fn (): ?Authenticatable => $user ?? auth()->user());

        $currentRequest = request();

        if ($currentRequest !== null) {
            $request->setRouteResolver($currentRequest->getRouteResolver());
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function normalizeRequestInput(array $input): array
    {
        return collect($input)
            ->map(fn (mixed $value): mixed => $this->normalizeRequestValue($value))
            ->all();
    }

    protected function normalizeRequestValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $nested): mixed => $this->normalizeRequestValue($nested))
                ->all();
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
    }
}
