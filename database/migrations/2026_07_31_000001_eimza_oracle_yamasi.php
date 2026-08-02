<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-İmza Oracle Yaması
 * ----------------------
 * E-İmza modülünün Oracle veritabanında sorunsuz çalışması için gerekli
 * tablo ve kolonları oluşturur. Sistem yöneticisi şu komutu çalıştırarak
 * gerçek Oracle sistemine aktarabilir:
 *
 *     php artisan migrate
 *
 * Tüm işlemler idempotent'tir (tablo/kolon zaten varsa atlanır), bu yüzden
 * hem ilk kurulumda hem de mevcut sistemlerde güvenle çalışır.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. e_imza_transactions tablosu (E-İmza işlem logları)
        if (!Schema::hasTable('e_imza_transactions')) {
            Schema::create('e_imza_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained()->cascadeOnDelete();
                $table->string('pdf_type', 50);
                $table->string('status', 20)->default('pending');
                $table->string('transaction_id', 100)->unique();
                $table->string('token', 255);
                $table->string('orijinal_pdf', 255)->nullable();
                $table->string('imzali_pdf', 255)->nullable();
                $table->longText('imzalayan_info')->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. e_imza_istemcileri tablosu (API anahtarı olan e-imza istemcileri)
        if (!Schema::hasTable('e_imza_istemcileri')) {
            Schema::create('e_imza_istemcileri', function (Blueprint $table) {
                $table->id();
                $table->string('api_key', 100)->unique();
                $table->string('label', 255)->nullable();
                $table->timestamp('son_erisim')->nullable();
                $table->timestamps();
            });
        }

        // 3. applications tablosuna ek kolonlar (e-imza + kurum devri)
        Schema::table('applications', function (Blueprint $table) {
            // İmzalı belgeler (JSON string olarak CLOB'da saklanır)
            if (!Schema::hasColumn('applications', 'module_documents')) {
                $table->longText('module_documents')->nullable();
            }
            // Kurum yetkilileri
            if (!Schema::hasColumn('applications', 'tesis_sorumlusu')) {
                $table->string('tesis_sorumlusu', 255)->nullable();
            }
            if (!Schema::hasColumn('applications', 'mudur_adi')) {
                $table->string('mudur_adi', 255)->nullable();
            }
            if (!Schema::hasColumn('applications', 'mudur_unvani')) {
                $table->string('mudur_unvani', 255)->nullable();
            }
            // Görev devri (Kuruma gönder) — atanan kullanıcı
            if (!Schema::hasColumn('applications', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            foreach (['module_documents', 'tesis_sorumlusu', 'mudur_adi', 'mudur_unvani'] as $col) {
                if (Schema::hasColumn('applications', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('applications', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
        });

        Schema::dropIfExists('e_imza_istemcileri');
        Schema::dropIfExists('e_imza_transactions');
    }
};
