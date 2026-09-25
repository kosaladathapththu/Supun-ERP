<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')->where('code', 'SUPUN')->update([
            'name' => 'Keep & Care',
            'legal_name' => 'Keep & Care',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('companies')->where('code', 'SUPUN')->update([
            'name' => 'Fuji Industries',
            'legal_name' => 'Fuji Industries',
            'updated_at' => now(),
        ]);
    }
};
