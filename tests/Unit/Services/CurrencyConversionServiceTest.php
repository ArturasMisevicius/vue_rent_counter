<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\CurrencyConversionService;
use App\Services\ExchangeRateProviderService;
use App\ValueObjects\ConversionResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CurrencyConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyConversionService $service;
    private Currency $usd;
    private Currency $eur;
    private Currency $gbp;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock exchange rate provider
        $mockProvider = $this->createMock(ExchangeRateProviderService::class);
        $this->service = new CurrencyConversionService($mockProvider);
        
        // Create test currencies
        $this->usd = Currency::factory()->usd()->create();
        $this->eur = Currency::factory()->eur()->create();
        $this->gbp = Currency::factory()->gbp()->create();
    }

    public function test_converts_same_currency(): void
    {
        $result = $this->service->convert(100.0, $this->usd, $this->usd);

        $this->assertInstanceOf(ConversionResult::class, $result);
        $this->assertEquals(100.0, $result->getOriginalAmount());
        $this->assertEquals(100.0, $result->getConvertedAmount());
        $this->assertEquals(1.0, $result->getExchangeRate());
        $this->assertEquals('same_currency', $result->getSource());
        $this->assertTrue($result->isSameCurrency());
    }

    public function test_converts_using_database_rate(): void
    {
        // Create exchange rate in database
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->effectiveOn(now())
            ->create();

        $result = $this->service->convert(100.0, $this->usd, $this->eur);

        $this->assertInstanceOf(ConversionResult::class, $result);
        $this->assertEquals(100.0, $result->getOriginalAmount());
        $this->assertEquals(85.0, $result->getConvertedAmount());
        $this->assertEquals(0.85, $result->getExchangeRate());
        $this->assertEquals('database', $result->getSource());
        $this->assertFalse($result->isSameCurrency());
        $this->assertTrue($result->isReliable());
    }

    public function test_converts_using_historical_rate(): void
    {
        $historicalDate = now()->subDays(30);
        
        // Create historical exchange rate
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.82)
            ->effectiveOn($historicalDate)
            ->create();

        $result = $this->service->convert(100.0, $this->usd, $this->eur, $historicalDate);

        $this->assertEquals(82.0, $result->getConvertedAmount());
        $this->assertEquals(0.82, $result->getExchangeRate());
        $this->assertEquals($historicalDate->toDateString(), $result->getConversionDate()->toDateString());
    }

    public function test_converts_using_reverse_rate(): void
    {
        // Create only EUR to USD rate (reverse of what we need)
        ExchangeRate::factory()
            ->forCurrencyPair($this->eur, $this->usd)
            ->withRate(1.18)
            ->effectiveOn(now())
            ->create();

        $result = $this->service->convert(100.0, $this->usd, $this->eur);

        $this->assertEquals(100.0, $result->getOriginalAmount());
        $this->assertEqualsWithDelta(84.75, $result->getConvertedAmount(), 0.01); // 100 / 1.18
        $this->assertEquals('reverse_calculation', $result->getSource());
    }

    public function test_throws_exception_when_no_rate_found(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No exchange rate found for USD to GBP');

        $this->service->convert(100.0, $this->usd, $this->gbp);
    }

    public function test_converts_batch_amounts(): void
    {
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->create();

        $amounts = [
            'amount1' => 100.0,
            'amount2' => 200.0,
            'amount3' => 50.0,
        ];

        $results = $this->service->convertBatch($amounts, $this->usd, $this->eur);

        $this->assertCount(3, $results);
        $this->assertInstanceOf(ConversionResult::class, $results['amount1']);
        $this->assertEquals(85.0, $results['amount1']->getConvertedAmount());
        $this->assertEquals(170.0, $results['amount2']->getConvertedAmount());
        $this->assertEquals(42.5, $results['amount3']->getConvertedAmount());
    }

    public function test_batch_conversion_handles_errors(): void
    {
        $amounts = [
            'valid' => 100.0,
            'invalid' => 200.0, // This will fail due to no exchange rate
        ];

        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->create();

        // Only convert the first amount, second will fail
        $results = $this->service->convertBatch(['valid' => 100.0], $this->usd, $this->eur);
        $errorResults = $this->service->convertBatch(['invalid' => 200.0], $this->usd, $this->gbp);

        $this->assertInstanceOf(ConversionResult::class, $results['valid']);
        $this->assertArrayHasKey('error', $errorResults['invalid']);
        $this->assertEquals(200.0, $errorResults['invalid']['original_amount']);
    }

    public function test_gets_historical_rates(): void
    {
        $startDate = now()->subDays(10);
        $endDate = now()->subDays(5);
        
        // Create multiple historical rates
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.83)
            ->effectiveOn($startDate)
            ->create();
            
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.84)
            ->effectiveOn($startDate->copy()->addDays(2))
            ->create();
            
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->effectiveOn($endDate)
            ->create();

        $rates = $this->service->getHistoricalRates($this->usd, $this->eur, $startDate, $endDate);

        $this->assertCount(3, $rates);
        $this->assertEquals(0.83, $rates->first()['rate']);
        $this->assertEquals(0.85, $rates->last()['rate']);
    }

    public function test_converts_invoice_amounts(): void
    {
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->create();

        $invoiceData = [
            'currency_id' => $this->usd->id,
            'subtotal' => 100.0,
            'tax_amount' => 20.0,
            'total_amount' => 120.0,
            'paid_amount' => 50.0,
            'balance' => 70.0,
            'line_items' => [
                ['description' => 'Item 1', 'amount' => 60.0],
                ['description' => 'Item 2', 'amount' => 40.0],
            ],
        ];

        $converted = $this->service->convertInvoiceAmounts($invoiceData, $this->eur);

        $this->assertEquals($this->eur->id, $converted['currency_id']);
        $this->assertEquals(85.0, $converted['subtotal']);
        $this->assertEquals(17.0, $converted['tax_amount']);
        $this->assertEquals(102.0, $converted['total_amount']);
        $this->assertEquals(42.5, $converted['paid_amount']);
        $this->assertEquals(59.5, $converted['balance']);
        
        // Check original amounts are preserved
        $this->assertEquals(100.0, $converted['subtotal_original']);
        $this->assertEquals(0.85, $converted['subtotal_exchange_rate']);
        
        // Check line items
        $this->assertEquals(51.0, $converted['line_items'][0]['amount']);
        $this->assertEquals(60.0, $converted['line_items'][0]['amount_original']);
        $this->assertEquals(34.0, $converted['line_items'][1]['amount']);
        $this->assertEquals(40.0, $converted['line_items'][1]['amount_original']);
        
        $this->assertArrayHasKey('conversion_date', $converted);
    }

    public function test_conversion_result_methods(): void
    {
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->create();

        $result = $this->service->convert(100.0, $this->usd, $this->eur);

        $this->assertEquals('$ 100.00', $result->getFormattedOriginalAmount());
        $this->assertEquals('€ 85.00', $result->getFormattedConvertedAmount());
        $this->assertStringContains('$ 100.00 → € 85.00', $result->getConversionSummary());
        $this->assertEquals(0.85, $result->getConversionFactor());
        
        $array = $result->toArray();
        $this->assertArrayHasKey('original_amount', $array);
        $this->assertArrayHasKey('converted_amount', $array);
        $this->assertArrayHasKey('from_currency', $array);
        $this->assertArrayHasKey('to_currency', $array);
        $this->assertArrayHasKey('exchange_rate', $array);
        $this->assertArrayHasKey('conversion_summary', $array);
    }

    public function test_uses_most_recent_rate_for_date(): void
    {
        $date = now()->subDays(5);
        
        // Create older rate
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.80)
            ->effectiveOn($date->copy()->subDays(2))
            ->create();
            
        // Create more recent rate for the same date
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->effectiveOn($date)
            ->create();

        $result = $this->service->convert(100.0, $this->usd, $this->eur, $date);

        // Should use the more recent rate (0.85)
        $this->assertEquals(85.0, $result->getConvertedAmount());
        $this->assertEquals(0.85, $result->getExchangeRate());
    }

    public function test_handles_inactive_rates(): void
    {
        // Create inactive rate
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.80)
            ->inactive()
            ->create();
            
        // Create active rate
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->create();

        $result = $this->service->convert(100.0, $this->usd, $this->eur);

        // Should use the active rate (0.85)
        $this->assertEquals(85.0, $result->getConvertedAmount());
        $this->assertEquals(0.85, $result->getExchangeRate());
    }

    public function test_conversion_with_zero_amount(): void
    {
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->create();

        $result = $this->service->convert(0.0, $this->usd, $this->eur);

        $this->assertEquals(0.0, $result->getOriginalAmount());
        $this->assertEquals(0.0, $result->getConvertedAmount());
        $this->assertEquals(0.0, $result->getConversionFactor());
    }

    public function test_conversion_with_negative_amount(): void
    {
        ExchangeRate::factory()
            ->forCurrencyPair($this->usd, $this->eur)
            ->withRate(0.85)
            ->create();

        $result = $this->service->convert(-100.0, $this->usd, $this->eur);

        $this->assertEquals(-100.0, $result->getOriginalAmount());
        $this->assertEquals(-85.0, $result->getConvertedAmount());
    }
}