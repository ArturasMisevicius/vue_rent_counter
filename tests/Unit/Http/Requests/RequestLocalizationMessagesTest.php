<?php

declare(strict_types=1);

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\CspViolationRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;

test('api login request messages are localized', function () {
    app()->setLocale('lt');

    $messages = (new LoginRequest)->messages();

    expect($messages['email.required'])->toBe(__('validation.custom_requests.api_login.email.required'))
        ->and($messages['email.email'])->toBe(__('validation.custom_requests.api_login.email.email'))
        ->and($messages['password.required'])->toBe(__('validation.custom_requests.api_login.password.required'))
        ->and($messages['password.min'])->toBe(__('validation.custom_requests.api_login.password.min'));
});

test('csp violation request messages are localized', function () {
    app()->setLocale('ru');

    $messages = (new CspViolationRequest)->messages();

    expect($messages['csp-report.required'])->toBe(__('validation.custom_requests.csp_violation.report_required'))
        ->and($messages['csp-report.violated-directive.required'])->toBe(__('validation.custom_requests.csp_violation.violated_directive_required'))
        ->and($messages['csp-report.document-uri.required'])->toBe(__('validation.custom_requests.csp_violation.document_uri_required'));
});

test('property request messages and attributes are localized', function () {
    app()->setLocale('en');

    $storeRequest = new StorePropertyRequest;
    $updateRequest = new UpdatePropertyRequest;

    expect($storeRequest->messages()['building_id.exists'])->toBe(__('validation.custom_requests.properties.building_must_belong'))
        ->and($updateRequest->messages()['building_id.exists'])->toBe(__('validation.custom_requests.properties.building_must_belong'))
        ->and($storeRequest->attributes()['area_sqm'])->toBe(__('validation.custom_requests.properties.attributes.area_sqm'))
        ->and($storeRequest->attributes()['unit_number'])->toBe(__('validation.custom_requests.properties.attributes.unit_number'));
});
