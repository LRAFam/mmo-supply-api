<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// S3 Diagnostic endpoint
Route::get('/debug/s3', function () {
    try {
        $diagnostics = [
            'environment' => app()->environment(),
            'filesystem_disk' => config('filesystems.default'),
            's3_config' => [
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
                'key_set' => !empty(config('filesystems.disks.s3.key')),
                'secret_set' => !empty(config('filesystems.disks.s3.secret')),
                'url' => config('filesystems.disks.s3.url'),
            ],
            'env_check' => [
                'FILESYSTEM_DISK' => env('FILESYSTEM_DISK'),
                'AWS_BUCKET' => env('AWS_BUCKET'),
                'AWS_REGION' => env('AWS_DEFAULT_REGION'),
                'AWS_KEY_SET' => !empty(env('AWS_ACCESS_KEY_ID')),
                'AWS_URL' => env('AWS_URL'),
            ],
        ];

        // Test S3 connection
        try {
            $testFile = 'diagnostic-test-' . time() . '.txt';
            Storage::disk('s3')->put($testFile, 'Production S3 test at ' . now());

            if (Storage::disk('s3')->exists($testFile)) {
                $diagnostics['s3_test'] = 'SUCCESS';
                $diagnostics['test_url'] = Storage::disk('s3')->url($testFile);
                Storage::disk('s3')->delete($testFile);
            } else {
                $diagnostics['s3_test'] = 'FAILED - File does not exist after upload';
            }
        } catch (\Exception $e) {
            $diagnostics['s3_test'] = 'ERROR';
            $diagnostics['s3_error'] = $e->getMessage();
        }

        // Check games directory
        try {
            $files = Storage::disk('s3')->allFiles('games');
            $diagnostics['games_directory'] = [
                'accessible' => true,
                'file_count' => count($files),
                'files' => $files,
            ];
        } catch (\Exception $e) {
            $diagnostics['games_directory'] = [
                'accessible' => false,
                'error' => $e->getMessage(),
            ];
        }

        return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500, [], JSON_PRETTY_PRINT);
    }
});
