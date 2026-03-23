<?php

declare(strict_types=1);

it('keeps admin report builders free from raw sql expressions', function () {
    $reportBuilderFiles = [
        base_path('app/Filament/Support/Admin/Reports/AbstractReportBuilder.php'),
        base_path('app/Filament/Support/Admin/Reports/ConsumptionReportBuilder.php'),
        base_path('app/Filament/Support/Admin/Reports/MeterComplianceReportBuilder.php'),
        base_path('app/Filament/Support/Admin/Reports/OutstandingBalancesReportBuilder.php'),
        base_path('app/Filament/Support/Admin/Reports/RevenueReportBuilder.php'),
    ];

    foreach ($reportBuilderFiles as $filePath) {
        $contents = file_get_contents($filePath);

        expect($contents)
            ->toBeString()
            ->not->toContain('DB::raw(')
            ->not->toContain('whereRaw(')
            ->not->toContain('selectRaw(')
            ->not->toContain('DB::select(')
            ->not->toContain('DB::statement(');
    }
});
