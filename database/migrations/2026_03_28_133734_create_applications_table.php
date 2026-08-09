<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->nullable()->unique();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->string('applicant_first_name');
            $table->string('applicant_last_name');
            $table->string('applicant_national_id', 11);
            $table->string('applicant_phone')->nullable();
            $table->string('excavation_reason')->nullable();
            $table->string('work_type')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_area_m2', 14, 4)->default(0);
            $table->decimal('total_price', 14, 2)->nullable();
            $table->decimal('discovery_amount', 14, 2)->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('approval_status')->default('pending');
            $table->timestamp('price_approved_at')->nullable();
            $table->foreignId('price_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('receipt_approved_at')->nullable();
            $table->foreignId('receipt_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->string('address_text')->nullable();
            $table->string('license_document_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
