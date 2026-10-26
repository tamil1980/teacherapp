<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->decimal('adjustment_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('available_at')->nullable()->index();
            $table->foreignId('payout_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique('booking_id');
            $table->index(['teacher_profile_id', 'status']);
        });

        Schema::create('teacher_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('reference_no', 32)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('method', 30)->default('bank_transfer');
            $table->string('status', 20)->default('requested')->index();
            $table->string('gateway_payout_id', 80)->nullable()->index();
            $table->string('bank_account_masked', 120)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_payout_id')->constrained('teacher_payouts')->cascadeOnDelete();
            $table->foreignId('teacher_earning_id')->constrained('teacher_earnings')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['teacher_payout_id', 'teacher_earning_id']);
        });

        Schema::table('teacher_earnings', function (Blueprint $table) {
            $table->foreign('payout_id')->references('id')->on('teacher_payouts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_earnings', function (Blueprint $table) {
            $table->dropForeign(['payout_id']);
        });
        Schema::dropIfExists('teacher_payout_items');
        Schema::dropIfExists('teacher_payouts');
        Schema::dropIfExists('teacher_earnings');
    }
};
