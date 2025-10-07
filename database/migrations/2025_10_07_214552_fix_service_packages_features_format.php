<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Service;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix existing services where package features are stored as string instead of array
        $services = Service::whereNotNull('packages')->get();

        foreach ($services as $service) {
            $packagesUpdated = false;
            $packages = $service->packages;

            if (is_array($packages)) {
                foreach ($packages as &$package) {
                    // If features is a string, convert it to an array
                    if (isset($package['features']) && is_string($package['features'])) {
                        $package['features'] = array_filter(
                            explode("\n", $package['features']),
                            fn($line) => trim($line) !== ''
                        );
                        $packagesUpdated = true;
                    }
                }
            }

            if ($packagesUpdated) {
                $service->packages = $packages;
                $service->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse this migration
    }
};
