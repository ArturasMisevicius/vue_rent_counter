<?php

declare(strict_types=1);

namespace App\DTOs\Enhanced;

use App\Enums\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Payment Processing DTO
 * 
 * Data transfer object for payment processing operations.
 * Provides type safety and validation for payment data.
 * 
 * @package App\DTOs\Enhanced
 */
final readonly class PaymentProcessingDTO
{
    public function __construct(
        public int $invoiceId,
        public float $amount,
        public PaymentMethod $paymentMethod,
        public string $paymentReference,
        public Carbon $paymentDate,
        public ?string $notes = null
    ) {
        $this->validate();
    }

    /**
     * Create DTO from HTTP request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            invoiceId: (int) $request->input('invoice_id'),
            amount: (float) $request->input('amount'),
            paymentMethod: PaymentMethod::from($request->input('payment_method')),
            paymentReference: $request->input('payment_reference'),
            paymentDate: Carbon::parse($request->input('payment_date', now())),
            notes: $request->input('notes')
        );
    }

    /**
     * Create DTO from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (int) $data['invoice_id'],
            amount: (float) $data['amount'],
            paymentMethod: $data['payment_method'] instanceof PaymentMethod 
                ? $data['payment_method'] 
                : PaymentMethod::from($data['payment_method']),
            paymentReference: $data['payment_reference'],
            paymentDate: $data['payment_date'] instanceof Carbon 
                ? $data['payment_date'] 
                : Carbon::parse($data['payment_date']),
            notes: $data['notes'] ?? null
        );
    }

    /**
     * Convert to array for model creation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'payment_reference' => $this->paymentReference,
            'payment_date' => $this->paymentDate->toDateString(),
            'notes' => $this->notes,
        ];
    }

    /**
     * Validate DTO data.
     *
     * @throws \InvalidArgumentException If validation fails
     */
    private function validate(): void
    {
        if ($this->invoiceId <= 0) {
            throw new \InvalidArgumentException('Invoice ID must be a positive integer');
        }

        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero');
        }

        if ($this->amount > 999999.99) {
            throw new \InvalidArgumentException('Payment amount exceeds maximum allowed value');
        }

        if (empty($this->paymentReference)) {
            throw new \InvalidArgumentException('Payment reference is required');
        }

        if (strlen($this->paymentReference) > 255) {
            throw new \InvalidArgumentException('Payment reference is too long (max 255 characters)');
        }

        if ($this->paymentDate->isFuture()) {
            throw new \InvalidArgumentException('Payment date cannot be in the future');
        }

        if ($this->paymentDate->lt(Carbon::now()->subYears(5))) {
            throw new \InvalidArgumentException('Payment date cannot be more than 5 years in the past');
        }

        if ($this->notes && strlen($this->notes) > 1000) {
            throw new \InvalidArgumentException('Payment notes are too long (max 1000 characters)');
        }
    }
}