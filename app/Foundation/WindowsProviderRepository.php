<?php

declare(strict_types=1);

namespace App\Foundation;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;

/**
 * Windows-compatible ProviderRepository that bypasses the is_writable() bug.
 * 
 * On Windows, PHP's is_writable() function can return false even when the directory
 * is actually writable. This class fixes that by testing actual write capability
 * instead of relying on the buggy is_writable() function.
 */
class WindowsProviderRepository extends ProviderRepository
{
    /**
     * Create a new service repository instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct($app, Filesystem $files, $manifestPath)
    {
        parent::__construct($app, $files, $manifestPath);
    }

    /**
     * Write the service manifest file to disk.
     *
     * @param  array  $manifest
     * @return array
     *
     * @throws \Exception
     */
    public function writeManifest($manifest)
    {
        $dirname = dirname($this->manifestPath);
        
        // Instead of using is_writable(), test actual write capability
        if (!$this->canActuallyWrite($dirname)) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $this->files->replace(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );

        return array_merge(['when' => []], $manifest);
    }
    
    /**
     * Test if we can actually write to the directory by attempting to create a test file.
     * This bypasses the Windows is_writable() bug.
     */
    private function canActuallyWrite(string $directory): bool
    {
        // First check if directory exists
        if (!is_dir($directory)) {
            return false;
        }
        
        // Try to write a test file
        $testFile = $directory . DIRECTORY_SEPARATOR . '.write_test_' . uniqid();
        
        try {
            $result = file_put_contents($testFile, 'test');
            if ($result !== false) {
                // Successfully wrote, now clean up
                @unlink($testFile);
                return true;
            }
        } catch (Exception $e) {
            // Write failed
        }
        
        // Clean up test file if it exists
        if (file_exists($testFile)) {
            @unlink($testFile);
        }
        
        return false;
    }
}