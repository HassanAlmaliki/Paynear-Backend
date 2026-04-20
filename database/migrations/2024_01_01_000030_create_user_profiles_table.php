<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type'); // user, merchant
            $table->string('id_type')->nullable(); // national_id, passport, etc
            $table->text('id_number')->nullable(); // encrypted
            $table->text('id_front_image')->nullable(); // encrypted path
            $table->text('id_back_image')->nullable(); // encrypted path
            $table->date('id_expiry_date')->nullable();
            $table->string('nationality')->nullable();
            $table->text('address')->nullable();
            $table->date('dob')->nullable();
            $table->string('verification_status')->default('pending'); // pending, pending_verification, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'owner_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
