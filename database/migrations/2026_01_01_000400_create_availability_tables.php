<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Availability & slot-locking tables.
     *
     * `slot_reservations` is the authoritative concurrency guard: the unique
     * index on (teacher_profile_id, slot_start_at) makes it impossible for two
     * parents to reserve the same teacher/time, even if they click "Book" at
     * the very same second.
     */
    public function up(): void
    {
        Schema::create('teacher_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('mode', 20)->default('both');
            $table->unsignedSmallInteger('session_minutes')->default(60);
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['teacher_profile_id', 'weekday', 'is_active'], 'tch_avail_rule_idx');
        });

        Schema::create('teacher_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->date('slot_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('available')->index();
            $table->string('mode', 20)->default('both');
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'slot_date', 'start_time'], 'teacher_time_slots_unique');
        });

        Schema::create('slot_holds', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('parent_profile_id')->constrained('parent_profiles')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->string('mode', 20)->default('online');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('expires_at')->index();
            // bookings table is created in a later migration, so the FK is added there.
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['teacher_profile_id', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_holds');
        Schema::dropIfExists('teacher_time_slots');
        Schema::dropIfExists('teacher_availability_rules');
    }
};
