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
            $table->foreignId('comment_updated_by')->nullable()->after('comments')->constrained('users')->nullOnDelete();
            $table->timestamp('comment_updated_at')->nullable()->after('comment_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['comment_updated_by']);
            $table->dropColumn(['comment_updated_by', 'comment_updated_at']);
        });
    }
};
