<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\ValueObjects\SecurityNonce;
use InvalidArgumentException;

/**
 * Content Security Policy Header Builder
 * 
 * Fluent builder for constructing CSP headers with proper validation
 * and nonce integration.
 */
final class CspHeaderBuilder
{
    private array $directives = [];
    private ?SecurityNonce $nonce = null;

    /**
     * Set default-src directive.
     */
    public function defaultSrc(string ...$sources): self
    {
        return $this->setDirective('default-src', $sources);
    }

    /**
     * Set script-src directive.
     */
    public function scriptSrc(string ...$sources): self
    {
        return $this->setDirective('script-src', $sources);
    }

    /**
     * Set style-src directive.
     */
    public function styleSrc(string ...$sources): self
    {
        return $this->setDirective('style-src', $sources);
    }

    /**
     * Set img-src directive.
     */
    public function imgSrc(string ...$sources): self
    {
        return $this->setDirective('img-src', $sources);
    }

    /**
     * Set font-src directive.
     */
    public function fontSrc(string ...$sources): self
    {
        return $this->setDirective('font-src', $sources);
    }

    /**
     * Set connect-src directive.
     */
    public function connectSrc(string ...$sources): self
    {
        return $this->setDirective('connect-src', $sources);
    }

    /**
     * Set frame-ancestors directive.
     */
    public function frameAncestors(string ...$sources): self
    {
        return $this->setDirective('frame-ancestors', $sources);
    }

    /**
     * Set frame-src directive.
     */
    public function frameSrc(string ...$sources): self
    {
        return $this->setDirective('frame-src', $sources);
    }

    /**
     * Set object-src directive.
     */
    public function objectSrc(string ...$sources): self
    {
        return $this->setDirective('object-src', $sources);
    }

    /**
     * Set base-uri directive.
     */
    public function baseUri(string ...$sources): self
    {
        return $this->setDirective('base-uri', $sources);
    }

    /**
     * Set form-action directive.
     */
    public function formAction(string ...$sources): self
    {
        return $this->setDirective('form-action', $sources);
    }

    /**
     * Set nonce for script and style sources.
     */
    public function withNonce(SecurityNonce $nonce): self
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * Add nonce to script-src if not already present.
     */
    public function addNonceToScripts(): self
    {
        if ($this->nonce === null) {
            throw new InvalidArgumentException('Nonce must be set before adding to scripts');
        }

        $this->addNonceToDirective('script-src');
        return $this;
    }

    /**
     * Add nonce to style-src if not already present.
     */
    public function addNonceToStyles(): self
    {
        if ($this->nonce === null) {
            throw new InvalidArgumentException('Nonce must be set before adding to styles');
        }

        $this->addNonceToDirective('style-src');
        return $this;
    }

    /**
     * Build the CSP header value.
     */
    public function build(): string
    {
        if (empty($this->directives)) {
            throw new InvalidArgumentException('At least one CSP directive must be set');
        }

        $parts = [];
        foreach ($this->directives as $directive => $sources) {
            $parts[] = $directive . ' ' . implode(' ', $sources);
        }

        return implode('; ', $parts);
    }

    /**
     * Create a new builder with strict defaults.
     */
    public static function strict(): self
    {
        return (new self())
            ->defaultSrc("'self'")
            ->scriptSrc("'self'")
            ->styleSrc("'self'")
            ->imgSrc("'self'", 'data:', 'https:')
            ->fontSrc("'self'")
            ->connectSrc("'self'")
            ->frameAncestors("'none'")
            ->frameSrc("'none'")
            ->objectSrc("'none'")
            ->baseUri("'self'")
            ->formAction("'self'");
    }

    /**
     * Create a new builder with development-friendly defaults.
     */
    public static function development(): self
    {
        return (new self())
            ->defaultSrc("'self'")
            ->scriptSrc("'self'", 'cdn.tailwindcss.com', 'cdn.jsdelivr.net', 'localhost:*', '127.0.0.1:*')
            ->styleSrc("'self'", 'fonts.googleapis.com', "'unsafe-inline'") // Allow inline styles in dev
            ->imgSrc("'self'", 'data:', 'https:', 'localhost:*', '127.0.0.1:*')
            ->fontSrc("'self'", 'fonts.gstatic.com')
            ->connectSrc("'self'", 'ws:', 'wss:', 'localhost:*', '127.0.0.1:*') // Allow WebSocket for HMR
            ->frameAncestors("'none'")
            ->frameSrc("'none'")
            ->objectSrc("'none'")
            ->baseUri("'self'")
            ->formAction("'self'");
    }

    /**
     * Set a directive with validation.
     */
    private function setDirective(string $directive, array $sources): self
    {
        $this->validateDirective($directive);
        $this->validateSources($sources);

        $this->directives[$directive] = $sources;
        return $this;
    }

    /**
     * Add nonce to a specific directive.
     */
    private function addNonceToDirective(string $directive): void
    {
        if (!isset($this->directives[$directive])) {
            $this->directives[$directive] = ["'self'"];
        }

        $nonceValue = $this->nonce->forCsp();
        if (!in_array($nonceValue, $this->directives[$directive], true)) {
            $this->directives[$directive][] = $nonceValue;
        }
    }

    /**
     * Validate directive name.
     */
    private function validateDirective(string $directive): void
    {
        $validDirectives = [
            'default-src', 'script-src', 'style-src', 'img-src', 'font-src',
            'connect-src', 'frame-ancestors', 'frame-src', 'object-src',
            'base-uri', 'form-action', 'media-src', 'manifest-src', 'worker-src'
        ];

        if (!in_array($directive, $validDirectives, true)) {
            throw new InvalidArgumentException("Invalid CSP directive: {$directive}");
        }
    }

    /**
     * Validate source values.
     */
    private function validateSources(array $sources): void
    {
        foreach ($sources as $source) {
            if (!is_string($source) || empty($source)) {
                throw new InvalidArgumentException('CSP sources must be non-empty strings');
            }

            // Basic validation for common CSP source patterns
            if (!preg_match('/^(?:\'[^\']*\'|[a-zA-Z][a-zA-Z0-9+.-]*:|[a-zA-Z0-9.-]+|\*)/', $source)) {
                throw new InvalidArgumentException("Invalid CSP source format: {$source}");
            }
        }
    }
}