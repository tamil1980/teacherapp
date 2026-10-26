<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'subject_id'], 'tch_subj_unique');
        });

        Schema::create('teacher_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'board_id'], 'tch_board_unique');
        });

        Schema::create('teacher_class_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('class_standard_id')->constrained('class_standards')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'class_standard_id'], 'tch_cls_unique');
        });

        Schema::create('teacher_teaching_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('mode', 20)->index();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'mode'], 'tch_mode_unique');
        });

        Schema::create('teacher_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('label');
            $table->string('type', 30)->default('teacher_location')->index();
            $table->string('address_line1')->nullable();
            $table->string('area')->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('travel_charge', 10, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('teacher_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('class_standard_id')->nullable()->constrained('class_standards')->nullOnDelete();
            $table->foreignId('board_id')->nullable()->constrained('boards')->nullOnDelete();
            $table->string('mode', 20)->default('online');
            $table->decimal('hourly_rate', 10, 2);
            $table->unsignedSmallInteger('session_minutes')->default(60);
            $table->decimal('travel_charge', 10, 2)->default(0);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['teacher_profile_id', 'mode', 'is_active']);
        });

        Schema::create('class_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('class_standard_id')->nullable()->constrained('class_standards')->nullOnDelete();
            $table->foreignId('board_id')->nullable()->constrained('boards')->nullOnDelete();
            $table->string('mode', 20)->default('online');
            $table->unsignedSmallInteger('total_sessions')->default(10);
            $table->unsignedSmallInteger('session_minutes')->default(60);
            $table->decimal('price_per_session', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('description')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_packages');
        Schema::dropIfExists('teacher_fees');
        Schema::dropIfExists('teacher_locations');
        Schema::dropIfExists('teacher_teaching_modes');
        Schema::dropIfExists('teacher_class_standards');
        Schema::dropIfExists('teacher_boards');
        Schema::dropIfExists('teacher_subjects');
    }
};
