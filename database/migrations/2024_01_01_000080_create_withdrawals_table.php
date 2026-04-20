<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('total_deducted_amount', 15, 2);
            $table->string('commission_type')->nullable(); // snapshot from agent settings
            $table->decimal('commission_value', 12, 2)->nullable(); // snapshot from agent settings
            $table->string('status')->default('pending'); // pending, completed, failed, cancelled
            $table->text('verification_code'); // encrypted OTP
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
