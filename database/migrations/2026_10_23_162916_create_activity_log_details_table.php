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
        Schema::create('activity_log_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->constrained('activity_logs')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('sid')->nullable();
            $table->string('change_type', 20);
            $table->string('field');
            $table->string('field_label');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();

            $table->index(['activity_log_id', 'id']);
            $table->index('customer_name');
            $table->index('sid');
            $table->index('change_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log_details');
    }
};
