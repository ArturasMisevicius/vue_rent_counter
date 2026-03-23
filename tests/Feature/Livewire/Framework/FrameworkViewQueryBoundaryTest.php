<?php

declare(strict_types=1);

it('keeps framework livewire view components free from direct database queries', function () {
    $frameworkComponentFiles = [
        base_path('resources/views/components/framework/⚡preview-modal/preview-modal.php'),
    ];

    foreach ($frameworkComponentFiles as $filePath) {
        $contents = file_get_contents($filePath);

        expect($contents)
            ->toBeString()
            ->not->toContain('::query(')
            ->not->toContain('DB::');
    }
});
