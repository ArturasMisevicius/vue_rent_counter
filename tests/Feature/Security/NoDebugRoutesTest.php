<?php

declare(strict_types=1);

test('debug and public diagnostic endpoints are not accessible', function (): void {
    $this->get('/test-debug')->assertNotFound();
    $this->get('/check-logs.php')->assertNotFound();
    $this->get('/debug-auth.php')->assertNotFound();
});
