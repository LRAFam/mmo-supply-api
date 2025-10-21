<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->morphs('product'); // product_id, product_type (automatically creates index)
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2); // Price at time of adding to cart
            $table->text('buyer_notes')->nullable(); // Per-item notes for this seller
            $table->timestamps();

            // Index for grouping by seller
            $table->index(['cart_id', 'seller_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
