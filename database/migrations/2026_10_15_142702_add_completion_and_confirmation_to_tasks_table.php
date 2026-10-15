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
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('late_reason')->nullable()->after('comments');
            $table->boolean('was_late')->default(false)->after('late_reason');
            $table->timestamp('done_marked_at')->nullable()->after('completed_at');
            $table->foreignId('done_marked_by')->nullable()->after('done_marked_at')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('reviewed_at');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['done_marked_by']);
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn([
                'late_reason',
                'was_late',
                'done_marked_at',
                'done_marked_by',
                'confirmed_at',
                'confirmed_by',
            ]);
        });
    }
};
