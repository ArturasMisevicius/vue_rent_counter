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
        $currentLocale = app()->getLocale();
        
        $publishedFaqs = Faq::query()
            ->published()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        
        $faqSource = $publishedFaqs->isNotEmpty()
            ? $publishedFaqs
            : collect(config('landing.faq', []));

        $faqItems = $faqSource->map(
            fn ($faq) => $this->formatFaqItem($faq, $currentLocale)
        );
        
        $canLogin = Route::has('login');
        $canRegister = Route::has('register');
        
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        
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

    private function formatFaqItem(array|Faq $faq, string $locale): array
    {
        $question = $faq instanceof Faq ? $faq->question : ($faq['question'] ?? null);
        $answer = $faq instanceof Faq ? $faq->answer : ($faq['answer'] ?? null);
        $category = $faq instanceof Faq ? $faq->category : ($faq['category'] ?? null);

        return [
            'question' => $this->localizedValue($question, $locale),
            'answer' => $this->localizedValue($answer, $locale),
            'category' => $this->localizedValue($category, $locale),
        ];
    }

    private function localizedValue(mixed $value, string $locale): string
    {
        if (is_array($value)) {
            $localized = $value[$locale] ?? reset($value);

            if (is_string($localized)) {
                return $this->toStringTranslation($localized);
            }

            return '';
        }

        if (is_string($value)) {
            return $this->toStringTranslation($value);
        }

        return '';
    }

    private function toStringTranslation(string $value): string
    {
        $translated = __($value);

        if (is_array($translated)) {
            // Find the first string value inside the translation array
            foreach ($translated as $item) {
                if (is_string($item)) {
                    $translated = $item;
                    break;
                }
            }

            // If no string was found, bail out with empty string to avoid notices
            if (is_array($translated)) {
                return '';
            }
        }

        return (string) $translated;
    }
}
