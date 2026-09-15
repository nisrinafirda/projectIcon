<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category'); // sso_open, baa, bai, exception, kontrak_exp
            $table->string('document_number'); // unique identifier per category (e.g. ticket/doc no)
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('service_type')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('pending'); // pending, in_progress, submitted, approved, rejected
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('submission_notes')->nullable();
            $table->string('submission_attachment')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Anti-duplicate constraint: prevent same document_number within the same category
            $table->unique(['category', 'document_number'], 'unique_category_doc_number');
            $table->index(['category', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
