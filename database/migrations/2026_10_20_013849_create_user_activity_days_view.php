<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS user_activity_days');
        DB::statement(DatabaseView::sql('2026_10_20_013849_user_activity_days.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS user_activity_days');
    }
};
