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
        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->unsignedInteger('order');
            $table->uuid('token')->unique();
            $table->string('status')->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'email']);
            $table->index(['document_id', 'status', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatories');
    }
};
