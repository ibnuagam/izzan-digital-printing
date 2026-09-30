<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', fn (Blueprint $t) => $t->unsignedInteger('minimum_quantity')->default(1));
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('service_id')->constrained()->restrictOnDelete();
            $t->string('service_name', 150);
            $t->string('unit', 20);
            $t->unsignedInteger('quantity');
            $t->decimal('width', 10, 3)->nullable();
            $t->decimal('height', 10, 3)->nullable();
            $t->decimal('volume', 18, 4);
            $t->decimal('unit_price', 14, 2)->nullable();
            $t->decimal('estimated_total', 18, 2)->nullable();
            $t->decimal('final_total', 18, 2)->nullable();
            $t->string('design_mode');
            $t->string('design_path')->nullable();
            $t->text('customer_notes')->nullable();
            $t->text('admin_notes')->nullable();
            $t->string('delivery_method');
            $t->text('delivery_address')->nullable();
            $t->string('status')->default('pending');
            $t->timestamp('quoted_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
            $t->index(['status', 'created_at']);
        });
        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('status');
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('orders');
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('minimum_quantity'));
    }
};
