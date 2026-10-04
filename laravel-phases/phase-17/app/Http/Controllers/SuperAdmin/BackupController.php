<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Backup;
use App\Services\DatabaseBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Backup & Recovery page for the Super Admin (capstone SCOPE-13, NFR-REQ020):
 * create a backup, download it, upload one that was downloaded before, restore (with confirmation) and delete.
 */
class BackupController extends Controller
{
    public function index(): View
    {
        return view('superadmin.backups', [
            'backups' => Backup::with('creator')->latest()->latest('id')->paginate(15),
            'tables' => count(DatabaseBackup::TABLES),
        ]);
    }

    public function store(Request $request, DatabaseBackup $service): RedirectResponse
    {
        $backup = $service->create($request->user()->id);
        ActivityLog::record('created', 'Backups', 'Created backup ' . $backup->filename . '.', $backup);

        return back()->with('status', 'Backup ' . $backup->filename . ' was created. Download a copy and keep it somewhere safe (e.g. a USB drive).');
    }

    public function upload(Request $request, DatabaseBackup $service): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:51200', 'extensions:json'],
        ], ['backup_file.extensions' => 'Choose a .json backup file made by this system.']);

        $file = $request->file('backup_file');
        $backup = $service->upload($file->get(), $file->getClientOriginalName(), $request->user()->id);
        ActivityLog::record('uploaded', 'Backups', 'Uploaded backup file ' . $backup->filename . '.', $backup);

        return back()->with('status', 'The backup file was uploaded and checked. It can now be restored.');
    }

    public function download(Backup $backup): StreamedResponse
    {
        $path = DatabaseBackup::DIRECTORY . '/' . $backup->filename;
        abort_unless(Storage::disk('local')->exists($path), 404);
        ActivityLog::record('downloaded', 'Backups', 'Downloaded backup ' . $backup->filename . '.', $backup);

        return Storage::disk('local')->download($path, $backup->filename, ['Content-Type' => 'application/json']);
    }

    public function restore(Request $request, Backup $backup, DatabaseBackup $service): RedirectResponse
    {
        $request->validate([
            'confirm_text' => ['required', 'in:RESTORE'],
            'password' => ['required', 'current_password'],
        ], [
            'confirm_text.in' => 'Type RESTORE (in capital letters) to confirm.',
            'password.current_password' => 'Your password is incorrect.',
        ]);

        $safety = $service->restore($backup, $request->user()->id);
        ActivityLog::record('restored', 'Backups', 'Restored the clinic data from ' . $backup->filename . '. The data before the restore was saved as ' . $safety->filename . '.', $backup);

        return back()->with('status', 'The data was restored from ' . $backup->filename . '. The data from before the restore was saved as ' . $safety->filename . ', just in case.');
    }

    public function destroy(Request $request, Backup $backup, DatabaseBackup $service): RedirectResponse
    {
        $name = $backup->filename;
        $service->delete($backup);
        ActivityLog::record('deleted', 'Backups', 'Deleted backup ' . $name . '.');

        return back()->with('status', 'Backup ' . $name . ' was deleted.');
    }
}
