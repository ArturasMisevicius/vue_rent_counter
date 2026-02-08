# Landing Page Translation Keys Reference

## Overview

Complete reference for all translation keys used in the landing page system. This document serves as a technical reference for developers working with landing page translations.

## Translation Key Structure

### Namespace: `landing.*`

All landing page translations use the `landing` namespace to avoid conflicts with other translation groups.

## Complete Key Reference

### CTA Bar Section (`landing.cta_bar.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.cta_bar.eyebrow` | "Utilities Management" | "Komunalinių paslaugų valdymas" | Small text above main CTA |
| `landing.cta_bar.title` | "Streamline Your Property Operations" | "Supaprastinkite savo nekilnojamojo turto veiklą" | Main CTA heading |

### Hero Section (`landing.hero.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.hero.badge` | "Vilnius Utilities Platform" | "Vilniaus komunalinių paslaugų platforma" | Platform identifier badge |
| `landing.hero.title` | "Modern Utilities Management for Lithuanian Properties" | "Šiuolaikiškas komunalinių paslaugų valdymas Lietuvos nekilnojamajam turtui" | Main hero heading |
| `landing.hero.tagline` | "Manage properties, meters, and invoices with confidence" | "Valdykite nekilnojamąjį turtą, skaitiklius ir sąskaitas faktūras su pasitikėjimu" | Hero subheading |

### Dashboard Preview (`landing.dashboard.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.dashboard.draft_invoices` | "Draft Invoices" | "Sąskaitų faktūrų projektai" | Dashboard metric label |
| `landing.dashboard.draft_invoices_hint` | "Invoices pending finalization" | "Sąskaitos faktūros, laukiančios patvirtinimo" | Dashboard metric description |
| `landing.dashboard.electricity` | "Electricity" | "Elektra" | Utility type label |
| `landing.dashboard.electricity_status` | "Electricity System Status" | "Elektros sistemos būsena" | System status label |
| `landing.dashboard.healthy` | "Healthy" | "Sveika" | System health indicator |
| `landing.dashboard.heating` | "Heating" | "Šildymas" | Utility type label |
| `landing.dashboard.heating_status` | "Heating System Status" | "Šildymo sistemos būsena" | System status label |
| `landing.dashboard.live_overview` | "Live System Overview" | "Tiesioginė sistemos apžvalga" | Dashboard section title |
| `landing.dashboard.meters_validated` | "Meters Validated" | "Skaitikliai patvirtinti" | Dashboard metric label |
| `landing.dashboard.meters_validated_hint` | "Meters with validated readings" | "Skaitikliai su patvirtintais rodmenimis" | Dashboard metric description |
| `landing.dashboard.portfolio_health` | "Portfolio Health" | "Portfelio sveikata" | Dashboard section title |
| `landing.dashboard.recent_readings` | "Recent Meter Readings" | "Naujausi skaitiklių rodmenys" | Dashboard section title |
| `landing.dashboard.trusted` | "Trusted" | "Patikimas" | Trust indicator |
| `landing.dashboard.water` | "Water" | "Vanduo" | Utility type label |
| `landing.dashboard.water_status` | "Water System Status" | "Vandens sistemos būsena" | System status label |

### Features Section (`landing.features.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.features_title` | "Comprehensive Utilities Management" | "Išsamus komunalinių paslaugų valdymas" | Features section heading |
| `landing.features_subtitle` | "Everything you need to manage utilities efficiently" | "Viskas, ko reikia efektyviam komunalinių paslaugų valdymui" | Features section subheading |

#### Individual Features (`landing.features.{feature}.*`)

**Unified Metering (`landing.features.unified_metering.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `title` | "Unified Meter Management" | "Suvienijtas skaitiklių valdymas" |
| `description` | "Manage all electricity, water, and heating meters in one place with automated reading validation." | "Valdykite visus elektros, vandens ir šildymo skaitiklius vienoje vietoje su automatizuotu rodmenų patvirtinimu." |

**Accurate Invoicing (`landing.features.accurate_invoicing.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `title` | "Accurate Invoice Calculations" | "Tikslūs sąskaitų faktūrų skaičiavimai" |
| `description` | "Automatically generate invoices based on validated meter readings with tariff snapshots." | "Automatiškai generuokite sąskaitas faktūras pagal patvirtintus skaitiklių rodmenis su tarifikų momentinėmis nuotraukomis." |

**Role Access (`landing.features.role_access.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `title` | "Role-Based Access Control" | "Vaidmenų prieigos kontrolė" |
| `description` | "Secure multi-tenant access management for superadmins, managers, and tenants." | "Saugus daugiašalis prieigos valdymas superadministratoriams, valdytojams ir nuomotojams." |

**Reporting (`landing.features.reporting.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `title` | "Comprehensive Reporting" | "Išsamūs ataskaitos" |
| `description` | "Generate detailed reports on consumption, revenue, and portfolio performance." | "Generuokite išsamias ataskaitas apie suvartojimą, pajamas ir portfelio našumą." |

**Performance (`landing.features.performance.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `title` | "High Performance" | "Aukštas našumas" |
| `description` | "Optimized architecture with caching mechanisms and N+1 query prevention." | "Optimizuota architektūra su talpyklos mechanizmais ir N+1 užklausų prevencija." |

**Tenant Clarity (`landing.features.tenant_clarity.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `title` | "Tenant Transparency" | "Nuomotojų skaidrumas" |
| `description` | "Tenants can view their meter readings, invoices, and download PDF statements." | "Nuomotojai gali peržiūrėti savo skaitiklių rodmenis, sąskaitas faktūras ir atsisiųsti PDF failus." |

### FAQ Section (`landing.faq.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.faq_intro` | "Frequently asked questions about our utilities management platform" | "Dažnai užduodami klausimai apie mūsų komunalinių paslaugų valdymo platformą" | FAQ section introduction |

#### FAQ Section Headers (`landing.faq_section.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.faq_section.eyebrow` | "Support" | "Pagalba" | FAQ section eyebrow text |
| `landing.faq_section.title` | "Frequently Asked Questions" | "Dažnai užduodami klausimai" | FAQ section heading |
| `landing.faq_section.category_prefix` | "Category:" | "Kategorija:" | FAQ category prefix |

#### Individual FAQ Items (`landing.faq.{item}.*`)

**Validation (`landing.faq.validation.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `question` | "How does meter reading validation work?" | "Kaip veikia skaitiklių rodmenų patvirtinimas?" |
| `answer` | "All meter readings are validated using monotonicity and temporal rules. The system automatically detects anomalies and requires manager approval." | "Visi skaitiklių rodmenys yra patvirtinami monotoniškumo ir laiko taisyklėmis. Sistema automatiškai aptinka anomalijas ir reikalauja vadybininko patvirtinimo." |

**Tenants (`landing.faq.tenants.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `question` | "What can tenants see?" | "Ką gali matyti nuomotojai?" |
| `answer` | "Tenants can view their property information, meter readings, invoice history, and download PDF statements. They cannot see other tenants' data." | "Nuomotojai gali peržiūrėti savo nekilnojamojo turto informaciją, skaitiklių rodmenis, sąskaitų faktūrų istoriją ir atsisiųsti PDF failus. Jie negali matyti kitų nuomotojų duomenų." |

**Invoices (`landing.faq.invoices.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `question` | "How does invoice generation work?" | "Kaip veikia sąskaitų faktūrų generavimas?" |
| `answer` | "Invoices are generated automatically based on validated meter readings. Tariff snapshots ensure invoice calculations remain accurate even when tariffs change." | "Sąskaitos faktūros generuojamos automatiškai pagal patvirtintus skaitiklių rodmenis. Tarifikų momentinės nuotraukos užtikrina, kad sąskaitų faktūrų skaičiavimai išlieka tikslūs net keičiantis tarifams." |

**Security (`landing.faq.security.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `question` | "How is data security ensured?" | "Kaip užtikrinamas duomenų saugumas?" |
| `answer` | "The platform uses multi-tenant isolation, role-based access control, and comprehensive auditing. All data is encrypted and regularly backed up." | "Platforma naudoja daugiašalę nuomotojų izoliaciją, vaidmenų prieigos kontrolę ir išsamų auditą. Visi duomenys yra šifruojami ir reguliariai kuriamos atsarginės kopijos." |

**Support (`landing.faq.support.*`)**
| Key | English | Lithuanian |
|-----|---------|------------|
| `question` | "What support is available?" | "Kokia pagalba teikiama?" |
| `answer` | "We provide comprehensive documentation, training, and technical support. The platform supports Lithuanian and English languages with localized interfaces." | "Teikiame išsamią dokumentaciją, mokymus ir techninę pagalbą. Platforma palaiko lietuvių ir anglų kalbas su lokalizuotomis sąsajomis." |

### Performance Metrics (`landing.metrics.*`, `landing.metric_values.*`)

#### Metric Labels (`landing.metrics.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.metrics.cache` | "Cache Performance" | "Talpyklos našumas" | Performance metric label |
| `landing.metrics.isolation` | "Tenant Isolation" | "Nuomotojų izoliacija" | Security metric label |
| `landing.metrics.readings` | "Meter Readings" | "Skaitiklių rodmenys" | Data metric label |

#### Metric Values (`landing.metric_values.*`)

| Key | English | Lithuanian | Usage |
|-----|---------|------------|-------|
| `landing.metric_values.five_minutes` | "< 5 minutes" | "< 5 minutės" | Performance time value |
| `landing.metric_values.full` | "100%" | "100%" | Completeness percentage |
| `landing.metric_values.zero` | "0" | "0" | Zero value indicator |

## Usage Examples

### Basic Translation Usage

```blade
{{-- Simple translation --}}
<h1>{{ __('landing.hero.title') }}</h1>

{{-- Translation with fallback --}}
<p>{{ __('landing.hero.tagline', [], 'en') }}</p>
```

### Configuration File Usage

```php
// config/landing.php
'features' => [
    [
        'title' => 'landing.features.unified_metering.title',
        'description' => 'landing.features.unified_metering.description',
        'icon' => 'meter',
    ],
],
```

### Programmatic Access

```php
// Get translation in current locale
$title = __('landing.hero.title');

// Get translation in specific locale
app()->setLocale('lt');
$lithuanianTitle = __('landing.hero.title');

// Check if translation exists
if (Lang::has('landing.hero.title')) {
    // Translation exists
}
```

## Validation Rules

### Key Naming Conventions

1. **Namespace**: All keys must start with `landing.`
2. **Structure**: Use dot notation for nesting (e.g., `landing.section.subsection.key`)
3. **Case**: Use snake_case for key names
4. **Descriptive**: Keys should be descriptive of their content/purpose

### Content Guidelines

1. **Length**: Consider UI constraints when writing translations
2. **Consistency**: Maintain consistent terminology across all translations
3. **Accuracy**: Ensure technical terms are accurately translated
4. **Context**: Provide sufficient context for translators

## Testing

### Automated Tests

```php
// Test all keys exist in both locales
it('has all landing translation keys in both locales', function () {
    $englishKeys = collect(Arr::dot(Lang::get('landing', [], 'en')));
    $lithuanianKeys = collect(Arr::dot(Lang::get('landing', [], 'lt')));
    
    expect($englishKeys->keys())->toEqual($lithuanianKeys->keys());
});
```

### Manual Testing Checklist

- [ ] All keys display translated content (not key names)
- [ ] Translations fit within UI constraints
- [ ] No broken layouts due to text length differences
- [ ] Consistent terminology across all sections
- [ ] Proper Lithuanian grammar and spelling

## Maintenance

### Adding New Keys

1. Add to English file first (`lang/en/landing.php`)
2. Add corresponding Lithuanian translation (`lang/lt/landing.php`)
3. Update this reference document
4. Add to automated tests if needed
5. Clear cache: `php artisan optimize:clear`

### Updating Existing Keys

1. Update both English and Lithuanian files
2. Test in both locales
3. Update documentation if key structure changes
4. Clear cache and test deployment

---

**Last Updated**: 2024-12-24  
**Version**: 1.0.0  
**Maintainer**: Development Team