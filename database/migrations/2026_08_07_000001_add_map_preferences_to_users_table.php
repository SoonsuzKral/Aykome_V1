<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kullanıcıya özel WMS katman renk tercihleri.
     * Kişiye özel olarak maps/index.blade.php'deki katman renklerini
     * (layerName -> colorHex) JSON olarak users.map_preferences kolonunda saklar.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('map_preferences')->nullable()->after('institution_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('map_preferences');
        });
    }
};