<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->decimal('original_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->string('type'); // payment, refund, deposit, withdrawal, p2p
            $table->string('status')->default('pending'); // pending, completed, failed, cancelled
            $table->string('reference')->unique()->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
