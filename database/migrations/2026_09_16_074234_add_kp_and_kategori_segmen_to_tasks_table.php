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
            //
            $table->string('kp')->nullable()->after('customer_name'); // surabaya, malang, madiun, jember
            $table->string('kategori_segmen')->default('publik')->after('kp'); // pln, publik
            $table->index('kp');
            $table->index('kategori_segmen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            //
            $table->dropIndex(['kp']);
            $table->dropIndex(['kategori_segmen']);
            $table->dropColumn(['kp', 'kategori_segmen']);
        });
    }
};
