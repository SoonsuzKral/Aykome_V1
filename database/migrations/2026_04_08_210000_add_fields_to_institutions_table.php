<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('type')->nullable()->after('is_municipality');
            $table->string('authorized_person')->nullable()->after('type');
            $table->string('tax_number', 20)->nullable()->after('authorized_person');
            $table->string('phone', 30)->nullable()->after('tax_number');
            $table->string('email')->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['type', 'authorized_person', 'tax_number', 'phone', 'email', 'address']);
        });
    }
};
