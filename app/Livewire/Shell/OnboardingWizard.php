<?php

namespace App\Livewire\Shell;

use App\Livewire\Concerns\AppliesShellLocale;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class OnboardingWizard extends Component
{
    use AppliesShellLocale;

    public bool $isEligible = false;

    public bool $isOpen = false;

    public int $stepIndex = 0;

    public function mount(): void
    {
        $this->applyShellLocale();

        $this->isEligible = $this->eligibleUser() instanceof User;
        $this->isOpen = $this->isEligible
            && ! (bool) session()->get('onboarding_tour_dismissed', false)
            && $this->eligibleUser()?->onboarding_tour_completed_at === null;
    }

    #[On('shell-locale-updated')]
    public function refresh(): void
    {
        $this->applyShellLocale();
    }

    public function open(): void
    {
        if (! $this->isEligible) {
            return;
        }

        session()->forget('onboarding_tour_dismissed');
        $this->stepIndex = 0;
        $this->isOpen = true;
    }

    public function dismiss(): void
    {
        session()->put('onboarding_tour_dismissed', true);
        $this->isOpen = false;
    }

    public function previous(): void
    {
        $this->stepIndex = max(0, $this->stepIndex - 1);
    }

    public function goTo(int $index): void
    {
        $this->stepIndex = $this->clampStepIndex($index, $this->steps());
    }

    public function next(): void
    {
        if ($this->stepIndex >= count($this->steps()) - 1) {
            $this->finish();

            return;
        }

        $this->stepIndex++;
    }

    public function finish(): void
    {
        $user = $this->eligibleUser();

        if (! $user instanceof User) {
            return;
        }

        $user->forceFill([
            'onboarding_tour_completed_at' => now(),
        ])->save();

        session()->forget('onboarding_tour_dismissed');

        $this->isOpen = false;
        $this->stepIndex = 0;
    }

    public function render(): View
    {
        $steps = $this->steps();
        $this->stepIndex = $this->clampStepIndex($this->stepIndex, $steps);

        return view('livewire.shell.onboarding-wizard', [
            'currentStep' => $steps[$this->stepIndex] ?? $steps[0],
            'isFirstStep' => $this->stepIndex === 0,
            'isLastStep' => $this->stepIndex === count($steps) - 1,
            'roleLabel' => $this->eligibleUser()?->role?->label(),
            'steps' => $steps,
            'totalSteps' => count($steps),
        ]);
    }

    private function eligibleUser(): ?User
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->isSuperadmin()) {
            return null;
        }

        return $user;
    }

    /**
     * @return list<array{key: string, icon: string, title: string, body: string, detail: string}>
     */
    private function steps(): array
    {
        $role = $this->roleKey();
        $stepKeys = ['workspace', 'navigation', 'workflows', 'activity', 'profile'];

        return collect($stepKeys)
            ->map(fn (string $step): array => [
                'key' => $step,
                'icon' => $this->iconFor($step),
                'title' => __("onboarding.tour.roles.{$role}.steps.{$step}.title"),
                'body' => __("onboarding.tour.roles.{$role}.steps.{$step}.body"),
                'detail' => __("onboarding.tour.roles.{$role}.steps.{$step}.detail"),
            ])
            ->all();
    }

    private function roleKey(): string
    {
        $user = $this->eligibleUser();

        return match (true) {
            $user?->isTenant() => 'tenant',
            $user?->isManager() => 'manager',
            default => 'admin',
        };
    }

    private function iconFor(string $step): string
    {
        return match ($step) {
            'workspace' => 'heroicon-m-squares-2x2',
            'navigation' => 'heroicon-m-bars-3-bottom-left',
            'workflows' => 'heroicon-m-bolt',
            'activity' => 'heroicon-m-chart-bar-square',
            default => 'heroicon-m-user-circle',
        };
    }

    /**
     * @param  list<array{key: string, icon: string, title: string, body: string, detail: string}>  $steps
     */
    private function clampStepIndex(int $index, array $steps): int
    {
        return min(max(0, $index), count($steps) - 1);
    }
}
