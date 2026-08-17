<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Validation\ValidationException;

class DatabaseBackupService
{
    public function create($user): BackupRun
    {
        $connection = config('database.default');
        $cfg = config("database.connections.$connection");
        if (($cfg['driver'] ?? null) !== 'mysql') {
            throw ValidationException::withMessages(['backup' => 'Database backups are available only for MySQL.']);
        }$dir = storage_path('app/backups');
        if (! is_dir($dir) && ! mkdir($dir, 0750, true)) {
            throw ValidationException::withMessages(['backup' => 'Cannot create the protected backup directory.']);
        }$filename = 'supun-erp-'.now()->format('Ymd-His').'.sql';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        $run = BackupRun::create(['company_id' => $user->company_id, 'created_by' => $user->id, 'filename' => $filename, 'status' => 'running']);
        $exe = 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe';
        if (! is_file($exe)) {
            $exe = 'D:\\Software\\Xammp\\mysql\\bin\\mysqldump.exe';
        }$env = getenv();
        $env['MYSQL_PWD'] = (string) $cfg['password'];
        $cmd = '"'.$exe.'" --host='.escapeshellarg($cfg['host']).' --port='.escapeshellarg((string) $cfg['port']).' --user='.escapeshellarg($cfg['username']).' --single-transaction --routines --events --result-file='.escapeshellarg($path).' '.escapeshellarg($cfg['database']);
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes, null, $env);
        $error = '';
        $code = 1;
        if (is_resource($process)) {
            stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);
        }if ($code !== 0 || ! is_file($path)) {
            $run->update(['status' => 'failed', 'error_message' => substr($error, 0, 1000)]);
            throw ValidationException::withMessages(['backup' => 'Backup failed. Review the backup log.']);
        }$run->update(['status' => 'completed', 'size_bytes' => filesize($path), 'checksum' => hash_file('sha256', $path), 'completed_at' => now()]);

        return $run->fresh();
    }
}
