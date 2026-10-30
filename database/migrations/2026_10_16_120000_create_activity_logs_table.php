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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->string('user_role');
            $table->string('action');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('document_number')->nullable();
            $table->text('description');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['created_at', 'user_id']);
            $table->index('category');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
