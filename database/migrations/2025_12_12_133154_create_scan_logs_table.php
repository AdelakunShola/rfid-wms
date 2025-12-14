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
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('epc_code');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('operation_type', ['receiving', 'picking', 'shipping', 'count']);
            $table->integer('quantity')->default(1);
            $table->string('device_id', 100)->nullable();
            $table->string('scanned_by', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('operation_type');
            $table->index('created_at');
            $table->index(['operation_type', 'created_at', 'product_id'], 'idx_scan_logs_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
