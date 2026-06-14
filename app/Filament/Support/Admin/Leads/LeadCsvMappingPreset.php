<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Leads;

class LeadCsvMappingPreset
{
    /**
     * @return array<string, string>
     */
    public static function aruodasDefault(): array
    {
        return [
            'external_id' => 'ID',
            'source_url' => 'URL',
            'listing_title' => 'Ad title',
            'property_address' => 'Address',
            'city' => 'City',
            'district' => 'District',
            'property_type' => 'Property type',
            'area' => 'Area',
            'rooms' => 'Rooms',
            'floor' => 'Floor',
            'price' => 'Price',
            'currency' => 'Currency',
            'owner_name' => 'Owner',
            'owner_phone' => 'Phone',
            'owner_email' => 'Email',
            'contact_raw' => 'Contact',
            'description' => 'Description',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function aliases(): array
    {
        return [
            'external_id' => ['id', 'external id', 'ad id', 'listing id', 'objekto id'],
            'source_url' => ['url', 'link', 'source url', 'ad url', 'skelbimo nuoroda'],
            'listing_title' => ['title', 'ad title', 'listing title', 'pavadinimas'],
            'property_address' => ['address', 'property address', 'adresas'],
            'city' => ['city', 'miestas'],
            'district' => ['district', 'rajonas'],
            'property_type' => ['property type', 'type', 'objekto tipas'],
            'area' => ['area', 'plotas'],
            'rooms' => ['rooms', 'kambariai'],
            'floor' => ['floor', 'aukstas', 'aukštas'],
            'price' => ['price', 'kaina'],
            'currency' => ['currency', 'valiuta'],
            'owner_name' => ['owner', 'owner name', 'name', 'vardas'],
            'owner_phone' => ['phone', 'telephone', 'owner phone', 'telefonas'],
            'owner_email' => ['email', 'owner email', 'el. pastas', 'el. paštas'],
            'contact_raw' => ['contact', 'contacts', 'kontaktai'],
            'description' => ['description', 'body', 'aprasymas', 'aprašymas'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function systemFields(): array
    {
        return array_keys(self::aruodasDefault());
    }
}
