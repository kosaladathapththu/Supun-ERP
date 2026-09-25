<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')->where('code', 'SUPUN')->update([
            'name' => 'Keep & Care Supun Group Of Company',
            'legal_name' => 'Keep & Care Supun Group Of Company',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('companies')->where('code', 'SUPUN')->update([
            'name' => 'Keep & Care',
            'legal_name' => 'Keep & Care',
            'updated_at' => now(),
        ]);
    }
};
