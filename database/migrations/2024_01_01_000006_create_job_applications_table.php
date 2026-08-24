<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('attachment_document_id')->nullable()->constrained('attachment_documents')->onDelete('set null');
            $table->string('recipient_email');
            $table->string('subject');
            $table->string('company_name');
            $table->string('position');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
