<?php

namespace App\Console\Commands;

use App\Models\BackupRun;
use Illuminate\Console\Command;

class VerifyErpBackup extends Command
{
    protected $signature = 'erp:backup:verify {filename? : Backup filename in storage/app/backups}';

    protected $description = 'Verify checksum and required schema markers in an ERP SQL backup';

    public function handle(): int
    {
        $record = $this->argument('filename') ? BackupRun::where('filename', $this->argument('filename'))->latest('id')->first() : BackupRun::where('status', 'completed')->latest('id')->first();
        if (! $record) {
            $this->error('No completed backup record found.');

            return self::FAILURE;
        }$path = storage_path('app/backups/'.$record->filename);
        if (! is_file($path)) {
            $this->error('Backup file is missing.');

            return self::FAILURE;
        }$checksum = hash_file('sha256', $path);
        if (! hash_equals((string) $record->checksum, $checksum)) {
            $this->error('Checksum mismatch: backup integrity failed.');

            return self::FAILURE;
        }$handle = fopen($path, 'r');
        $found = [];
        $required = ['users', 'products', 'sales', 'journal_entries', 'audit_logs'];
        while (! feof($handle) && count($found) < count($required)) {
            $line = fgets($handle);
            foreach ($required as $table) {
                if (! isset($found[$table]) && stripos($line, 'CREATE TABLE `'.$table.'`') !== false) {
                    $found[$table] = true;
                }
            }
        }fclose($handle);
        $missing = array_diff($required, array_keys($found));
        if ($missing) {
            $this->error('Required tables missing: '.implode(', ', $missing));

            return self::FAILURE;
        }$this->info("Backup integrity verified: {$record->filename}");
        $this->line("SHA-256: {$checksum}");
        $this->warn('This verifies the artifact; promotion still requires an isolated database restore drill.');

        return self::SUCCESS;
    }
}
