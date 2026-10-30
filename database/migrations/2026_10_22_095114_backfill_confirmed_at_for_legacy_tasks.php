<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'confirmed_at')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->timestamp('confirmed_at')->nullable()->after('reviewed_at');
            });
        }

        DB::table('tasks')
            ->where(function ($query): void {
                $query->where('status', 'approved')
                    ->orWhereNotNull('confirmed_by');
            })
            ->whereNull('confirmed_at')
            ->update(['confirmed_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data backfill.
    }
};
