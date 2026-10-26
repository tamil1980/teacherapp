<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 32)->unique();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('parent_profile_id')->constrained('parent_profiles')->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('board_id')->nullable()->constrained('boards')->nullOnDelete();
            $table->foreignId('class_standard_id')->nullable()->constrained('class_standards')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('teacher_location_id')->nullable()->constrained('teacher_locations')->nullOnDelete();
            $table->string('mode', 20)->index();
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('travel_charge', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_code', 40)->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->string('pricing_snapshot', 60)->nullable();
            $table->text('notes')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->string('cancelled_by', 20)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('attendance_status', 20)->nullable();
            $table->string('completion_confirmed_by', 20)->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->unsignedInteger('recurrence_count')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['teacher_profile_id', 'start_at', 'status']);
            $table->index(['parent_profile_id', 'start_at']);
            $table->index(['student_profile_id', 'start_at']);
        });

        // Add the deferred foreign key from slot_holds -> bookings.
        Schema::table('slot_holds', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
        });

        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'created_at']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('status', 20)->default('present');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            $table->unique('booking_id');
        });

        Schema::create('online_class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->string('provider', 30)->default('manual');
            $table->string('meeting_id')->nullable();
            $table->string('join_url')->nullable();
            $table->string('host_url')->nullable();
            $table->string('passcode')->nullable();
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->string('recording_url')->nullable();
            $table->timestamps();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('raised_by_role', 20)->nullable();
            $table->string('reason', 60);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('against_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('reason', 60);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->string('action_taken')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('online_class_sessions');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('booking_status_histories');
        Schema::table('slot_holds', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });
        Schema::dropIfExists('bookings');
    }
};
