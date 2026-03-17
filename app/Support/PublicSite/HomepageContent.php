<?php

namespace App\Support\PublicSite;

class HomepageContent
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'brand' => [
                'name' => config('app.name', 'Tenanto'),
                'tagline' => __('landing.brand.tagline'),
                'kicker' => __('landing.brand.kicker'),
            ],
            'hero' => [
                'eyebrow' => __('landing.hero.eyebrow'),
                'title' => __('landing.hero.title'),
                'description' => __('landing.hero.description'),
                'chips' => trans('landing.hero.chips'),
            ],
            'roles' => [
                'heading' => __('landing.roles.heading'),
                'description' => __('landing.roles.description'),
                'items' => trans('landing.roles.items'),
            ],
            'tester' => [
                'heading' => __('landing.tester.heading'),
                'description' => __('landing.tester.description'),
                'items' => trans('landing.tester.items'),
            ],
            'roadmap' => [
                'heading' => __('landing.roadmap.heading'),
                'lead' => __('landing.roadmap.lead'),
                'description' => __('landing.roadmap.description'),
                'status' => __('landing.roadmap.status'),
                'items' => trans('landing.roadmap.items'),
            ],
            'cta' => [
                'heading' => __('landing.cta.heading'),
                'description' => __('landing.cta.description'),
                'note' => __('landing.cta.note'),
                'login' => __('landing.cta.login'),
                'register' => __('landing.cta.register'),
            ],
        ];
    }
}
