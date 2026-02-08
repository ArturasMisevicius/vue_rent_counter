<?php

use App\Exceptions\InvalidMeterReadingException;
use App\Exceptions\InvoiceException;
use App\Exceptions\TariffNotFoundException;

test('InvalidMeterReadingException creates monotonicity exception', function () {
    $exception = InvalidMeterReadingException::monotonicity(50.0, 100.0);
    
    expect($exception->getMessage())->toContain('50')
        ->and($exception->getMessage())->toContain('100')
        ->and($exception->getMessage())->toContain('cannot be lower');
});

test('InvalidMeterReadingException creates future date exception', function () {
    $exception = InvalidMeterReadingException::futureDate();
    
    expect($exception->getMessage())->toContain('future');
});

test('InvalidMeterReadingException creates zone not supported exception', function () {
    $exception = InvalidMeterReadingException::zoneNotSupported('M12345');
    
    expect($exception->getMessage())->toContain('M12345')
        ->and($exception->getMessage())->toContain('does not support');
});

test('InvalidMeterReadingException creates zone required exception', function () {
    $exception = InvalidMeterReadingException::zoneRequired('M12345');
    
    expect($exception->getMessage())->toContain('M12345')
        ->and($exception->getMessage())->toContain('required');
});

test('TariffNotFoundException creates for provider exception', function () {
    $exception = TariffNotFoundException::forProvider(1, '2024-01-15');
    
    expect($exception->getMessage())->toContain('provider 1')
        ->and($exception->getMessage())->toContain('2024-01-15');
});

test('TariffNotFoundException creates invalid configuration exception', function () {
    $exception = TariffNotFoundException::invalidConfiguration('Missing rate field');
    
    expect($exception->getMessage())->toContain('Missing rate field');
});

test('InvoiceException creates already finalized exception', function () {
    $exception = InvoiceException::alreadyFinalized(123);
    
    expect($exception->getMessage())->toContain('123')
        ->and($exception->getMessage())->toContain('finalized');
});

test('InvoiceException creates invalid status transition exception', function () {
    $exception = InvoiceException::invalidStatusTransition('paid', 'draft');
    
    expect($exception->getMessage())->toContain('paid')
        ->and($exception->getMessage())->toContain('draft');
});

test('InvoiceException creates no items exception', function () {
    $exception = InvoiceException::noItems();
    
    expect($exception->getMessage())->toContain('without any items');
});
