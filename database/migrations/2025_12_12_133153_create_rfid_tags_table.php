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
        Schema::create('rfid_tags', function (Blueprint $table) {
            $table->id();
            $table->string('epc_code')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('encoded_at');
            $table->string('encoded_by', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('epc_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_tags');
    }
};
