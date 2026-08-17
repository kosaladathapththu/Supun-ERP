<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(int $companyId, string $type, string $prefix, ?int $year = null): string
    {
        $year = $year ?: now()->year;

        return DB::transaction(function () use ($companyId, $type, $prefix, $year) {
        $seq = DB::table('number_sequences')->where('company_id', $companyId)->where('document_type', $type)->where('year', $year)->lockForUpdate()->first();
        if (! $seq) {
        DB::table('number_sequences')->insert(['company_id' => $companyId, 'document_type' => $type, 'prefix' => $prefix, 'year' => $year, 'next_number' => 2, 'padding' => 6, 'created_at' => now(), 'updated_at' => now()]);
        $number = 1;
        $padding = 6;
        } else {
        $number = $seq->next_number;
        $padding = $seq->padding;
        DB::table('number_sequences')->where('id', $seq->id)->update(['next_number' => $number + 1, 'updated_at' => now()]);
        }

return $prefix.'-'.$year.'-'.str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
        });
    }
}
