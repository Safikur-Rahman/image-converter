<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('original_filename');
            $table->string('original_format');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->boolean('remove_background')->default(false);
            $table->string('conversion_type')->default('color'); // color, b_w
            $table->string('quality')->default('medium'); // low, medium, high
            $table->string('path_optimization')->default('balanced'); // simple, balanced, aggressive
            $table->string('disk')->default('local');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('conversion_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index(); // original, background_removed, svg, preview
            $table->string('disk');
            $table->string('path');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->unique(['conversion_id', 'type']);
        });

        Schema::create('background_removals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversion_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('status')->default('pending');
            $table->string('input_path')->nullable();
            $table->string('output_path')->nullable();
            $table->timestamps();
        });

        Schema::create('svg_conversions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversion_id')->constrained()->cascadeOnDelete();
            $table->string('tracing_engine');
            $table->string('status')->default('processing');
            $table->integer('input_width')->nullable();
            $table->integer('input_height')->nullable();
            $table->integer('output_width')->nullable();
            $table->integer('output_height')->nullable();
            $table->unsignedBigInteger('original_svg_size')->nullable();
            $table->unsignedBigInteger('optimized_svg_size')->nullable();
            $table->integer('path_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('svg_conversions');
        Schema::dropIfExists('background_removals');
        Schema::dropIfExists('conversion_files');
        Schema::dropIfExists('conversions');
    }
};