<?php

return [
    'distribution_method' => [
        'equal' => 'Equal Distribution',
        'area' => 'Area-Based Distribution',
        'by_consumption' => 'Consumption-Based Distribution',
        'custom_formula' => 'Custom Formula Distribution',
        'equal_description' => 'Distribute costs equally among all properties',
        'area_description' => 'Distribute costs proportionally based on property area',
        'by_consumption_description' => 'Distribute costs based on actual consumption ratios',
        'custom_formula_description' => 'Use custom mathematical formula for distribution',
    ],

    'pricing_model' => [
        'fixed_monthly' => 'Fixed Monthly Rate',
        'consumption_based' => 'Consumption-Based Pricing',
        'tiered_rates' => 'Tiered Rate Structure',
        'hybrid' => 'Hybrid Pricing Model',
        'custom_formula' => 'Custom Formula Pricing',
        'flat' => 'Flat Rate',
        'time_of_use' => 'Time-of-Use Pricing',
        'fixed_monthly_description' => 'Fixed monthly fee regardless of consumption',
        'consumption_based_description' => 'Pricing based on actual consumption amount',
        'tiered_rates_description' => 'Different rates for different consumption levels',
        'hybrid_description' => 'Combination of fixed fee and consumption-based pricing',
        'custom_formula_description' => 'Custom mathematical formula for pricing calculation',
        'flat_description' => 'Single flat rate for all consumption',
        'time_of_use_description' => 'Different rates for different times of day',
    ],

    'input_method' => [
        'manual' => 'Manual Entry',
        'photo_ocr' => 'Photo with OCR',
        'csv_import' => 'CSV Import',
        'api_integration' => 'API Integration',
        'estimated' => 'Estimated Reading',
        'manual_description' => 'Manually entered by user',
        'photo_ocr_description' => 'Extracted from meter photo using OCR',
        'csv_import_description' => 'Imported from CSV file',
        'api_integration_description' => 'Received via API integration',
        'estimated_description' => 'Estimated based on historical data',
    ],

    'validation_status' => [
        'pending' => 'Pending Validation',
        'validated' => 'Validated',
        'rejected' => 'Rejected',
        'requires_review' => 'Requires Review',
        'pending_description' => 'Waiting for validation',
        'validated_description' => 'Approved and ready for billing',
        'rejected_description' => 'Rejected due to errors or inconsistencies',
        'requires_review_description' => 'Needs manual review before approval',
    ],

    'area_type' => [
        'total_area' => 'Total Area',
        'heated_area' => 'Heated Area',
        'commercial_area' => 'Commercial Area',
    ],
    
    'gyvatukas_calculation_type' => [
        'summer' => 'Summer Calculation',
        'winter' => 'Winter Calculation',
    ],
];