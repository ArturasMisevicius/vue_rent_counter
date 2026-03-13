<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecurityViolation>
 */
final class SecurityViolationFactory extends Factory
{
    protected $model = SecurityViolation::class;

    public function definition(): array
    {
        $directives = ['script-src', 'style-src', 'img-src', 'font-src', 'connect-src', 'frame-src'];
        $severities = ['low', 'medium', 'high', 'critical'];
        $classifications = ['unknown', 'suspicious', 'malicious', 'false_positive'];

        return [
            'tenant_id' => User::factory()->manager(1),
            'violation_type' => 'csp',
            'policy_directive' => $this->faker->randomElement($directives),
            'blocked_uri' => $this->faker->url(),
            'document_uri' => $this->faker->url(),
            'referrer' => $this->faker->optional()->url(),
            'user_agent' => hash('sha256', $this->faker->userAgent().config('app.key')),
            'source_file' => $this->faker->optional()->url(),
            'line_number' => $this->faker->optional()->numberBetween(1, 1000),
            'column_number' => $this->faker->optional()->numberBetween(1, 100),
            'severity_level' => $this->faker->randomElement($severities),
            'threat_classification' => $this->faker->randomElement($classifications),
            'resolved_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'resolution_notes' => $this->faker->optional()->sentence(),
            'metadata' => [
                'ip_hash' => hash('sha256', $this->faker->ipv4().config('app.key')),
                'request_id' => $this->faker->uuid(),
                'processed_at' => now()->toISOString(),
                'mcp_tracked' => $this->faker->boolean(),
            ],
        ];
    }

    public function malicious(): static
    {
        return $this->state(fn (array $attributes) => [
            'blocked_uri' => $this->faker->randomElement([
                'javascript:alert("xss")',
                'data:text/html,<script>alert("xss")</script>',
                'eval(atob("YWxlcnQoJ1hTUycpOw=="))',
            ]),
            'severity_level' => 'critical',
            'threat_classification' => 'malicious',
        ]);
    }

    public function suspicious(): static
    {
        return $this->state(fn (array $attributes) => [
            'blocked_uri' => 'http://suspicious.example.com/script.js',
            'policy_directive' => 'script-src',
            'severity_level' => 'high',
            'threat_classification' => 'suspicious',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'resolution_notes' => 'Resolved: '.$this->faker->sentence(),
        ]);
    }

    public function scriptSrc(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_directive' => 'script-src',
            'severity_level' => 'high',
        ]);
    }

    public function styleSrc(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_directive' => 'style-src',
            'severity_level' => 'medium',
        ]);
    }

    public function withTenant(User $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
