<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Language;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

final class WelcomeController extends Controller
{
    public function __invoke(): View
    {
        $brand = config('landing.brand', []);
        $features = config('landing.features', []);
        
        $publishedFaqs = Faq::query()
            ->published()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        
        $faqItems = $publishedFaqs->isNotEmpty()
            ? $publishedFaqs->map(fn ($faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
            ])
            : collect(config('landing.faq', []));
        
        $canLogin = Route::has('login');
        $canRegister = Route::has('register');
        
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
        $currentLocale = app()->getLocale();
        $canSwitchLocale = Route::has('locale.set');
        
        return view('welcome', compact(
            'brand',
            'features',
            'faqItems',
            'canLogin',
            'canRegister',
            'languages',
            'currentLocale',
            'canSwitchLocale'
        ));
    }
}
