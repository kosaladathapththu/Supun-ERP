<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')
            ->where('code', 'SUPUN')
            ->update([
                'name' => 'Camy Global Marcket',
                'legal_name' => 'Camy Global Marcket',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('companies')
            ->where('code', 'SUPUN')
            ->update([
                'name' => 'Supun Group',
                'legal_name' => 'Supun Group',
                'updated_at' => now(),
            ]);
    }
};
