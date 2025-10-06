<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix any JSON fields that were accidentally stored as strings
     * This ensures all array-casted fields are properly formatted
     */
    public function up(): void
    {
        $this->fixJsonFields('items', ['images', 'tags', 'variants']);
        $this->fixJsonFields('currencies', ['images', 'tags', 'bulk_pricing']);
        $this->fixJsonFields('accounts', ['images', 'tags', 'account_stats', 'included_items']);
        $this->fixJsonFields('services', ['images', 'tags', 'packages', 'addons', 'schedule']);
    }

    /**
     * Fix JSON string fields in a table
     */
    private function fixJsonFields(string $table, array $fields): void
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $records = DB::table($table)->get();

        foreach ($records as $record) {
            $updates = [];

            foreach ($fields as $field) {
                if (!property_exists($record, $field)) {
                    continue;
                }

                $value = $record->{$field};

                // Skip if null or already valid JSON/array
                if (is_null($value)) {
                    continue;
                }

                // If it's a string, try to decode it
                if (is_string($value)) {
                    // Check if it's already valid JSON
                    $decoded = json_decode($value, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        // It's valid JSON string, re-encode to ensure consistency
                        $updates[$field] = json_encode($decoded);
                    } else {
                        // Not valid JSON, treat as empty array
                        $updates[$field] = json_encode([]);
                    }
                }
            }

            // Update record if any fields need fixing
            if (!empty($updates)) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update($updates);
            }
        }

        $count = count($records);
        echo "Fixed JSON fields in {$table}: {$count} records processed\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - this is a data fix migration
    }
};
