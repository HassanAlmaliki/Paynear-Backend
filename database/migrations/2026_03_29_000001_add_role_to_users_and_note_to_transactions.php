<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة حقل الدور للمستخدمين
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('status'); // user, pos
        });

        // إضافة حقل الملاحظة للمعاملات
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('note')->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
