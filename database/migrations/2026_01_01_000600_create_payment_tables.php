<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Authoritative slot-lock table. The unique index guarantees that two
        // parents can never reserve the same teacher/time slot.
        Schema::create('slot_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->dateTime('slot_start_at');
            $table->dateTime('slot_end_at');
            $table->string('reservation_type', 20)->default('hold')->index();
            $table->foreignId('slot_hold_id')->nullable()->constrained('slot_holds')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'slot_start_at'], 'slot_res_unique_slot');
            $table->index(['teacher_profile_id', 'slot_start_at', 'slot_end_at'], 'slot_res_teach_slot_idx');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 32)->unique();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('parent_profile_id')->nullable()->constrained('parent_profiles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gateway', 30)->default('razorpay');
            $table->string('order_id', 64)->unique();
            $table->string('gateway_order_id', 64)->nullable()->index();
            $table->string('gateway_payment_id', 80)->nullable()->index();
            $table->string('gateway_signature', 255)->nullable();
            $table->string('method', 40)->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('gateway_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('status', 30)->default('created')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('invoice_number', 32)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['booking_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique();
            $table->string('event_type', 80)->index();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('gateway_order_id', 80)->nullable()->index();
            $table->string('gateway_payment_id', 80)->nullable()->index();
            $table->string('signature', 255)->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 32)->unique();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('platform_fee_retained', 10, 2)->default(0);
            $table->decimal('teacher_share_refunded', 10, 2)->default(0);
            $table->string('reason', 60);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway_refund_id', 80)->nullable()->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission_percent', 6, 3);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('fixed_fee', 10, 2)->default(0);
            $table->decimal('teacher_amount', 12, 2);
            $table->decimal('platform_amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->timestamps();
            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_commissions');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('slot_reservations');
    }
};
