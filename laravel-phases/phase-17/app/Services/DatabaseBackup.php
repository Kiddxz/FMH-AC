<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Database backup and recovery (capstone SCOPE-13, NFR-REQ020).
 *
 * A backup is one JSON file with every row of the clinic tables, kept in storage/app/private/backups
 * (never inside "public", so nobody can download it without logging in as Super Admin).
 * It is made with PHP only, so it works the same on XAMPP/Windows and on any server (no mysqldump needed).
 *
 * Restoring replaces the clinic data with the data in the file, inside ONE database transaction:
 * if anything fails, nothing is changed. A safety backup of the current data is made first.
 * The activity log is not part of a backup and is never replaced (it stays a complete history).
 */
class DatabaseBackup
{
    public const FORMAT = 'fmh-animal-clinic-backup';

    public const DIRECTORY = 'backups';

    // Parents before children, so rows can be inserted in this order and deleted in the reverse order
    public const TABLES = [
        'roles', 'users', 'permissions', 'permission_role',
        'customers', 'pets', 'services', 'clinic_schedules', 'appointments', 'patient_visits',
        'suppliers', 'inventory_items', 'inventory_batches', 'inventory_movements',
        'medical_records', 'prescriptions', 'treatments', 'vaccinations', 'care_instructions',
        'transactions', 'transaction_items', 'payments',
        'waiver_templates', 'waivers', 'settings',
    ];

    public function create(int $userId, string $label = 'manual'): Backup
    {
        $data = ['format' => self::FORMAT, 'created_at' => now()->toIso8601String(), 'label' => $label, 'tables' => []];
        foreach (self::TABLES as $table) {
            $data['tables'][$table] = DB::table($table)->orderBy($this->key($table))->get()->map(fn ($row) => (array) $row)->all();
        }

        $filename = 'fmh-backup_' . now()->format('Y-m-d_His') . ($label === 'manual' ? '' : '_' . $label) . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this->store($filename, $json, $userId);
    }

    // A backup file downloaded earlier (e.g. kept on a USB drive) is added back to the list
    public function upload(string $contents, string $originalName, int $userId): Backup
    {
        $this->read($contents);   // refuses files that are not FMH backups

        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $filename = 'uploaded_' . now()->format('Y-m-d_His') . '_' . (preg_replace('/[^A-Za-z0-9_-]+/', '-', $name) ?: 'backup') . '.json';

        return $this->store($filename, $contents, $userId);
    }

    /**
     * Replace the clinic data with the data in the backup. Returns the safety backup made first.
     */
    public function restore(Backup $backup, int $userId): Backup
    {
        $path = self::DIRECTORY . '/' . $backup->filename;
        if (! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages(['backup' => 'The backup file is missing from the server.']);
        }
        $tables = $this->read(Storage::disk('local')->get($path));

        $safety = $this->create($userId, 'before-restore');

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($tables) {
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }
                foreach (self::TABLES as $table) {
                    foreach (array_chunk($tables[$table], 200) as $rows) {
                        DB::table($table)->insert($rows);
                    }
                }
            });
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['backup' => 'The backup could not be restored, so nothing was changed. (' . class_basename($e) . ')']);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $safety;
    }

    public function delete(Backup $backup): void
    {
        Storage::disk('local')->delete(self::DIRECTORY . '/' . $backup->filename);
        $backup->delete();
    }

    // Checks that the text is an FMH backup and returns its tables (rows as arrays)
    private function read(string $contents): array
    {
        $data = json_decode($contents, true);
        if (! is_array($data) || ($data['format'] ?? null) !== self::FORMAT || ! is_array($data['tables'] ?? null)) {
            throw ValidationException::withMessages(['backup_file' => 'This is not an FMH Animal Clinic backup file.']);
        }
        foreach (self::TABLES as $table) {
            if (! is_array($data['tables'][$table] ?? null)) {
                throw ValidationException::withMessages(['backup_file' => "The backup file is incomplete (the {$table} table is missing)."]);
            }
        }
        if (empty($data['tables']['users']) || empty($data['tables']['roles'])) {
            throw ValidationException::withMessages(['backup_file' => 'The backup file has no user accounts, so it cannot be used.']);
        }

        return $data['tables'];
    }

    private function store(string $filename, string $contents, int $userId): Backup
    {
        Storage::disk('local')->put(self::DIRECTORY . '/' . $filename, $contents);

        $backup = new Backup(['filename' => $filename, 'size_bytes' => strlen($contents)]);
        $backup->created_by = $userId;
        $backup->save();

        return $backup;
    }

    // permission_role has no "id" column
    private function key(string $table): string
    {
        return $table === 'permission_role' ? 'role_id' : 'id';
    }
}
