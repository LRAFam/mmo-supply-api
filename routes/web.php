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

        // Check recent Laravel logs for S3 errors
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $logLines = file($logFile);
                $recentLogs = array_slice($logLines, -50); // Last 50 lines
                $s3Errors = array_filter($recentLogs, function($line) {
                    return stripos($line, 's3') !== false ||
                           stripos($line, 'upload') !== false ||
                           stripos($line, 'storage') !== false;
                });
                $diagnostics['recent_logs'] = array_values($s3Errors);
            }
        } catch (\Exception $e) {
            $diagnostics['log_error'] = $e->getMessage();
        }

        return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500, [], JSON_PRETTY_PRINT);
    }
});

// Test Filament upload simulation
Route::get('/debug/filament-upload', function () {
    try {
        // Simulate what Filament does
        $result = [];

        // Test 1: Direct S3 upload
        $testContent = 'test-image-data';
        $filename = 'test-' . uniqid() . '.png';

        $result['step_1_upload'] = Storage::disk('s3')->put('games/logos/' . $filename, $testContent);
        $result['step_2_exists'] = Storage::disk('s3')->exists('games/logos/' . $filename);

        if ($result['step_2_exists']) {
            $result['step_3_url'] = Storage::disk('s3')->url('games/logos/' . $filename);

            // Simulate saving to database
            $game = new \App\Models\Game();
            $game->title = 'Test Game';
            $game->slug = 'test-game-' . time();
            $game->logo = 'games/logos/' . $filename;
            $game->icon = 'games/icons/test.png';
            $game->provider_count = 0;

            $result['step_4_save'] = $game->save();
            $result['step_5_logo_url'] = $game->logo_url;

            // Clean up
            $game->delete();
            Storage::disk('s3')->delete('games/logos/' . $filename);
            $result['cleanup'] = true;
        }

        $result['all_files_in_games'] = Storage::disk('s3')->allFiles('games');

        return response()->json($result, 200, [], JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ], 500, [], JSON_PRETTY_PRINT);
    }
});
