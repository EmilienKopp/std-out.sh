<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_runs', function (Blueprint $table) {
            // No FK constraint: adding one to an existing sqlite table forces a
            // table rebuild that drops dependent views. Plain indexed column.
            $table->unsignedBigInteger('user_id')->nullable()->after('team_id');
            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::table('practice_runs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'started_at']);
            $table->dropColumn('user_id');
        });
    }
};
