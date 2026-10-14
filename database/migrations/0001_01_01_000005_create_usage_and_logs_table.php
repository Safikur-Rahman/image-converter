<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action')->index(); // svg_conversion, background_removal, download
            $table->integer('quantity')->default(1);
            $table->json('metadata')->nullable();
            $table->string('period')->index(); // YYYY-MM
            $table->timestamps();

            $table->index(['user_id', 'period']);
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversion_file_id')->nullable()->constrained('conversion_files')->cascadeOnDelete();
            $table->string('type')->nullable(); // svg, original, background_removed
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('level')->default('info')->index();
            $table->string('source')->nullable()->index(); // conversion, storage, background_removal, system
            $table->string('message');
            $table->json('context')->nullable();
            $table->string('conversion_uuid')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('usage_records');
    }
};