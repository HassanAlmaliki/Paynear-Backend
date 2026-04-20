<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type'); // user, merchant
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('currency')->default('YER');
            $table->string('status')->default('active'); // active, inactive, frozen
            $table->timestamps();

            $table->index(['owner_id', 'owner_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
